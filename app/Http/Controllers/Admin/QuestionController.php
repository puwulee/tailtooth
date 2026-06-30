<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Question;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    /** 回答提問。 */
    public function answer(Request $request, Event $event, Question $question)
    {
        $this->authorize($request, $event, $question);

        $data = $request->validate([
            'answer' => ['required', 'string', 'max:2000'],
        ]);

        $question->update([
            'answer' => trim($data['answer']),
            'answered_at' => now(),
            'answered_by' => $request->user()->id,
            'status' => 'published',
        ]);

        return back()->with('status', '已回覆。');
    }

    /** 審核通過（上牆）。 */
    public function approve(Request $request, Event $event, Question $question)
    {
        $this->authorize($request, $event, $question);

        $question->update(['status' => 'published']);

        return back()->with('status', '已核准上牆。');
    }

    /** 置頂/取消置頂。 */
    public function togglePin(Request $request, Event $event, Question $question)
    {
        $this->authorize($request, $event, $question);

        $question->update(['pinned' => ! $question->pinned]);

        return back();
    }

    /** 封存（隱藏）。 */
    public function archive(Request $request, Event $event, Question $question)
    {
        $this->authorize($request, $event, $question);

        $question->update(['status' => 'archived', 'pinned' => false]);

        return back()->with('status', '已封存。');
    }

    /** 刪除提問。 */
    public function destroy(Request $request, Event $event, Question $question)
    {
        $this->authorize($request, $event, $question);

        $question->delete();

        return back()->with('status', '提問已刪除。');
    }

    private function authorize(Request $request, Event $event, Question $question): void
    {
        abort_unless($event->user_id === $request->user()->id, 403);
        abort_unless($question->event_id === $event->id, 404);
    }
}
