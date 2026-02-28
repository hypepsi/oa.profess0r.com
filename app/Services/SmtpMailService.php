<?php

namespace App\Services;

use App\Models\EmailAccount;
use App\Models\EmailMessage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;

class SmtpMailService
{
    /**
     * Send an email using the given account's SMTP credentials.
     */
    public function send(
        EmailAccount $account,
        string $to,
        string $subject,
        string $body,
        ?string $replyToMessageId = null
    ): EmailMessage {
        // Temporarily override mailer config with this account's SMTP settings
        config([
            'mail.mailers.smtp.host'       => $account->smtp_host,
            'mail.mailers.smtp.port'       => $account->smtp_port,
            'mail.mailers.smtp.encryption' => $account->smtp_encryption,
            'mail.mailers.smtp.username'   => $account->email,
            'mail.mailers.smtp.password'   => $account->getPassword(),
            'mail.from.address'            => $account->email,
            'mail.from.name'               => $account->name,
        ]);

        // Resolve the mailer with fresh config
        $mailer = app('mail.manager')->mailer('smtp');

        $mailer->html($body, function (Message $msg) use ($to, $subject, $account) {
            $msg->to($to)
                ->subject($subject)
                ->from($account->email, $account->name);
        });

        // Record the sent message in DB
        return EmailMessage::create([
            'email_account_id' => $account->id,
            'folder'           => 'Sent',
            'subject'          => $subject,
            'from_name'        => $account->name,
            'from_email'       => $account->email,
            'to_addresses'     => [['name' => null, 'email' => $to]],
            'body_html'        => $body,
            'direction'        => 'outbound',
            'is_read'          => true,
            'sent_at'          => now(),
        ]);
    }
}
