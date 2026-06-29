<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>登入 · Tailtooth</title>
    <style>
        body { margin:0; height:100vh; display:flex; align-items:center; justify-content:center;
               font-family:system-ui,"Noto Sans TC",sans-serif; background:radial-gradient(800px 500px at 50% -10%,#1b2236,#0a0d15); color:#eef2ff; }
        .card { width:340px; background:#141a28; border:1px solid #26304a; border-radius:16px; padding:28px; }
        h1 { font-size:22px; margin:0 0 4px; } .sub { color:#9aa6c4; font-size:13px; margin-bottom:18px; }
        label { font-size:13px; color:#9aa6c4; } input { width:100%; padding:11px; margin:6px 0 14px; border-radius:10px; border:1px solid #26304a; background:#1b2233; color:#eef2ff; }
        button { width:100%; padding:12px; border:0; border-radius:10px; font-weight:800; background:linear-gradient(160deg,#ffd86b,#e0a900); color:#1a1300; }
        .err { color:#ff7b94; font-size:13px; margin-bottom:10px; }
    </style>
</head>
<body>
    <form class="card" method="POST" action="/login">
        @csrf
        <h1>⚔️ Tailtooth</h1>
        <div class="sub">賽事管理後台登入</div>
        @if (isset($errors) && $errors->any())<div class="err">{{ $errors->first() }}</div>@endif
        <label>Email</label><input type="email" name="email" required autofocus>
        <label>密碼</label><input type="password" name="password" required>
        <button type="submit">登入</button>
    </form>
</body>
</html>
