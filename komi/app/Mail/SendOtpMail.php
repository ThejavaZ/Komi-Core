<?php
// app/Mail/SendOtpMail.php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $otp;

    public function __construct($otp)
    {
        $this->otp = $otp; // Recibimos el código dinámico
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu código de verificación de Komi',
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: "<h2>Tu código de verificación es: {$this->otp}</h2>",
        );
    }
}
