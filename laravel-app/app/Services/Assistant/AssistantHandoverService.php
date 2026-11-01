<?php

namespace App\Services\Assistant;

use App\Jobs\SendAssistantEscalation;
use App\Services\WhatsApp\Providers\WaSenderProvider;
use App\Services\WhatsApp\WhatsAppConversationService;
use App\User;
use App\WhatsApp\WhatsAppConversation;
use App\WhatsApp\WhatsAppConversationEvent;
use App\WhatsApp\WhatsAppSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AssistantHandoverService
{
    protected $conversations;

    public function __construct(WhatsAppConversationService $conversations)
    {
        $this->conversations = $conversations;
    }

    public function toHuman(WhatsAppConversation $conversation, $reason = 'handover')
    {
        $conversation->mode = WhatsAppConversation::MODE_HUMAN;
        if ($conversation->status === WhatsAppConversation::STATUS_CLOSED) {
            $conversation->status = WhatsAppConversation::STATUS_WAITING_STAFF;
        }
        $agentId = AssistantRuntimeSettings::handoverUserId();
        if ($agentId > 0 && ! $conversation->assigned_user_id && User::where('id', $agentId)->exists()) {
            $conversation->assigned_user_id = $agentId;
        }
        $conversation->save();
        WhatsAppConversationEvent::create([
            'conversation_id' => $conversation->id,
            'type' => WhatsAppConversationEvent::TAKEOVER,
            'body' => 'AI handed over: '.$reason,
        ]);

        return $conversation;
    }

    public function escalationNumber()
    {
        $stored = trim((string) WhatsAppSetting::getValue('AI_ESCALATION_WHATSAPP_NUMBER', ''));
        if ($stored === '') {
            $stored = trim((string) config('assistant.escalation_whatsapp_number', ''));
        }

        return $stored;
    }

    public function notifyStaff(WhatsAppConversation $conversation, array $details)
    {
        $conversation = $this->toHuman($conversation, isset($details['reason']) ? $details['reason'] : 'handover');
        $conversation->status = WhatsAppConversation::STATUS_WAITING_STAFF;
        $conversation->save();

        $number = $this->escalationNumber();
        $customer = $conversation->contact ? (string) $conversation->contact->normalized_phone : '';
        if ($number !== '' && $customer !== '' && $this->samePhone($number, $customer)) {
            $this->flagFailure($conversation, 'loop_blocked');

            return $this->result(false, false, true, 'loop_blocked');
        }
        if ($number === '') {
            $this->flagFailure($conversation, 'escalation_number_missing');

            return $this->result(false, false, true, 'escalation_number_missing');
        }

        $claim = $this->claim($conversation->id);
        if ($claim === 'submitted') {
            return $this->result(true, false, true, null, true);
        }
        if ($claim === 'in_progress') {
            return $this->result(false, false, true, null, true);
        }

        return $this->attempt($conversation, $details, $number, $customer, true);
    }

    public function retrySend($conversationId, array $details)
    {
        $conversation = WhatsAppConversation::with('contact')->find($conversationId);
        if (! $conversation || $conversation->mode !== WhatsAppConversation::MODE_HUMAN) {
            return $this->result(false, false, true, 'not_human');
        }
        $number = $this->escalationNumber();
        if ($number === '') {
            $this->flagFailure($conversation, 'escalation_number_missing');

            return $this->result(false, false, true, 'escalation_number_missing');
        }
        $claim = $this->claim($conversation->id);
        if ($claim === 'submitted') {
            return $this->result(true, false, true, null, true);
        }
        if ($claim === 'in_progress') {
            return $this->result(false, false, true, 'in_progress', true);
        }
        $customer = $conversation->contact ? (string) $conversation->contact->normalized_phone : '';

        return $this->attempt($conversation, $details, $number, $customer, false);
    }

    protected function attempt(WhatsAppConversation $conversation, array $details, $number, $customer, $queueOnFailure)
    {
        $summary = $this->staffSummary($conversation, $details, $customer);
        try {
            $sent = app(WaSenderProvider::class)->sendPlainText($number, $summary);
        } catch (\Throwable $e) {
            $sent = ['success' => false, 'error' => $e->getMessage(), 'transient' => true];
        }
        if (! empty($sent['success'])) {
            $this->mark($conversation, 'Escalation submitted');

            return $this->result(true, false, true);
        }

        $error = isset($sent['error']) ? (string) $sent['error'] : 'send_failed';
        $transient = ! empty($sent['transient']) || $this->isTransient($error);
        if ($transient) {
            $this->mark($conversation, 'Escalation retrying: '.mb_substr($error, 0, 120));
            if ($queueOnFailure) {
                SendAssistantEscalation::dispatch($conversation->id, $this->jobDetails($details))->onQueue('whatsapp');
            }
            Log::warning('[beyond-assistant] handover alert retrying', [
                'conversation_id' => $conversation->id,
                'error' => mb_substr($error, 0, 180),
            ]);

            return $this->result(false, false, true, $error, false, true);
        }

        $this->flagFailure($conversation, $error);

        return $this->result(false, false, true, $error);
    }

    protected function claim($conversationId)
    {
        return DB::transaction(function () use ($conversationId) {
            WhatsAppConversation::where('id', $conversationId)->lockForUpdate()->first();
            $submitted = WhatsAppConversationEvent::where('conversation_id', $conversationId)
                ->where('type', WhatsAppConversationEvent::TAKEOVER)
                ->where('body', 'Escalation submitted')
                ->where('created_at', '>=', now()->subMinutes(15))
                ->exists();
            if ($submitted) {
                return 'submitted';
            }
            $sending = WhatsAppConversationEvent::where('conversation_id', $conversationId)
                ->where('type', WhatsAppConversationEvent::TAKEOVER)
                ->where('body', 'Escalation sending')
                ->where('created_at', '>=', now()->subMinutes(2))
                ->exists();
            if ($sending) {
                return 'in_progress';
            }
            WhatsAppConversationEvent::create([
                'conversation_id' => $conversationId,
                'type' => WhatsAppConversationEvent::TAKEOVER,
                'body' => 'Escalation sending',
            ]);

            return 'claimed';
        });
    }

    protected function mark(WhatsAppConversation $conversation, $body)
    {
        $row = WhatsAppConversationEvent::where('conversation_id', $conversation->id)
            ->where('type', WhatsAppConversationEvent::TAKEOVER)
            ->where('body', 'Escalation sending')
            ->orderByDesc('id')
            ->first();
        if ($row) {
            $row->body = $body;
            $row->save();

            return;
        }
        WhatsAppConversationEvent::create([
            'conversation_id' => $conversation->id,
            'type' => WhatsAppConversationEvent::TAKEOVER,
            'body' => $body,
        ]);
    }

    protected function staffSummary(WhatsAppConversation $conversation, array $details, $customer)
    {
        $question = $this->redact(isset($details['question']) ? $details['question'] : '');
        $reason = $this->redact(isset($details['reason']) ? $details['reason'] : 'handover');
        $language = trim((string) (isset($details['language']) ? $details['language'] : ''));
        $link = route('whatsapp.conversation', $conversation->id);

        return implode("\n", array_filter([
            'Mbole AI handover',
            'Customer: '.$customer,
            $language !== '' ? 'Language: '.$language : null,
            'Question: '.$question,
            'Reason: '.$reason,
            'Conversation: '.$link,
        ]));
    }

    public function redact($text)
    {
        $text = trim((string) $text);
        $text = preg_replace('/\b(?:\d[ -]?){13,19}\b/', '[redacted]', $text);
        $text = preg_replace('/\b(otp|pin|cvv|cvc|password|secret|token)\b[:\s]*\S*/i', '[redacted]', $text);

        return mb_substr($text, 0, 500);
    }

    protected function flagFailure(WhatsAppConversation $conversation, $error)
    {
        Log::warning('[beyond-assistant] handover delivery failed', [
            'conversation_id' => $conversation->id,
            'error' => mb_substr((string) $error, 0, 180),
        ]);
        $this->mark($conversation, 'Escalation failed: '.mb_substr((string) $error, 0, 160));
    }

    protected function isTransient($error)
    {
        return (bool) preg_match('/timeout|temporar|unavailable|connection|429|500|502|503|504/i', (string) $error);
    }

    protected function samePhone($left, $right)
    {
        $a = substr(preg_replace('/\D+/', '', (string) $left), -9);
        $b = substr(preg_replace('/\D+/', '', (string) $right), -9);

        return $a !== '' && $a === $b;
    }

    protected function jobDetails(array $details)
    {
        return [
            'reason' => isset($details['reason']) ? $this->redact($details['reason']) : 'handover',
            'question' => isset($details['question']) ? $this->redact($details['question']) : '',
            'language' => isset($details['language']) ? mb_substr((string) $details['language'], 0, 40) : '',
        ];
    }

    protected function result($submitted, $delivered, $queued, $error = null, $duplicate = false, $retrying = false)
    {
        if ($submitted && ! $delivered) {
            $instruction = 'Tell the customer the request was sent to the hotel team. Do not say it was delivered or read.';
        } elseif ($delivered) {
            $instruction = 'Tell the customer the hotel team received the alert.';
        } else {
            $instruction = 'Do not say the request was forwarded or sent. Say it is waiting in the hotel support queue.';
        }

        return [
            'handed_over' => true,
            'submitted' => (bool) $submitted,
            'delivered' => (bool) $delivered,
            'notified' => (bool) $submitted,
            'queued' => (bool) $queued,
            'duplicate' => (bool) $duplicate,
            'retrying' => (bool) $retrying,
            'error' => $error,
            'customer_instruction' => $instruction,
        ];
    }
}
