<?php

namespace App\Services;

use App\Models\EmailAccount;
use App\Models\EmailAttachment;
use App\Models\EmailMessage;
use Carbon\Carbon;
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
                'sequence'      => \Webklex\PHPIMAP\IMAP::ST_UID,
                'fetch_order'   => 'desc',
                'open_timeout'  => 30,
                'read_timeout'  => 30,
                'write_timeout' => 30,
            ],
        ]);
    }

    /**
     * Sync new messages for an account.
     * Returns the number of new messages imported.
     */
    public function syncAccount(EmailAccount $account, int $limit = 100): int
    {
        $tag = "[IMAP:{$account->email}]";
        Log::info("{$tag} Starting sync (limit={$limit})");

        try {
            $client = $this->makeClient($account);
            $client->connect();
            Log::info("{$tag} Connected to {$account->imap_host}:{$account->imap_port}");

            $inbox = $client->getFolder('INBOX');

            // ── FIX: must call ->all() (or another criteria method) before ->limit()
            // Without a criteria, webklex sends "UID SEARCH" with no args → server rejects.
            $messages = $inbox->messages()
                ->all()          // fetch all UIDs (filters by already-imported handled below)
                ->leaveUnread()  // do not mark as \Seen on the server
                ->setFetchOrder('desc')
                ->limit($limit, 1)
                ->get();

            Log::info("{$tag} Fetched " . $messages->count() . " messages from server");

            $imported = 0;
            foreach ($messages as $message) {
                if ($this->importMessage($account, $message, 'INBOX')) {
                    $imported++;
                }
            }

            $client->disconnect();
            Log::info("{$tag} Sync complete — imported {$imported} new messages");
            return $imported;

        } catch (\Exception $e) {
            Log::error("{$tag} Sync FAILED: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Test IMAP connection — returns true on success, throws on failure.
     */
    public function testConnection(EmailAccount $account): bool
    {
        $tag = "[IMAP:{$account->email}]";
        Log::info("{$tag} Testing connection to {$account->imap_host}:{$account->imap_port}");

        $client = $this->makeClient($account);
        $client->connect();

        $folders = $client->getFolders();
        Log::info("{$tag} Connection OK — found " . $folders->count() . " folders");

        $client->disconnect();
        return true;
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    private function makeClient(EmailAccount $account): \Webklex\PHPIMAP\Client
    {
        return $this->clientManager->make([
            'host'          => $account->imap_host,
            'port'          => $account->imap_port,
            'encryption'    => $account->imap_encryption,
            'validate_cert' => false,
            'username'      => $account->email,
            'password'      => $account->getPassword(),
            'protocol'      => 'imap',
        ]);
    }

    private function importMessage(EmailAccount $account, $message, string $folder): bool
    {
        $uid = (string) $message->uid;
        $tag = "[IMAP:{$account->email}]";

        // Skip if already imported
        if (EmailMessage::where('email_account_id', $account->id)
            ->where('uid', $uid)
            ->where('folder', $folder)
            ->exists()) {
            return false;
        }

        Log::debug("{$tag} Importing UID={$uid} folder={$folder}");

        try {
            $from      = $message->getFrom();
            $fromName  = $from[0]?->personal ?? null;
            $fromEmail = $from[0]?->mail ?? null;
            // Decode RFC 2047 MIME encoded sender name
            if ($fromName && str_contains($fromName, '=?')) {
                $fromName = mb_decode_mimeheader($fromName);
            }

            $toAddresses = $this->parseAddresses($message->getTo());
            $ccAddresses = $this->parseAddresses($message->getCc());

            $bodyHtml       = $message->getHTMLBody();
            $bodyText       = $message->getTextBody();
            $attachments    = $message->getAttachments();
            $hasAttachments = $attachments->count() > 0;

            $subject = $message->getSubject();
            // subject can be an object with a toString
            if (is_object($subject) && method_exists($subject, '__toString')) {
                $subject = (string) $subject;
            }
            // Decode RFC 2047 MIME encoded-words (e.g. =?UTF-8?B?...?=)
            if ($subject && str_contains($subject, '=?')) {
                $subject = mb_decode_mimeheader($subject);
            }

            $record = EmailMessage::create([
                'email_account_id' => $account->id,
                'message_id'       => (string) ($message->message_id ?? ''),
                'uid'              => $uid,
                'folder'           => $folder,
                'subject'          => $subject ?: '(No Subject)',
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
                'sent_at'          => $this->parseDate($message->getDate()),
            ]);

            // Store attachments to local disk
            foreach ($attachments as $att) {
                try {
                    $filename = $att->getName() ?? 'attachment';
                    $path     = "email-attachments/{$account->id}/{$record->id}/{$filename}";
                    $content  = $att->getContent();

                    Storage::disk('local')->put($path, $content);

                    EmailAttachment::create([
                        'email_message_id' => $record->id,
                        'filename'         => $filename,
                        'mime_type'        => $att->getMimeType(),
                        'size'             => strlen($content),
                        'disk'             => 'local',
                        'path'             => $path,
                    ]);

                    Log::debug("{$tag} Stored attachment: {$filename}");
                } catch (\Exception $e) {
                    Log::warning("{$tag} Failed to store attachment for message {$record->id}: " . $e->getMessage());
                }
            }

            return true;

        } catch (\Exception $e) {
            Log::error("{$tag} Failed to import UID={$uid}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Parse a webklex date Attribute into a Carbon instance.
     * Webklex returns dates as Attribute objects; we cast to string then parse.
     */
    private function parseDate($dateAttr): ?Carbon
    {
        if (!$dateAttr) return null;
        try {
            // Attribute may be a collection; grab first value or cast to string
            $val = method_exists($dateAttr, 'first') ? $dateAttr->first() : $dateAttr;
            if ($val instanceof \DateTime || $val instanceof Carbon) {
                return Carbon::instance($val);
            }
            return Carbon::parse((string) $val);
        } catch (\Exception $e) {
            Log::warning('Could not parse email date: ' . (string) $dateAttr . ' — ' . $e->getMessage());
            return null;
        }
    }

    private function parseAddresses($addresses): array
    {
        if (!$addresses) return [];
        $result = [];
        foreach ((array) $addresses as $addr) {
            if (!$addr) continue;
            $name = $addr->personal ?? null;
            // Decode RFC 2047 MIME encoded display names
            if ($name && str_contains($name, '=?')) {
                $name = mb_decode_mimeheader($name);
            }
            $result[] = [
                'name'  => $name,
                'email' => $addr->mail ?? null,
            ];
        }
        return $result;
    }
}
