@extends('layouts.app')

@section('title', '建立活動 · '.config('app.name'))

@section('content')
    <p><a href="{{ route('admin.events.index') }}">← 回我的活動</a></p>
    <div class="panel">
        <h1>建立活動</h1>
        <p class="muted">建立後會自動產生一組加入代碼，分享給觀眾即可開始提問。</p>
        <form method="POST" action="{{ route('admin.events.store') }}">
            @csrf
            @include('admin.events._form')
            <div style="margin-top:1.2rem;">
                <button class="btn" type="submit">建立活動</button>
            </div>
        </form>
    </div>
@endsection
