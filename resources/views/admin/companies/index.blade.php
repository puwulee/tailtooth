@extends('layouts.app')

@section('title', '公司名單 · '.config('app.name'))
@section('container-class', 'wide')

@section('content')
    <div class="spread" style="margin-bottom:1rem;">
        <div>
            <h1 style="margin-bottom:.2rem;">公司名單</h1>
            <p class="muted" style="margin:0;">統一編號 ↔ 公司名稱對照表，供「需統編驗證」的活動共用。</p>
        </div>
        <a class="btn secondary" href="{{ route('admin.events.index') }}">← 我的活動</a>
    </div>

    @error('rows')<div class="alert err">{{ $message }}</div>@enderror
    @if (session('import_errors') && count(session('import_errors')))
        <div class="alert err">
            以下行格式錯誤已略過（需「8 碼統編 + 公司名稱」）：<br>
            {!! implode('<br>', array_map('e', session('import_errors'))) !!}
        </div>
    @endif

    <div class="panel">
        <h2>新增單筆</h2>
        <form method="POST" action="{{ route('admin.companies.store') }}">
            @csrf
            <div class="row" style="align-items:flex-start;">
                <div style="width:180px;">
                    <label for="tax_id">統一編號</label>
                    <input type="text" id="tax_id" name="tax_id" value="{{ old('tax_id') }}"
                           inputmode="numeric" maxlength="8" placeholder="8 碼數字">
                    @error('tax_id')<div class="errors">{{ $message }}</div>@enderror
                </div>
                <div style="flex:1; min-width:200px;">
                    <label for="name">公司名稱</label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}">
                    @error('name')<div class="errors">{{ $message }}</div>@enderror
                </div>
                <div style="align-self:flex-end;">
                    <button class="btn" type="submit">新增</button>
                </div>
            </div>
        </form>
    </div>

    <div class="panel">
        <h2>批次貼上匯入</h2>
        <p class="muted">每行一筆，格式「統編,公司名稱」（逗號、Tab 或空白皆可分隔）。重複統編會更新名稱。</p>
        <form method="POST" action="{{ route('admin.companies.import') }}">
            @csrf
            <textarea name="rows" rows="6" placeholder="12345675,台灣示範股份有限公司&#10;53212539	原創科技行銷">{{ old('rows') }}</textarea>
            <div style="margin-top:.6rem;"><button class="btn" type="submit">匯入</button></div>
        </form>
    </div>

    <div class="panel">
        <div class="spread" style="margin-bottom:.6rem;">
            <h2 style="margin:0;">名單（{{ $companies->total() }}）</h2>
            <form method="GET" action="{{ route('admin.companies.index') }}" class="row">
                <input type="text" name="q" value="{{ $q }}" placeholder="搜尋統編或公司名" style="width:auto;">
                <button class="btn secondary sm" type="submit">搜尋</button>
                @if ($q !== '')<a class="btn ghost sm" href="{{ route('admin.companies.index') }}">清除</a>@endif
            </form>
        </div>

        @if ($companies->isEmpty())
            <div class="empty">名單是空的，請用上方新增或批次匯入。</div>
        @else
            @foreach ($companies as $c)
                <div class="row" style="gap:.5rem; padding:.5rem 0; border-bottom:1px solid var(--line); flex-wrap:nowrap;">
                    <form method="POST" action="{{ route('admin.companies.update', $c) }}"
                          class="row" style="gap:.5rem; flex:1; flex-wrap:nowrap;">
                        @csrf @method('PUT')
                        <input type="text" name="tax_id" value="{{ $c->tax_id }}" maxlength="8"
                               inputmode="numeric" style="width:120px;">
                        <input type="text" name="name" value="{{ $c->name }}" style="flex:1; min-width:140px;">
                        <label class="check" style="margin:0; white-space:nowrap;">
                            <input type="checkbox" name="active" value="1" {{ $c->active ? 'checked' : '' }}> 啟用
                        </label>
                        <button class="btn secondary sm" type="submit">儲存</button>
                    </form>
                    <form method="POST" action="{{ route('admin.companies.destroy', $c) }}" class="inline-form"
                          onsubmit="return confirm('刪除「{{ $c->name }}」？');">
                        @csrf @method('DELETE')
                        <button class="btn ghost sm" type="submit">刪除</button>
                    </form>
                </div>
            @endforeach
            <div style="margin-top:1rem;">{{ $companies->links() }}</div>
        @endif
    </div>
@endsection
