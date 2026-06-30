<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    /** 主辦者的活動列表。 */
    public function index(Request $request)
    {
        $events = $request->user()->events()
            ->withCount(['questions', 'questions as pending_count' => fn ($q) => $q->where('status', 'pending')])
            ->latest()
            ->get();

        return view('admin.events.index', ['events' => $events]);
    }

    /** 新增活動表單。 */
    public function create()
    {
        return view('admin.events.create');
    }

    /** 儲存新活動。 */
    public function store(Request $request)
    {
        $data = $this->validateEvent($request);

        $event = $request->user()->events()->create($data);

        return redirect()->route('admin.events.show', $event)
            ->with('status', "活動「{$event->title}」已建立，加入代碼：{$event->code}");
    }

    /** 後台活動管理（主持人視角，可審核/回答）。 */
    public function show(Request $request, Event $event)
    {
        $this->authorizeEvent($request, $event);

        $event->load(['questions' => fn ($q) => $q
            ->orderByDesc('pinned')
            ->orderByDesc('upvotes_count')
            ->orderByDesc('id'),
        ]);

        return view('admin.events.show', ['event' => $event]);
    }

    /** 編輯活動。 */
    public function edit(Request $request, Event $event)
    {
        $this->authorizeEvent($request, $event);

        return view('admin.events.edit', ['event' => $event]);
    }

    /** 更新活動設定。 */
    public function update(Request $request, Event $event)
    {
        $this->authorizeEvent($request, $event);

        $event->update($this->validateEvent($request));

        return redirect()->route('admin.events.show', $event)->with('status', '活動設定已更新。');
    }

    /** 開啟/關閉提問。 */
    public function toggleStatus(Request $request, Event $event)
    {
        $this->authorizeEvent($request, $event);

        $event->update(['status' => $event->isOpen() ? 'closed' : 'open']);

        return back()->with('status', $event->isOpen() ? '已開啟提問。' : '已關閉提問。');
    }

    /** 刪除活動。 */
    public function destroy(Request $request, Event $event)
    {
        $this->authorizeEvent($request, $event);

        $title = $event->title;
        $event->delete();

        return redirect()->route('admin.events.index')->with('status', "活動「{$title}」已刪除。");
    }

    private function validateEvent(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'require_approval' => ['nullable', 'boolean'],
            'allow_anonymous' => ['nullable', 'boolean'],
            'require_company' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'],
        ]);

        // checkbox 未勾選時不會出現在請求中，明確轉成布林。
        $data['require_approval'] = $request->boolean('require_approval');
        $data['allow_anonymous'] = $request->boolean('allow_anonymous');
        $data['require_company'] = $request->boolean('require_company');

        return $data;
    }

    private function authorizeEvent(Request $request, Event $event): void
    {
        abort_unless($event->user_id === $request->user()->id, 403);
    }
}
