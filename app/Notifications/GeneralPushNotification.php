<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class GeneralPushNotification extends Notification
{
    use Queueable;

    protected $title;
    protected $body;
    protected $url;
    protected $icon;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($title, $body, $url = '/dashboard', $icon = '/icons/icon-192x192.png')
    {
        $this->title = $title;
        $this->body = $body;
        $this->url = $url;
        $this->icon = $icon;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return [WebPushChannel::class];
    }

    /**
     * Get the web push representation of the notification.
     *
     * @param  mixed  $notifiable
     * @param  mixed  $notification
     * @return \Illuminate\Notifications\Messages\WebPushMessage
     */
    public function toWebPush($notifiable, $notification)
    {
        return (new WebPushMessage)
            ->title($this->title)
            ->icon($this->icon)
            ->body($this->body)
            ->action('Buka Aplikasi', $this->url)
            ->data(['url' => $this->url]);
    }
}
