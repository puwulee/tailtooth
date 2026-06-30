@extends('layouts.app')

@section('title', $event->title.' · 管理 · '.config('app.name'))
@section('container-class', 'wide')

@php
    $pending = $event->questions->where('status', 'pending');
    $published = $event->questions->where('status', 'published');
    $aiCount = $event->questions->where('source', 'ai')->count();
    $shareUrl = route('events.show', $event);
@endphp

@section('content')
    <p><a href="{{ route('admin.events.index') }}">← 回我的活動</a></p>

    <div class="panel">
        <div class="spread">
            <div>
                <h1 style="margin-bottom:.4rem;">{{ $event->title }}</h1>
                <span class="pill {{ $event->status }}">{{ $event->isOpen() ? '提問開放中' : '提問已關閉' }}</span>
                @if ($event->require_approval) <span class="pill">需審核</span> @endif
                @if (! $event->allow_anonymous) <span class="pill">禁止匿名</span> @endif
                @if ($event->company_identity) <span class="pill">統編公司身分</span> @endif
                <div class="muted" style="margin-top:.5rem; font-size:.9rem;">
                    📅 {{ optional($event->event_date)->toDateString() ?? '—' }}
                    · 🎤 {{ $event->speaker ?? '—' }}
                    · 主題：{{ $event->topic ?? '—' }}
                </div>
            </div>
            <div class="row">
                <a class="btn secondary sm" href="{{ route('admin.events.edit', $event) }}">編輯設定</a>
                <form method="POST" action="{{ route('admin.events.toggle', $event) }}" class="inline-form">
                    @csrf
                    <button class="btn secondary sm" type="submit">{{ $event->isOpen() ? '關閉提問' : '開放提問' }}</button>
                </form>
            </div>
        </div>

        <div class="row" style="margin-top:1rem; gap:1rem;">
            <div>
                <div class="muted" style="font-size:.8rem;">加入代碼</div>
                <span class="code-badge">{{ $event->code }}</span>
            </div>
            <div style="flex:1; min-width:220px;">
                <div class="muted" style="font-size:.8rem;">觀眾連結</div>
                <input type="text" readonly value="{{ $shareUrl }}" onclick="this.select()">
            </div>
            <a class="btn secondary sm" href="{{ $shareUrl }}" target="_blank" rel="noopener">開啟觀眾頁 ↗</a>
        </div>
    </div>

    <div class="panel">
        <div class="spread">
            <div>
                <h2 style="margin:0;">AI 產生題目</h2>
                <p class="muted" style="margin:.3rem 0 0;">
                    依主題用 AI 產生 10 題（會取代現有 {{ $aiCount }} 題 AI 題目，觀眾提問不受影響）。
                </p>
            </div>
            <form method="POST" action="{{ route('admin.events.generate', $event) }}" class="inline-form">
                @csrf
                <button class="btn" type="submit">🤖 產生 10 題</button>
            </form>
        </div>
        @error('generate')<div class="alert err" style="margin-top:.8rem;">{{ $message }}</div>@enderror
        @error('topic')<div class="alert err" style="margin-top:.8rem;">{{ $message }}</div>@enderror
    </div>

    @if ($pending->isNotEmpty())
    <div class="panel">
        <h2>待審核 <span class="pill pending">{{ $pending->count() }}</span></h2>
        @foreach ($pending as $q)
            <div class="qcard">
                <div class="qbody">{{ $q->body }}</div>
                <div class="meta">{{ $q->displayName() }} · {{ $q->created_at->diffForHumans() }}</div>
                <div class="row" style="margin-top:.6rem;">
                    <form method="POST" action="{{ route('admin.questions.approve', [$event, $q]) }}" class="inline-form">
                        @csrf <button class="btn sm" type="submit">核准上牆</button>
                    </form>
                    <form method="POST" action="{{ route('admin.questions.destroy', [$event, $q]) }}" class="inline-form"
                          onsubmit="return confirm('刪除這則提問？');">
                        @csrf @method('DELETE') <button class="btn ghost sm" type="submit">刪除</button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
    @endif

    <div class="panel">
        <h2>提問牆 <span class="pill">{{ $published->count() }}</span></h2>
        @if ($published->isEmpty())
            <div class="empty">目前沒有公開的提問。</div>
        @else
            @foreach ($published as $q)
                <div class="qcard {{ $q->pinned ? 'pinned' : '' }}">
                    <div class="qrow">
                        <div class="vote {{ $q->upvotes_count ? 'voted' : '' }}" style="cursor:default;">
                            <span class="n">{{ $q->upvotes_count }}</span><span class="t">讚</span>
                        </div>
                        <div class="grow">
                            <div class="qbody">{{ $q->body }}</div>
                            <div class="meta">
                                {{ $q->pinned ? '📌 置頂 · ' : '' }}{{ $q->isAi() ? '🤖 ' : '' }}{{ $q->displayName() }} ·
                                {{ $q->created_at->diffForHumans() }}
                                @if ($q->isAnswered()) · ✅ 已回覆 @endif
                            </div>

                            @if ($q->isAnswered())
                                <div class="answer"><span class="lbl">主辦回覆</span><div>{{ $q->answer }}</div></div>
                            @endif

                            <details style="margin-top:.6rem;">
                                <summary class="btn ghost sm" style="display:inline-block;">
                                    {{ $q->isAnswered() ? '編輯回覆' : '回覆' }}
                                </summary>
                                <form method="POST" action="{{ route('admin.questions.answer', [$event, $q]) }}" style="margin-top:.5rem;">
                                    @csrf
                                    <textarea name="answer" maxlength="2000" placeholder="輸入回覆…" required>{{ $q->answer }}</textarea>
                                    <button class="btn sm" type="submit" style="margin-top:.4rem;">送出回覆</button>
                                </form>
                            </details>

                            <div class="row" style="margin-top:.6rem;">
                                <form method="POST" action="{{ route('admin.questions.pin', [$event, $q]) }}" class="inline-form">
                                    @csrf <button class="btn ghost sm" type="submit">{{ $q->pinned ? '取消置頂' : '置頂' }}</button>
                                </form>
                                <form method="POST" action="{{ route('admin.questions.archive', [$event, $q]) }}" class="inline-form">
                                    @csrf <button class="btn ghost sm" type="submit">封存</button>
                                </form>
                                <form method="POST" action="{{ route('admin.questions.destroy', [$event, $q]) }}" class="inline-form"
                                      onsubmit="return confirm('刪除這則提問？');">
                                    @csrf @method('DELETE') <button class="btn ghost sm" type="submit">刪除</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
@endsection
