@extends('layouts.app')

@section('title', $event->title.' · '.config('app.name'))

@section('content')
    <div class="panel">
        <div class="spread">
            <div>
                <h1 style="margin-bottom:.35rem;">{{ $event->title }}</h1>
                <span class="pill {{ $event->status }}" id="evStatus">
                    {{ $event->isOpen() ? '提問開放中' : '提問已關閉' }}
                </span>
                @if ($event->require_company) <span class="pill">需統編驗證</span> @endif
            </div>
            <span class="code-badge">{{ $event->code }}</span>
        </div>
        @if ($event->description)
            <p class="muted" style="margin-top:.6rem; white-space:pre-wrap;">{{ $event->description }}</p>
        @endif
    </div>

    @if ($event->require_company)
    <div class="panel" id="verifyPanel" style="display:none;">
        <h2>輸入統一編號參與</h2>
        <p class="muted">本活動需以公司身分參與。請輸入貴公司統一編號，驗證後即可提問與投票。</p>
        <div class="row" style="align-items:flex-start;">
            <div style="flex:1; min-width:180px;">
                <input type="text" id="taxInput" inputmode="numeric" maxlength="8" placeholder="8 碼統一編號"
                       style="letter-spacing:.1em; font-weight:600;">
                <div class="errors" id="taxError" style="display:none;"></div>
            </div>
            <button class="btn" type="button" id="taxBtn">驗證</button>
        </div>
    </div>

    <div class="panel" id="identityBar" style="display:none;">
        <div class="spread">
            <div>目前以 <strong id="companyName"></strong> 身分參與</div>
            <button class="btn ghost sm" type="button" id="switchBtn">切換公司</button>
        </div>
    </div>
    @endif

    @if ($event->isOpen())
    <div class="panel" id="askPanel">
        <h2>我要提問</h2>
        <form id="askForm">
            <textarea id="qbody" maxlength="1000" placeholder="輸入你的問題…" required></textarea>
            <label for="qname">你的名字（選填{{ $event->allow_anonymous ? '，留空即匿名' : '' }}）</label>
            <input type="text" id="qname" maxlength="60"
                   placeholder="{{ $event->allow_anonymous ? '匿名' : '請輸入名字' }}"
                   {{ $event->allow_anonymous ? '' : 'required' }}>
            <div class="row" style="margin-top:.9rem; justify-content:space-between;">
                <span class="muted" id="askHint">
                    @if ($event->require_approval) 送出後需主辦審核才會公開顯示。 @endif
                </span>
                <button class="btn" type="submit" id="askBtn">送出提問</button>
            </div>
        </form>
    </div>
    @else
        <div class="panel"><div class="empty">此活動目前未開放提問，以下為已公開的問答。</div></div>
    @endif

    <div class="panel">
        <div class="tabs">
            <button data-sort="top" class="active">🔥 熱門</button>
            <button data-sort="recent">🕒 最新</button>
        </div>
        <div id="qlist"><div class="empty">載入中…</div></div>
    </div>

    <script>
    (function () {
        const eventId = @json($event->id);
        const requireCompany = @json((bool) $event->require_company);
        const urls = {
            list: @json(route('events.questions', $event)),
            ask: @json(route('events.questions.store', $event)),
            verify: @json(route('events.verify', $event)),
            vote: @json(url("/e/{$event->slug}/questions")) + '/{id}/vote',
        };
        const csrf = document.querySelector('meta[name=csrf-token]').content;

        // 此瀏覽器身分（投票去重 / 標記自己的提問）
        let token = localStorage.getItem('qa_token');
        if (!token) {
            token = (crypto.randomUUID ? crypto.randomUUID() : String(Date.now()) + Math.random());
            localStorage.setItem('qa_token', token);
        }

        // 公司身分（需統編驗證的活動）
        const companyKey = 'qa_company_' + eventId;
        let company = null;
        try { company = JSON.parse(localStorage.getItem(companyKey) || 'null'); } catch (e) {}

        let sort = 'top';
        let busyVotes = {};
        const listEl = document.getElementById('qlist');

        function esc(s) {
            return String(s).replace(/[&<>"']/g, c => (
                {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]
            ));
        }

        // ---- 統編驗證 UI ----
        function refreshIdentity() {
            if (!requireCompany) return;
            const verified = !!company;
            const vp = document.getElementById('verifyPanel');
            const ib = document.getElementById('identityBar');
            const ap = document.getElementById('askPanel');
            if (vp) vp.style.display = verified ? 'none' : '';
            if (ib) ib.style.display = verified ? '' : 'none';
            if (ap) ap.style.display = verified ? '' : 'none';
            if (verified && ib) document.getElementById('companyName').textContent = company.name;
        }

        async function verify() {
            const input = document.getElementById('taxInput');
            const err = document.getElementById('taxError');
            const taxId = (input.value || '').replace(/\D/g, '');
            err.style.display = 'none';
            if (taxId.length !== 8) { err.textContent = '請輸入 8 碼數字。'; err.style.display = ''; return; }
            try {
                const res = await fetch(urls.verify, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    body: JSON.stringify({ tax_id: taxId }),
                });
                const data = await res.json();
                if (res.ok && data.ok) {
                    company = { tax_id: data.tax_id, name: data.company_name };
                    localStorage.setItem(companyKey, JSON.stringify(company));
                    refreshIdentity();
                } else {
                    err.textContent = data.message || '驗證失敗。'; err.style.display = '';
                }
            } catch (e) { err.textContent = '網路錯誤，請再試一次。'; err.style.display = ''; }
        }

        if (requireCompany) {
            document.getElementById('taxBtn').addEventListener('click', verify);
            document.getElementById('taxInput').addEventListener('keydown', e => { if (e.key === 'Enter') verify(); });
            document.getElementById('switchBtn').addEventListener('click', () => {
                company = null; localStorage.removeItem(companyKey);
                document.getElementById('taxInput').value = '';
                refreshIdentity();
            });
            refreshIdentity();
        }

        function needCompany() {
            if (requireCompany && !company) {
                const vp = document.getElementById('verifyPanel');
                if (vp) vp.scrollIntoView({ behavior: 'smooth' });
                return true;
            }
            return false;
        }

        // ---- 提問牆 ----
        function render(questions) {
            if (!questions.length) {
                listEl.innerHTML = '<div class="empty">還沒有人提問，搶第一個吧！</div>';
                return;
            }
            listEl.innerHTML = questions.map(q => `
                <div class="qcard ${q.pinned ? 'pinned' : ''}">
                    <div class="qrow">
                        <button class="vote ${q.voted ? 'voted' : ''}" data-id="${q.id}" aria-label="按讚">
                            <span class="n">${q.upvotes}</span><span class="t">讚</span>
                        </button>
                        <div class="grow">
                            <div class="qbody">${esc(q.body)}</div>
                            <div class="meta">
                                ${q.pinned ? '📌 置頂 · ' : ''}${esc(q.author)}${q.mine ? '（你）' : ''}
                                ${q.answered ? ' · ✅ 已回覆' : ''}
                            </div>
                            ${q.answered ? `<div class="answer"><span class="lbl">主辦回覆</span><div>${esc(q.answer)}</div></div>` : ''}
                        </div>
                    </div>
                </div>
            `).join('');

            listEl.querySelectorAll('.vote').forEach(btn => {
                btn.addEventListener('click', () => vote(btn.dataset.id));
            });
        }

        async function load() {
            try {
                const res = await fetch(`${urls.list}?sort=${sort}&token=${encodeURIComponent(token)}`, {
                    headers: { 'Accept': 'application/json' },
                });
                const data = await res.json();
                render(data.questions);
                const st = document.getElementById('evStatus');
                if (st && data.event) {
                    const open = data.event.status === 'open';
                    st.textContent = open ? '提問開放中' : '提問已關閉';
                    st.className = 'pill ' + data.event.status;
                }
            } catch (e) { /* 網路抖動：保留現有畫面 */ }
        }

        async function vote(id) {
            if (needCompany()) return;
            if (busyVotes[id]) return;
            busyVotes[id] = true;
            try {
                const res = await fetch(urls.vote.replace('{id}', id), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    body: JSON.stringify({ token, tax_id: company ? company.tax_id : null }),
                });
                if (res.ok) await load();
            } finally { busyVotes[id] = false; }
        }

        document.querySelectorAll('.tabs button').forEach(t => t.addEventListener('click', () => {
            document.querySelectorAll('.tabs button').forEach(x => x.classList.remove('active'));
            t.classList.add('active');
            sort = t.dataset.sort;
            load();
        }));

        const form = document.getElementById('askForm');
        if (form) {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                if (needCompany()) return;
                const body = document.getElementById('qbody').value.trim();
                const name = document.getElementById('qname').value.trim();
                if (body.length < 2) return;
                const btn = document.getElementById('askBtn');
                btn.disabled = true;
                try {
                    const res = await fetch(urls.ask, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                        body: JSON.stringify({ body, author_name: name, token, tax_id: company ? company.tax_id : null }),
                    });
                    if (res.ok) {
                        const data = await res.json();
                        document.getElementById('qbody').value = '';
                        document.getElementById('askHint').textContent =
                            data.pending ? '✅ 已送出，待主辦審核後顯示。' : '✅ 已送出！';
                        await load();
                    } else {
                        document.getElementById('askHint').textContent = '送出失敗，請確認身分或稍後再試。';
                    }
                } finally { btn.disabled = false; }
            });
        }

        load();
        setInterval(load, 4000); // 輪詢即時更新
    })();
    </script>
@endsection
