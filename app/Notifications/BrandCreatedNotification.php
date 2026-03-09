<?php

namespace App\Notifications;

use App\Models\Brand;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BrandCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Brand $brand)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->brand->loadMissing('brandCountry', 'brandType', 'classes', 'creator', 'statusDefinition');

        return (new MailMessage)
            ->subject('Nueva solicitud de marca: '.$this->brand->display_name)
            ->view('mail.brands.new-brand', [
                'brand' => $this->brand,
                'user' => $notifiable,
                'ctaUrl' => route('brands.index'),
            ]);
    }
}
