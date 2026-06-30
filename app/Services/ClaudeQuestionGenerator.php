<?php

namespace App\Services;

use Anthropic\Client;
use App\Contracts\QuestionGenerator;
use App\Models\Event;
use RuntimeException;

/**
 * 以 Claude（claude-opus-4-8）依活動主題生成提問清單。
 * 使用結構化輸出（output_config.format）確保回傳可解析的 JSON。
 */
class ClaudeQuestionGenerator implements QuestionGenerator
{
    public function __construct(private Client $client) {}

    public function generate(Event $event, int $count = 10): array
    {
        $topic = trim((string) $event->topic);
        if ($topic === '') {
            throw new RuntimeException('活動尚未設定主題，無法生成題目。');
        }

        $speaker = $event->speaker ? "主講人：{$event->speaker}。" : '';
        $date = $event->event_date ? "日期：{$event->event_date->toDateString()}。" : '';

        $prompt = <<<PROMPT
            你是活動問答主持人。請依下列活動資訊，生成 {$count} 個適合現場觀眾票選、提供給主講人回答的問題。
            問題要具體、口語、與主題高度相關，避免重複、避免是非題，每題不超過 60 字。

            活動名稱：{$event->title}
            活動主題：{$topic}
            {$speaker}{$date}

            只輸出題目清單，數量為 {$count} 題。
            PROMPT;

        $schema = [
            'type' => 'object',
            'properties' => [
                'questions' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
            ],
            'required' => ['questions'],
            'additionalProperties' => false,
        ];

        $message = $this->client->messages->create(
            maxTokens: 2048,
            model: 'claude-opus-4-8',
            messages: [['role' => 'user', 'content' => $prompt]],
            outputConfig: ['format' => ['type' => 'json_schema', 'schema' => $schema]],
        );

        return $this->extractQuestions($message, $count);
    }

    private function extractQuestions(object $message, int $count): array
    {
        $text = '';
        foreach ($message->content as $block) {
            if (($block->type ?? null) === 'text') {
                $text = $block->text;
                break;
            }
        }

        $data = json_decode($text, true);
        $questions = $data['questions'] ?? null;

        if (! is_array($questions)) {
            throw new RuntimeException('AI 回傳格式無法解析。');
        }

        // 清理、去空白、去重，取前 N 題
        $questions = array_values(array_filter(array_map(
            fn ($q) => trim((string) $q),
            $questions,
        )));
        $questions = array_values(array_unique($questions));

        return array_slice($questions, 0, $count);
    }
}
