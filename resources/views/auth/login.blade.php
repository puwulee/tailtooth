@extends('layouts.app')

@section('title', '主辦者登入 · '.config('app.name'))

@section('content')
    <div class="panel" style="max-width:420px; margin:2rem auto;">
        <h1>主辦者登入</h1>
        <p class="muted">登入以建立活動、審核與回覆提問。</p>

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
            @error('email')<div class="errors">{{ $message }}</div>@enderror

            <label for="password">密碼</label>
            <input type="password" id="password" name="password" required>

            <label class="check" style="margin-top:1rem;">
                <input type="checkbox" name="remember" value="1"> 記住我
            </label>

            <button class="btn" type="submit" style="margin-top:.6rem;">登入</button>
        </form>
    </div>
@endsection
