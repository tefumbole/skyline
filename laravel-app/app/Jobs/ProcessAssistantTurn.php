<?php

namespace App\Jobs;

use App\Services\Assistant\BeyondAssistantService;
use App\WhatsApp\WhatsAppMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessAssistantTurn implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;

    public $timeout = 90;

    protected $messageId;

    public function __construct($messageId)
    {
        $this->messageId = (int) $messageId;
    }

    public function handle(BeyondAssistantService $assistant)
    {
        $message = WhatsAppMessage::with('conversation.contact')->find($this->messageId);
        if (! $message || $message->direction !== WhatsAppMessage::DIR_IN) {
            return;
        }
        $started = microtime(true);
        $conversationId = (int) $message->conversation_id;
        $queueDelayMs = $message->created_at ? (int) (microtime(true) * 1000) - (int) ($message->created_at->getTimestamp() * 1000) : null;
        if (! $this->lockConversation($conversationId)) {
            $this->release(8);

            return;
        }
        try {
            if (! $this->prepareAudio($message)) {
                return;
            }
            $message = $message->fresh(['conversation.contact']);
            if (! $message) {
                return;
            }
            if (strtoupper((string) $message->type) === 'AUDIO') {
                $conversation = $message->conversation ? $message->conversation->fresh() : null;
                if (! $conversation || $conversation->mode !== \App\WhatsApp\WhatsAppConversation::MODE_AI) {
                    return;
                }
            }
            $tenantId = null;
            if (\Illuminate\Support\Facades\Schema::hasColumn('whatsapp_conversations', 'cloud_tenant_id')) {
                $tenantId = app(\App\Services\Cloud\CloudTenantContext::class)->withoutIsolation(function () use ($message) {
                    $conversation = \App\WhatsApp\WhatsAppConversation::find($message->conversation_id);

                    return $conversation ? $conversation->cloud_tenant_id : null;
                });
                if (! $tenantId) {
                    \Illuminate\Support\Facades\Log::warning('[beyond-assistant] turn has no company', [
                        'message_id' => $this->messageId,
                    ]);

                    return;
                }
            }
            $run = function () use ($assistant, $message) {
                $tenant = app(\App\Services\Cloud\CloudTenantContext::class)->tenant();
                if ($tenant && $tenant->type === \App\Cloud\CloudTenantType::CUSTOMER
                    && \Illuminate\Support\Facades\Schema::hasTable('cloud_subscriptions')
                    && ! app(\App\Services\Cloud\CloudModuleAccessService::class)->canWriteCapability($tenant, 'messaging')) {
                    \Illuminate\Support\Facades\Log::info('[beyond-assistant] reply skipped, messaging not entitled', [
                        'message_id' => $this->messageId,
                        'cloud_tenant_id' => $tenant->id,
                    ]);

                    return;
                }
                $assistant->handleIncoming($message);
            };
            if ($tenantId) {
                app(\App\Services\Cloud\CloudTenantContextRunner::class)->run($tenantId, $run);
            } else {
                $run();
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[beyond-assistant] turn failed', [
                'message_id' => $this->messageId,
                'error' => $e->getMessage(),
            ]);
        } finally {
            $this->unlockConversation($conversationId);
            \Illuminate\Support\Facades\Log::info('[beyond-assistant] timing', [
                'message_id' => $this->messageId,
                'queue_delay_ms' => $queueDelayMs,
                'total_ms' => (int) ((microtime(true) - $started) * 1000),
            ]);
        }
    }

    protected function prepareAudio(WhatsAppMessage $message)
    {
        if (strtoupper((string) $message->type) !== 'AUDIO') {
            return true;
        }
        $conversation = $message->conversation ? $message->conversation->fresh() : null;
        if (! $conversation || $conversation->mode !== \App\WhatsApp\WhatsAppConversation::MODE_AI) {
            return false;
        }
        $media = $message->media();
        if (! empty($media['transcript_error']) || $message->body === '[voice note unavailable]') {
            return false;
        }
        $started = microtime(true);
        $result = app(\App\Services\WhatsApp\WhatsAppAudioTranscriber::class)->transcribe($message);
        \Illuminate\Support\Facades\Log::info('[beyond-assistant] transcription', [
            'message_id' => $message->id,
            'ms' => (int) ((microtime(true) - $started) * 1000),
            'ok' => ! empty($result['success']),
            'cached' => ! empty($result['cached']),
        ]);
        if (! empty($result['success'])) {
            return true;
        }
        $conversation = $message->conversation ? $message->conversation->fresh() : null;
        $media = app(\App\Services\WhatsApp\WhatsAppAudioTranscriber::class)->forAssistant($message->media());
        $media['transcript_error'] = isset($result['error']) ? $result['error'] : 'audio_unclear';
        $message->media_json = json_encode($media);
        $message->body = '[voice note unavailable]';
        $message->save();
        if (! $conversation || $conversation->mode !== \App\WhatsApp\WhatsAppConversation::MODE_AI) {
            return false;
        }
        $previous = null;
        if (\Illuminate\Support\Facades\Schema::hasTable('assistant_memories')) {
            $memory = app(\App\Services\Assistant\AssistantConversationMemory::class)->forConversation($conversation->id);
            $params = $memory->parameters();
            $previous = isset($params['reply_language']) ? $params['reply_language'] : null;
        }
        $french = $previous === 'fr' || $previous === 'french';
        $body = $french
            ? 'Je n’ai pas bien entendu ce message vocal. Renvoyez-le ou écrivez votre question.'
            : 'I could not hear that voice note clearly. Please send it again or type your question.';
        app(\App\Services\WhatsApp\WhatsAppConversationService::class)->assistantReply($conversation, $body);

        return false;
    }

    protected function lockConversation($conversationId)
    {
        if (\Illuminate\Support\Facades\DB::connection()->getDriverName() !== 'mysql') {
            return true;
        }
        $row = \Illuminate\Support\Facades\DB::select('SELECT GET_LOCK(?, 20) AS locked', ['mbole-ai-'.(int) $conversationId]);

        return isset($row[0]->locked) && (int) $row[0]->locked === 1;
    }

    protected function unlockConversation($conversationId)
    {
        if (\Illuminate\Support\Facades\DB::connection()->getDriverName() !== 'mysql') {
            return;
        }
        \Illuminate\Support\Facades\DB::select('SELECT RELEASE_LOCK(?)', ['mbole-ai-'.(int) $conversationId]);
    }
}
