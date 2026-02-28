<?php

namespace App\Services;

use App\Models\EmailAccount;
use App\Models\EmailAttachment;
use App\Models\EmailMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Webklex\PHPIMAP\ClientManager;

class ImapService
{
    private ClientManager $clientManager;

    public function __construct()
    {
        $this->clientManager = new ClientManager([
            'options' => [
                'sequence' => \Webklex\PHPIMAP\IMAP::ST_UID,
                'fetch_order' => 'desc',
                'open_timeout' => 30,
                'read_timeout' => 30,
                'write_timeout' => 30,
            ],
        ]);
    }

    /**
     * Sync new messages for an account.
     * Returns the number of new messages imported.
     */
    public function syncAccount(EmailAccount $account, int $limit = 50): int
    {
        try {
            $client = $this->clientManager->make([
                'host'          => $account->imap_host,
                'port'          => $account->imap_port,
                'encryption'    => $account->imap_encryption,
                'validate_cert' => false,
                'username'      => $account->email,
                'password'      => $account->getPassword(),
                'protocol'      => 'imap',
            ]);

            $client->connect();

            $inbox = $client->getFolder('INBOX');
            $messages = $inbox->messages()
                ->leaveUnread()
                ->limit($limit)
                ->get();

            $imported = 0;
            foreach ($messages as $message) {
                if ($this->importMessage($account, $message, 'INBOX')) {
                    $imported++;
                }
            }

            $client->disconnect();
            return $imported;

        } catch (\Exception $e) {
            Log::error("IMAP sync failed for {$account->email}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Test IMAP connection — returns true on success, throws on failure.
     */
    public function testConnection(EmailAccount $account): bool
    {
        $client = $this->clientManager->make([
            'host'          => $account->imap_host,
            'port'          => $account->imap_port,
            'encryption'    => $account->imap_encryption,
            'validate_cert' => false,
            'username'      => $account->email,
            'password'      => $account->getPassword(),
            'protocol'      => 'imap',
        ]);

        $client->connect();
        $client->disconnect();
        return true;
    }

    // -------------------------------------------------------------------------
    // Internal
    // -------------------------------------------------------------------------

    private function importMessage(EmailAccount $account, $message, string $folder): bool
    {
        $uid = (string) $message->uid;

        // Skip if already exists
        if (EmailMessage::where('email_account_id', $account->id)
            ->where('uid', $uid)
            ->where('folder', $folder)
            ->exists()) {
            return false;
        }

        $from       = $message->getFrom();
        $fromName   = $from[0]?->personal ?? null;
        $fromEmail  = $from[0]?->mail ?? null;

        $toAddresses  = $this->parseAddresses($message->getTo());
        $ccAddresses  = $this->parseAddresses($message->getCc());

        $bodyHtml = $message->getHTMLBody();
        $bodyText = $message->getTextBody();

        // Get attachments
        $attachments = $message->getAttachments();
        $hasAttachments = $attachments->count() > 0;

        $record = EmailMessage::create([
            'email_account_id' => $account->id,
            'message_id'       => (string) ($message->message_id ?? ''),
            'uid'              => $uid,
            'folder'           => $folder,
            'subject'          => $message->getSubject() ?? '(No Subject)',
            'from_name'        => $fromName,
            'from_email'       => $fromEmail,
            'to_addresses'     => $toAddresses,
            'cc_addresses'     => $ccAddresses,
            'bcc_addresses'    => [],
            'body_html'        => $bodyHtml,
            'body_text'        => $bodyText,
            'is_read'          => $message->getFlags()->has('seen'),
            'has_attachments'  => $hasAttachments,
            'direction'        => 'inbound',
            'sent_at'          => $message->getDate()?->toDateTime(),
        ]);

        // Store attachments
        foreach ($attachments as $att) {
            try {
                $filename = $att->getName() ?? 'attachment';
                $path = "email-attachments/{$account->id}/{$record->id}/{$filename}";
                Storage::disk('local')->put($path, $att->getContent());

                EmailAttachment::create([
                    'email_message_id' => $record->id,
                    'filename'         => $filename,
                    'mime_type'        => $att->getMimeType(),
                    'size'             => strlen($att->getContent()),
                    'disk'             => 'local',
                    'path'             => $path,
                ]);
            } catch (\Exception $e) {
                Log::warning("Failed to store attachment for message {$record->id}: " . $e->getMessage());
            }
        }

        return true;
    }

    private function parseAddresses($addresses): array
    {
        if (!$addresses) return [];
        $result = [];
        foreach ($addresses as $addr) {
            $result[] = [
                'name'  => $addr->personal ?? null,
                'email' => $addr->mail ?? null,
            ];
        }
        return $result;
    }
}
