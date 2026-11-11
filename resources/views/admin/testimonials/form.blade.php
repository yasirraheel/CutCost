<div class="row">
    <div class="col-12 col-md-6 form-group">
        <label for="author_name">Author name</label>
        <input class="input-sharesub" id="author_name" name="author_name" value="{{ old('author_name', $testimonial->author_name) }}" required>
    </div>
    <div class="col-12 col-md-6 form-group">
        <label for="role_text">Role</label>
        <input class="input-sharesub" id="role_text" name="role_text" value="{{ old('role_text', $testimonial->role_text) }}">
    </div>
    <div class="col-12 col-md-6 form-group">
        <label for="rating">Rating</label>
        <select class="input-sharesub" id="rating" name="rating">
            <option value="">No rating</option>
            @for ($i = 1; $i <= 5; $i++)
                <option value="{{ $i }}" @selected((int) old('rating', $testimonial->rating) === $i)>{{ $i }}</option>
            @endfor
        </select>
    </div>
    <div class="col-12 col-md-6 form-group">
        <label for="sort_order">Sort order</label>
        <input class="input-sharesub" id="sort_order" name="sort_order" type="number" value="{{ old('sort_order', $testimonial->sort_order ?? 0) }}">
    </div>
    <div class="col-12 form-group">
        <label for="quote">Quote</label>
        <textarea class="input-sharesub" id="quote" name="quote" rows="4" required>{{ old('quote', $testimonial->quote) }}</textarea>
    </div>
    <div class="col-12 form-group">
        <label for="avatar">Avatar image <span class="text-muted">(jpg, png, webp or svg, max 2 MB)</span></label>
        @if ($testimonial->avatar_path)
            <div class="mb-2"><img src="{{ str_starts_with($testimonial->avatar_path, '/') || str_starts_with($testimonial->avatar_path, 'http') ? asset($testimonial->avatar_path) : asset('storage/'.$testimonial->avatar_path) }}" alt="" style="max-height: 60px;"></div>
        @endif
        <input class="input-sharesub" id="avatar" name="avatar" type="file" accept=".jpg,.jpeg,.png,.webp,.svg">
    </div>
    <div class="col-12 form-group">
        <div class="filter-toggle justify-content-start">
            <div class="filter-toggle-wrapper">
                <input type="checkbox" id="is_active" name="is_active" value="1" class="checkbox-sharesub" @checked(old('is_active', $testimonial->is_active))>
                <label for="is_active" class="label-sharesub">Toggle</label>
            </div>
            <div class="filter-toggle-text">Active</div>
        </div>
    </div>
</div>
