<div class="row">
    <div class="col-12 col-md-6 form-group">
        <label for="slug">Slug</label>
        <input class="input-sharesub" id="slug" name="slug" value="{{ old('slug', $page->slug) }}" required>
    </div>
    <div class="col-12 col-md-6 form-group">
        <label for="type">Type</label>
        @php $selectedType = (string) old('type', $page->type); @endphp
        <select class="input-sharesub" id="type" name="type" required>
            @foreach ($types as $typeValue => $typeLabel)
                <option value="{{ $typeValue }}" @selected($selectedType === $typeValue)>{{ $typeLabel }}</option>
            @endforeach
            @if ($selectedType !== '' && ! array_key_exists($selectedType, $types))
                <option value="{{ $selectedType }}" selected>{{ $selectedType }} (no longer listed)</option>
            @endif
        </select>
    </div>
    <div class="col-12 form-group">
        <label for="title">Title</label>
        <input class="input-sharesub" id="title" name="title" value="{{ old('title', $page->title) }}" required>
    </div>
    <div class="col-12 form-group">
        <label for="excerpt">Excerpt</label>
        @php
            $textareaRows = 0;
            foreach (preg_split('/\r\n|\r|\n/', (string) (old('excerpt', $page->excerpt))) ?: [] as $textareaLine) {
                $textareaRows += max(1, (int) ceil(mb_strlen($textareaLine) / 80));
            }
            $textareaRows = min(24, max(6, $textareaRows + 1));
        @endphp
        <textarea style="height: auto;" class="input-sharesub" id="excerpt" name="excerpt" rows="{{ $textareaRows }}">{{ old('excerpt', $page->excerpt) }}</textarea>
    </div>
    <div class="col-12 form-group">
        <label for="body_html">Body HTML</label>
        @php
            $textareaRows = 0;
            foreach (preg_split('/\r\n|\r|\n/', (string) (old('body_html', $page->body_html))) ?: [] as $textareaLine) {
                $textareaRows += max(1, (int) ceil(mb_strlen($textareaLine) / 80));
            }
            $textareaRows = min(24, max(12, $textareaRows + 1));
        @endphp
        <textarea style="height: auto;" class="input-sharesub rich-text-editor" id="body_html" name="body_html" rows="{{ $textareaRows }}" required>{{ old('body_html', $page->body_html) }}</textarea>
    </div>
    <div class="col-12 col-md-6 form-group">
        <label for="sort_order">Sort order</label>
        <input class="input-sharesub" id="sort_order" name="sort_order" type="number" value="{{ old('sort_order', $page->sort_order ?? 0) }}">
    </div>
    <div class="col-12 col-md-6 form-group">
        <label for="image">Image <span class="text-muted">(jpg, png, webp or svg, max 2 MB)</span></label>
        @if ($page->image_path)
            <div class="mb-2"><img src="{{ str_starts_with($page->image_path, '/') || str_starts_with($page->image_path, 'http') ? asset($page->image_path) : asset('storage/'.$page->image_path) }}" alt="" style="max-height: 60px;"></div>
        @endif
        <input class="input-sharesub" id="image" name="image" type="file" accept=".jpg,.jpeg,.png,.webp,.svg">
    </div>
    <div class="col-12 col-md-6 form-group">
        <div class="filter-toggle justify-content-start">
            <div class="filter-toggle-wrapper">
                <input type="checkbox" id="is_active" name="is_active" value="1" class="checkbox-sharesub" @checked(old('is_active', $page->is_active))>
                <label for="is_active" class="label-sharesub">Toggle</label>
            </div>
            <div class="filter-toggle-text">Active</div>
        </div>
    </div>
</div>

@include('admin.partials.rich-text-editor')
