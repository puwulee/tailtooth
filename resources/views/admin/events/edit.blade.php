@extends('layouts.app')

@section('title', '編輯活動 · '.config('app.name'))

@section('content')
    <p><a href="{{ route('admin.events.show', $event) }}">← 回活動管理</a></p>
    <div class="panel">
        <h1>編輯活動</h1>
        <form method="POST" action="{{ route('admin.events.update', $event) }}">
            @csrf
            @method('PUT')
            @include('admin.events._form')
            <div class="spread" style="margin-top:1.2rem;">
                <button class="btn" type="submit">儲存變更</button>
            </div>
        </form>
    </div>

    <div class="panel">
        <h2>危險操作</h2>
        <form method="POST" action="{{ route('admin.events.destroy', $event) }}"
              onsubmit="return confirm('確定刪除此活動？所有提問將一併刪除，且無法復原。');">
            @csrf
            @method('DELETE')
            <button class="btn danger" type="submit">刪除此活動</button>
        </form>
    </div>
@endsection
