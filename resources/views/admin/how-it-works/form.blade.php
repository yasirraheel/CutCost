<div class="row">
    <div class="col-12 form-group">
        <label for="title">Title</label>
        <input class="input-sharesub" id="title" name="title" value="{{ old('title', $step->title) }}" required>
    </div>
    <div class="col-12 form-group">
        <label for="text">Text</label>
        @php
            $textareaRows = 0;
            foreach (preg_split('/\r\n|\r|\n/', (string) (old('text', $step->text))) ?: [] as $textareaLine) {
                $textareaRows += max(1, (int) ceil(mb_strlen($textareaLine) / 80));
            }
            $textareaRows = min(24, max(6, $textareaRows + 1));
        @endphp
        <textarea style="height: auto;" class="input-sharesub" id="text" name="text" rows="{{ $textareaRows }}">{{ old('text', $step->text) }}</textarea>
    </div>
    <div class="col-12 form-group">
        <label for="icon_svg">Icon SVG</label>
        @php
            $textareaRows = 0;
            foreach (preg_split('/\r\n|\r|\n/', (string) (old('icon_svg', $step->icon_svg))) ?: [] as $textareaLine) {
                $textareaRows += max(1, (int) ceil(mb_strlen($textareaLine) / 80));
            }
            $textareaRows = min(24, max(6, $textareaRows + 1));
        @endphp
        <textarea style="height: auto;" class="input-sharesub" id="icon_svg" name="icon_svg" rows="{{ $textareaRows }}">{{ old('icon_svg', $step->icon_svg) }}</textarea>
    </div>
    <div class="col-12 col-md-6 form-group">
        <label for="audience">Audience</label>
        @php $selectedAudience = (string) old('audience', $step->audience ?? 'share'); @endphp
        <select class="input-sharesub" id="audience" name="audience">
            @foreach ($audiences as $audienceValue => $audienceLabel)
                <option value="{{ $audienceValue }}" @selected($selectedAudience === $audienceValue)>{{ $audienceLabel }}</option>
            @endforeach
            @if ($selectedAudience !== '' && ! array_key_exists($selectedAudience, $audiences))
                <option value="{{ $selectedAudience }}" selected>{{ $selectedAudience }} (no longer listed)</option>
            @endif
        </select>
    </div>
    <div class="col-12 form-group">
        <label for="mockup">Mockup image <span class="text-muted">(jpg, png, webp or svg, max 2 MB; shown in that step's mockup panel)</span></label>
        @if ($step->mockup_image_path)
            <div class="mb-2"><img src="{{ str_starts_with($step->mockup_image_path, '/') || str_starts_with($step->mockup_image_path, 'http') ? asset($step->mockup_image_path) : asset('storage/'.$step->mockup_image_path) }}" alt="" style="max-height: 120px;"></div>
            <div class="mb-2 text-muted text-sm">Current: {{ $step->mockup_image_path }}</div>
        @endif
        <input class="input-sharesub" id="mockup" name="mockup" type="file" accept=".jpg,.jpeg,.png,.webp,.svg">
    </div>
    <div class="col-12 col-md-6 form-group">
        <label for="sort_order">Sort order</label>
        <input class="input-sharesub" id="sort_order" name="sort_order" type="number" value="{{ old('sort_order', $step->sort_order ?? 0) }}">
    </div>
    <div class="col-12 col-md-6 form-group">
        <div class="filter-toggle justify-content-start">
            <div class="filter-toggle-wrapper">
                <input type="checkbox" id="is_active" name="is_active" value="1" class="checkbox-sharesub" @checked(old('is_active', $step->is_active))>
                <label for="is_active" class="label-sharesub">Toggle</label>
            </div>
            <div class="filter-toggle-text">Active</div>
        </div>
    </div>
</div>
