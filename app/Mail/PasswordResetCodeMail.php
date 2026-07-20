<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $code;

    public int $minutes;

    public function __construct(string $code, int $minutes = 10)
    {
        $this->code = $code;
        $this->minutes = $minutes;
    }

    public function build()
    {
        return $this->subject(__('auth.password_reset_code_subject'))
            ->view('emails.password_reset_code')
            ->with([
                'code' => $this->code,
                'minutes' => $this->minutes,
            ]);
    }
}
