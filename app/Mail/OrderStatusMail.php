<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        $subject = match ($this->order->status) {
            'confirmed' => "Order Confirmed ({$this->order->order_number}) — Kohan Mart",
            'ready_for_pickup' => "Ready for Pickup! ({$this->order->order_number}) — Kohan Mart",
            'completed' => "Order Completed & Thank You! ({$this->order->order_number}) — Kohan Mart",
            'cancelled' => "Order Cancelled ({$this->order->order_number}) — Kohan Mart",
            default => "Order Update ({$this->order->order_number}) — Kohan Mart",
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.order-status');
    }
}