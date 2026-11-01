<?php

namespace App\Jobs;

use App\Services\Assistant\AssistantHandoverService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendAssistantEscalation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $retryAfter = 45;

    public $timeout = 30;

    protected $conversationId;

    protected $details;

    public function __construct($conversationId, array $details)
    {
        $this->conversationId = (int) $conversationId;
        $this->details = $details;
    }

    public function handle(AssistantHandoverService $handover)
    {
        $result = $handover->retrySend($this->conversationId, $this->details);
        if (! empty($result['retrying'])) {
            throw new \RuntimeException('escalation_not_submitted');
        }
    }
}
