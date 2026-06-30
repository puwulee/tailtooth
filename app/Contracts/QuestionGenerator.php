<?php

namespace App\Contracts;

use App\Models\Event;

interface QuestionGenerator
{
    /**
     * 依活動主題生成題目清單。
     *
     * @return array<int, string> 題目字串陣列
     */
    public function generate(Event $event, int $count = 10): array;
}
