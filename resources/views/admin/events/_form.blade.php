    <label for="title">活動名稱</label>
    <input type="text" id="title" name="title" value="{{ old('title', $event->title ?? '') }}" required>
    @error('title')<div class="errors">{{ $message }}</div>@enderror

    <label for="description">活動說明（選填）</label>
    <textarea id="description" name="description" maxlength="2000">{{ old('description', $event->description ?? '') }}</textarea>
    @error('description')<div class="errors">{{ $message }}</div>@enderror

    <label for="starts_at">開始時間（選填）</label>
    <input type="datetime-local" id="starts_at" name="starts_at"
           value="{{ old('starts_at', isset($event->starts_at) ? $event->starts_at->format('Y-m-d\TH:i') : '') }}">
    @error('starts_at')<div class="errors">{{ $message }}</div>@enderror

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
