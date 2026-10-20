<?php

namespace App\Mail;

use App\Models\Depot;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MissingEntryNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public Depot $depot;
    public string $date;

    public function __construct(Depot $depot, string $date)
    {
        $this->depot = $depot;
        $this->date = $date;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[CRITICAL ALERT] Missing Daily Expense Log - {$this->depot->name} Depot ({$this->date})",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.missing_entry',
        );
    }
}
