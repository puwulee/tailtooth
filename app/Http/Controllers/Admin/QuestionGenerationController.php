<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\QuestionGenerator;
use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class QuestionGenerationController extends Controller
{
    /**
     * 依活動主題用 AI 生成題目（每場 10 題）。
     * 會先清除既有的 AI 題再重新生成。
     */
    public function generate(Request $request, Event $event, QuestionGenerator $generator)
    {
        abort_unless($event->user_id === $request->user()->id, 403);

        if (! $event->topic) {
            return back()->withErrors(['topic' => '請先設定活動主題（編輯活動）才能產生題目。']);
        }

        try {
            $questions = $generator->generate($event, 10);
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors(['generate' => 'AI 產生題目失敗：'.$e->getMessage()]);
        }

        if (empty($questions)) {
            return back()->withErrors(['generate' => 'AI 沒有回傳任何題目，請再試一次。']);
        }

        // 取代既有 AI 題（觀眾提問保留）
        $event->questions()->where('source', 'ai')->delete();

        foreach ($questions as $body) {
            $event->questions()->create([
                'source' => 'ai',
                'body' => $body,
                'author_token' => Str::random(32),
                'status' => 'published',
            ]);
        }

        return back()->with('status', '已用 AI 產生 '.count($questions).' 題。');
    }
}
