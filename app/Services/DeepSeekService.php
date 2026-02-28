<?php

namespace App\Services;

use App\Models\EmailMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeepSeekService
{
    private string $apiKey;
    private string $baseUrl;
    private string $model;

    public function __construct()
    {
        $this->apiKey  = config('services.deepseek.api_key', '');
        $this->baseUrl = config('services.deepseek.base_url', 'https://api.deepseek.com/v1');
        $this->model   = config('services.deepseek.model', 'deepseek-chat');
    }

    /**
     * Summarize an email message using DeepSeek.
     * Saves the result back to the message record.
     */
    public function summarizeEmail(EmailMessage $message): string
    {
        $body = strip_tags($message->body_html ?? $message->body_text ?? '');
        $body = mb_substr($body, 0, 8000); // Limit to avoid token overflow

        $prompt = <<<PROMPT
请用1-2句中文简明概括以下邮件的核心内容，不要分条列举，不要废话：

发件人：{$message->from_display}
主题：{$message->subject}
正文：{$body}
PROMPT;

        Log::info("[DeepSeek] Calling API model={$this->model} message_id={$message->id}");

        try {
            $response = Http::withToken($this->apiKey)
                ->baseUrl($this->baseUrl)
                ->timeout(60)
                ->post('/chat/completions', [
                    'model'       => $this->model,
                    'messages'    => [
                        [
                            'role'    => 'system',
                            'content' => '你是邮件摘要助手，只输出1-2句中文摘要，不分条，不废话。',
                        ],
                        [
                            'role'    => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'max_tokens'  => 150,
                    'temperature' => 0.3,
                ]);

            Log::info("[DeepSeek] API responded status=" . $response->status());

            if (!$response->successful()) {
                Log::error('[DeepSeek] API error: ' . $response->body());
                throw new \RuntimeException('AI service returned an error: ' . $response->status());
            }

            $summary = $response->json('choices.0.message.content', '');

            $message->update([
                'ai_summary'        => $summary,
                'ai_summarized_at'  => now(),
            ]);

            return $summary;

        } catch (\Exception $e) {
            Log::error('DeepSeek summarize failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate a reply draft for an email.
     */
    public function draftReply(EmailMessage $message, string $userInstruction = ''): string
    {
        $body = strip_tags($message->body_html ?? $message->body_text ?? '');
        $body = mb_substr($body, 0, 4000);

        $instruction = $userInstruction ?: '请用专业、简洁的语气撰写一封回复邮件';

        $prompt = <<<PROMPT
原始邮件：
发件人：{$message->from_display}
主题：{$message->subject}
内容：{$body}

任务：{$instruction}

请直接输出邮件正文内容，不要包含收件人、主题等邮件头信息。
PROMPT;

        $response = Http::withToken($this->apiKey)
            ->baseUrl($this->baseUrl)
            ->timeout(60)
            ->post('/chat/completions', [
                'model'       => $this->model,
                'messages'    => [
                    [
                        'role'    => 'system',
                        'content' => '你是一个专业的商务邮件撰写助理，能够根据上下文撰写合适的回复邮件。',
                    ],
                    [
                        'role'    => 'user',
                        'content' => $prompt,
                    ],
                ],
                'max_tokens'  => 1500,
                'temperature' => 0.7,
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException('AI service error: ' . $response->status());
        }

        return $response->json('choices.0.message.content', '');
    }
}
