<div class="row">
    <div class="col-12 col-md-6 form-group">
        <label for="brand_id">Brand</label>
        @php $selectedBrandId = (int) old('brand_id', $faq->brand_id); @endphp
        <select class="input-sharesub" id="brand_id" name="brand_id" required>
            <option value="">Select a brand</option>
            @foreach ($brands as $brand)
                <option value="{{ $brand->id }}" @selected($selectedBrandId === $brand->id)>{{ $brand->name }}</option>
            @endforeach
            @if ($selectedBrandId > 0 && ! $brands->contains('id', $selectedBrandId))
                <option value="{{ $selectedBrandId }}" selected>Brand #{{ $selectedBrandId }} (no longer listed)</option>
            @endif
        </select>
    </div>
    <div class="col-12 col-md-6 form-group">
        <label for="sort_order">Sort order</label>
        <input class="input-sharesub" id="sort_order" name="sort_order" type="number" value="{{ old('sort_order', $faq->sort_order ?? 0) }}">
    </div>
    <div class="col-12 form-group">
        <label for="question">Question</label>
        <input class="input-sharesub" id="question" name="question" value="{{ old('question', $faq->question) }}" required>
    </div>
    <div class="col-12 form-group">
        <label for="answer">Answer</label>
        @php
            $textareaRows = 0;
            foreach (preg_split('/\r\n|\r|\n/', (string) (old('answer', $faq->answer))) ?: [] as $textareaLine) {
                $textareaRows += max(1, (int) ceil(mb_strlen($textareaLine) / 80));
            }
            $textareaRows = min(24, max(6, $textareaRows + 1));
        @endphp
        <textarea style="height: auto;" class="input-sharesub" id="answer" name="answer" rows="{{ $textareaRows }}" required>{{ old('answer', $faq->answer) }}</textarea>
    </div>
    <div class="col-12 col-md-6 form-group">
        <div class="filter-toggle justify-content-start">
            <div class="filter-toggle-wrapper">
                <input type="checkbox" id="is_active" name="is_active" value="1" class="checkbox-sharesub" @checked(old('is_active', $faq->is_active))>
                <label for="is_active" class="label-sharesub">Toggle</label>
            </div>
            <div class="filter-toggle-text">Active</div>
        </div>
    </div>
</div>
