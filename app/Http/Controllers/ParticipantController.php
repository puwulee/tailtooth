<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Question;
use App\Models\Vote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ParticipantController extends Controller
{
    /** 觀眾端的活動提問牆。 */
    public function show(Event $event)
    {
        abort_if($event->status === 'closed' && $event->questions()->published()->count() === 0, 404);

        return view('events.show', ['event' => $event]);
    }

    /**
     * 提問牆 JSON（前端輪詢即時更新用）。
     * sort=top（讚數優先）或 recent（最新）。
     */
    public function questions(Request $request, Event $event)
    {
        $token = (string) $request->query('token', '');
        $sort = $request->query('sort') === 'recent' ? 'recent' : 'top';

        $query = $event->questions()->published();

        if ($sort === 'top') {
            $query->orderByDesc('pinned')->orderByDesc('upvotes_count')->orderByDesc('id');
        } else {
            $query->orderByDesc('pinned')->orderByDesc('id');
        }

        // 此瀏覽器已投過讚的題目
        $myVotes = $token === ''
            ? []
            : Vote::whereIn('question_id', $event->questions()->pluck('id'))
                ->where('voter_token', $token)
                ->pluck('question_id')
                ->all();
        $myVotes = array_flip($myVotes);

        $questions = $query->get()->map(fn (Question $q) => [
            'id' => $q->id,
            'body' => $q->body,
            'author' => $q->displayName(),
            'mine' => $token !== '' && $q->author_token === $token,
            'upvotes' => $q->upvotes_count,
            'voted' => isset($myVotes[$q->id]),
            'pinned' => $q->pinned,
            'answer' => $q->answer,
            'answered' => $q->isAnswered(),
            'created_at' => $q->created_at->toIso8601String(),
        ]);

        return response()->json([
            'event' => [
                'title' => $event->title,
                'status' => $event->status,
            ],
            'questions' => $questions,
        ]);
    }

    /** 送出提問。 */
    public function storeQuestion(Request $request, Event $event)
    {
        abort_unless($event->isOpen(), 403, '此活動已關閉提問。');

        $data = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:1000'],
            'author_name' => ['nullable', 'string', 'max:60'],
            'token' => ['required', 'string', 'max:64'],
        ]);

        $name = $event->allow_anonymous ? ($data['author_name'] ?? null) : ($data['author_name'] ?: null);

        $question = $event->questions()->create([
            'body' => trim($data['body']),
            'author_name' => $name ? trim($name) : null,
            'author_token' => $data['token'],
            'status' => $event->require_approval ? 'pending' : 'published',
        ]);

        return response()->json([
            'ok' => true,
            'pending' => $event->require_approval,
            'id' => $question->id,
        ], 201);
    }

    /** 對某題按讚（每瀏覽器限一次，可取消）。 */
    public function vote(Request $request, Event $event, Question $question)
    {
        abort_unless($question->event_id === $event->id, 404);

        $data = $request->validate([
            'token' => ['required', 'string', 'max:64'],
        ]);

        $token = $data['token'];

        $voted = DB::transaction(function () use ($question, $token) {
            $existing = Vote::where('question_id', $question->id)
                ->where('voter_token', $token)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                $existing->delete();
                $question->decrement('upvotes_count');

                return false;
            }

            Vote::create(['question_id' => $question->id, 'voter_token' => $token]);
            $question->increment('upvotes_count');

            return true;
        });

        return response()->json([
            'voted' => $voted,
            'upvotes' => $question->fresh()->upvotes_count,
        ]);
    }
}
