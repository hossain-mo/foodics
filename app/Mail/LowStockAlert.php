<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Ingredient;

class LowStockAlert extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $ingredient;

    public function __construct(Ingredient $ingredient)
    {
        $this->ingredient = $ingredient;
    }

    public function build()
    {
        return $this->view('emails.low_stock_alert')
        ->with([
            'ingredientName' => $this->ingredient->name,
            'remainingStock' => $this->ingredient->remaining,
        ]);
    }

    
    public function attachments(): array
    {
        return [];
    }
}
