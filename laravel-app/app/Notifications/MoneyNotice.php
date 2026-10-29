<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MoneyNotice extends Notification
{
    use Queueable;

    protected $message;

    public function __construct($message)
    {
        $this->message = (string) $message;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'message' => $this->message,
        ];
    }
}
