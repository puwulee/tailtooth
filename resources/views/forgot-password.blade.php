<!DOCTYPE html><html lang="zh-Hant"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1"><title>忘記密碼 · Tailtooth</title>
<style>body{margin:0;height:100vh;display:flex;align-items:center;justify-content:center;font-family:system-ui,"Noto Sans TC",sans-serif;background:radial-gradient(800px 500px at 50% -10%,#1b2236,#0a0d15);color:#eef2ff}
.card{width:340px;background:#141a28;border:1px solid #26304a;border-radius:16px;padding:28px}h1{font-size:20px;margin:0 0 14px}
input{width:100%;padding:11px;margin:6px 0 14px;border-radius:10px;border:1px solid #26304a;background:#1b2233;color:#eef2ff}
button{width:100%;padding:12px;border:0;border-radius:10px;font-weight:800;background:linear-gradient(160deg,#ffd86b,#e0a900);color:#1a1300}
.ok{color:#5dffa0;font-size:13px;margin-bottom:10px}a{color:#9aa6c4;font-size:13px}</style></head><body>
<form class="card" method="POST" action="/forgot-password">@csrf<h1>🔑 忘記密碼</h1>
@if(session('status'))<div class="ok">{{ session('status') }}</div>@endif
<label>Email</label><input type="email" name="email" required>
<button type="submit">寄送重設連結</button>
<p><a href="/login">← 返回登入</a></p></form></body></html>
