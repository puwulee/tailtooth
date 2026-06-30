    <label for="title">活動名稱</label>
    <input type="text" id="title" name="title" value="{{ old('title', $event->title ?? '') }}" required>
    @error('title')<div class="errors">{{ $message }}</div>@enderror

    <div class="row" style="align-items:flex-start;">
        <div style="flex:1; min-width:160px;">
            <label for="event_date">場次日期</label>
            <input type="date" id="event_date" name="event_date"
                   value="{{ old('event_date', isset($event->event_date) ? $event->event_date->toDateString() : '') }}" required>
            @error('event_date')<div class="errors">{{ $message }}</div>@enderror
        </div>
        <div style="flex:1; min-width:160px;">
            <label for="speaker">主講人</label>
            <input type="text" id="speaker" name="speaker" value="{{ old('speaker', $event->speaker ?? '') }}" required>
            @error('speaker')<div class="errors">{{ $message }}</div>@enderror
        </div>
    </div>

    <label for="topic">主題（AI 產生題目的依據）</label>
    <input type="text" id="topic" name="topic" maxlength="255" value="{{ old('topic', $event->topic ?? '') }}" required
           placeholder="例如：2026 年度會員權益與活動規劃">
    @error('topic')<div class="errors">{{ $message }}</div>@enderror

    <label for="description">活動說明（選填）</label>
    <textarea id="description" name="description" maxlength="2000">{{ old('description', $event->description ?? '') }}</textarea>
    @error('description')<div class="errors">{{ $message }}</div>@enderror

    <label class="check">
        <input type="checkbox" name="allow_anonymous" value="1"
               {{ old('allow_anonymous', $event->allow_anonymous ?? true) ? 'checked' : '' }}>
        允許匿名提問
    </label>
    <label class="check">
        <input type="checkbox" name="require_approval" value="1"
               {{ old('require_approval', $event->require_approval ?? false) ? 'checked' : '' }}>
        提問需審核後才公開（避免不當內容上牆）
    </label>
    <label class="check">
        <input type="checkbox" name="company_identity" value="1"
               {{ old('company_identity', $event->company_identity ?? false) ? 'checked' : '' }}>
        啟用統編公司身分（選填）— 觀眾可輸入統編，對照<a href="{{ route('admin.companies.index') }}" target="_blank" rel="noopener">公司名單</a>後以公司名顯示；未輸入者仍可匿名參與
    </label>
