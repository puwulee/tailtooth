@extends('layouts.app')

@section('title', '加入活動 · '.config('app.name'))

@section('content')
    <div class="panel">
        <h1>加入活動提問</h1>
        <p class="muted">輸入主辦單位提供的活動代碼，即可在現場匿名提問、為喜歡的問題按讚。</p>

        <form method="POST" action="{{ route('join') }}">
            @csrf
            <label for="code">活動代碼</label>
            <input type="text" id="code" name="code" value="{{ old('code') }}"
                   placeholder="例如 ABC123" autocomplete="off" autofocus
                   style="text-transform:uppercase; letter-spacing:.15em; font-weight:700;">
            @error('code')<div class="errors">{{ $message }}</div>@enderror

            <div style="margin-top:1rem;">
                <button class="btn" type="submit">加入 →</button>
            </div>
        </form>
    </div>

    <div class="panel">
        <h2>你是主辦單位？</h2>
        <p class="muted">登入後可建立活動、即時審核與回覆觀眾提問。</p>
        <a class="btn secondary" href="{{ route('login') }}">主辦者登入</a>
    </div>
@endsection
