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
请对以下邮件进行分析和总结，用中文输出：

**发件人**：{$message->from_display}
**主题**：{$message->subject}
**时间**：{$message->sent_at?->format('Y-m-d H:i')}

**邮件正文**：
{$body}

请提供：
1. **核心内容摘要**（2-3句话概括邮件主要内容）
2. **关键信息提取**（列出重要日期、金额、联系方式、行动项等）
3. **建议回复方向**（如需要回复，给出简洁的回复建议）
PROMPT;

        try {
            $response = Http::withToken($this->apiKey)
                ->baseUrl($this->baseUrl)
                ->timeout(60)
                ->post('/chat/completions', [
                    'model'       => $this->model,
                    'messages'    => [
                        [
                            'role'    => 'system',
                            'content' => '你是一个专业的邮件助理，擅长分析商务邮件并用清晰的中文进行总结。',
                        ],
                        [
                            'role'    => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'max_tokens'  => 1000,
                    'temperature' => 0.3,
                ]);

            if (!$response->successful()) {
                Log::error('DeepSeek API error: ' . $response->body());
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
