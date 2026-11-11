<div class="row">
    <div class="col-12 form-group">
        <label for="title">Title</label>
        <input class="input-sharesub" id="title" name="title" value="{{ old('title', $feature->title) }}" required>
    </div>
    <div class="col-12 form-group">
        <label for="text">Text</label>
        @php
            $textareaRows = 0;
            foreach (preg_split('/\r\n|\r|\n/', (string) (old('text', $feature->text))) ?: [] as $textareaLine) {
                $textareaRows += max(1, (int) ceil(mb_strlen($textareaLine) / 80));
            }
            $textareaRows = min(24, max(6, $textareaRows + 1));
        @endphp
        <textarea style="height: auto;" class="input-sharesub" id="text" name="text" rows="{{ $textareaRows }}">{{ old('text', $feature->text) }}</textarea>
    </div>
    <div class="col-12 form-group">
        <label for="icon_path">Icon path <span class="text-muted">(existing /img asset, for example /img/dashboard/features/service-legal.svg)</span></label>
        <input class="input-sharesub" id="icon_path" name="icon_path" value="{{ old('icon_path', $feature->icon_path) }}">
    </div>
    <div class="col-12 form-group">
        <label for="icon">Icon image upload <span class="text-muted">(jpg, png, webp or svg, max 2 MB; replaces the icon path above)</span></label>
        @if ($feature->icon_path)
            <div class="mb-2"><img src="{{ str_starts_with($feature->icon_path, '/') || str_starts_with($feature->icon_path, 'http') ? asset($feature->icon_path) : asset('storage/'.$feature->icon_path) }}" alt="" style="max-height: 60px;"></div>
            <div class="mb-2 text-muted text-sm">Current: {{ $feature->icon_path }}</div>
        @endif
        <input class="input-sharesub" id="icon" name="icon" type="file" accept=".jpg,.jpeg,.png,.webp,.svg">
    </div>
    <div class="col-12 form-group">
        <label for="link_url">Link URL <span class="text-muted">(used for the Read More link; leave empty for no link)</span></label>
        <input class="input-sharesub" id="link_url" name="link_url" value="{{ old('link_url', $feature->link_url) }}">
    </div>
    <div class="col-12 form-group">
        <label for="icon_svg">Icon SVG <span class="text-muted">(inline SVG alternative)</span></label>
        @php
            $textareaRows = 0;
            foreach (preg_split('/\r\n|\r|\n/', (string) (old('icon_svg', $feature->icon_svg))) ?: [] as $textareaLine) {
                $textareaRows += max(1, (int) ceil(mb_strlen($textareaLine) / 80));
            }
            $textareaRows = min(24, max(6, $textareaRows + 1));
        @endphp
        <textarea style="height: auto;" class="input-sharesub" id="icon_svg" name="icon_svg" rows="{{ $textareaRows }}">{{ old('icon_svg', $feature->icon_svg) }}</textarea>
    </div>
    <div class="col-12 col-md-6 form-group">
        <label for="sort_order">Sort order</label>
        <input class="input-sharesub" id="sort_order" name="sort_order" type="number" value="{{ old('sort_order', $feature->sort_order ?? 0) }}">
    </div>
    <div class="col-12 col-md-6 form-group">
        <div class="filter-toggle justify-content-start">
            <div class="filter-toggle-wrapper">
                <input type="checkbox" id="is_active" name="is_active" value="1" class="checkbox-sharesub" @checked(old('is_active', $feature->is_active))>
                <label for="is_active" class="label-sharesub">Toggle</label>
            </div>
            <div class="filter-toggle-text">Active</div>
        </div>
    </div>
</div>
