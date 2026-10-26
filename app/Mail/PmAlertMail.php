<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class PmAlertMail extends Mailable {
    public function __construct(
        public readonly string $subjectLine,
        public readonly string $bodyText,
        public readonly string $actionUrl,
    ) {}
    public function build():static {
        return $this->subject('[SIGIT PM] '.$this->subjectLine)
            ->text('emails.pm-alert')
            ->with(['bodyText'=>$this->bodyText,'actionUrl'=>$this->actionUrl]);
    }
}