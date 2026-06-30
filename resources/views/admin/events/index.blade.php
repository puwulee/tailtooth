@extends('layouts.app')

@section('title', '我的活動 · '.config('app.name'))
@section('container-class', 'wide')

@section('content')
    <div class="spread" style="margin-bottom:1rem;">
        <h1>我的活動</h1>
        <a class="btn" href="{{ route('admin.events.create') }}">＋ 建立活動</a>
    </div>

    @if ($events->isEmpty())
        <div class="panel"><div class="empty">還沒有活動，點右上角「建立活動」開始吧。</div></div>
    @else
        <div class="panel">
            <table>
                <thead>
                    <tr><th>活動</th><th>代碼</th><th>狀態</th><th>提問</th><th></th></tr>
                </thead>
                <tbody>
                @foreach ($events as $event)
                    <tr>
                        <td>
                            <a href="{{ route('admin.events.show', $event) }}"><strong>{{ $event->title }}</strong></a>
                        </td>
                        <td><span class="code-badge">{{ $event->code }}</span></td>
                        <td><span class="pill {{ $event->status }}">{{ $event->isOpen() ? '開放中' : '已關閉' }}</span></td>
                        <td>
                            {{ $event->questions_count }}
                            @if ($event->pending_count) <span class="pill pending">{{ $event->pending_count }} 待審</span> @endif
                        </td>
                        <td><a href="{{ route('admin.events.show', $event) }}">管理 →</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
