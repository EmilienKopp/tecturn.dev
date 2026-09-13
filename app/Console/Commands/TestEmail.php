<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Throwaway command to verify mail delivery. Safe to delete.
 */
#[Signature('test:email {to=emilien.kopp@gmail.com : Recipient email address}')]
#[Description('Send a throwaway test email to verify mail configuration')]
class TestEmail extends Command
{
    public function handle(): int
    {
        $to = $this->argument('to');

        $this->info("Sending test email to {$to} via mailer [".config('mail.default').']...');

        try {
            Mail::raw('This is a test email from Tecturn sent at '.now()->toDateTimeString().'.', function ($message) use ($to) {
                $message->to($to)->subject('Tecturn test email');
            });
        } catch (\Throwable $e) {
            $this->error('Failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Sent. Check the inbox (or your mail log).');

        return self::SUCCESS;
    }
}
