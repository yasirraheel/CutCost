<div class="row">
    <div class="col-12 form-group">
        <label for="question">Question</label>
        <input class="input-sharesub" id="question" name="question" value="{{ old('question', $faq->question) }}" required>
    </div>
    <div class="col-12 form-group">
        <label for="answer">Answer</label>
        <textarea class="input-sharesub" id="answer" name="answer" rows="4" required>{{ old('answer', $faq->answer) }}</textarea>
    </div>
    <div class="col-12 col-md-6 form-group">
        <label for="sort_order">Sort order</label>
        <input class="input-sharesub" id="sort_order" name="sort_order" type="number" value="{{ old('sort_order', $faq->sort_order ?? 0) }}">
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
