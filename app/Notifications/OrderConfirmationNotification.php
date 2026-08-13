<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class OrderConfirmationNotification extends Notification
{
    use Queueable; // sent asynchronously, not inline during the request

    public function __construct(private Order $order) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Order Confirmed — {$this->order->order_number}")
            ->greeting("Thanks for your order, {$notifiable->name}!")
            ->line("Your order {$this->order->order_number} has been placed successfully.")
            ->line('Total: $'.number_format($this->order->total_cents / 100, 2))
            ->line('We\'ll notify you again once it ships.');
    }
}
