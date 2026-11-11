<div class="row">
    <div class="col-12 col-md-6 form-group">
        <label for="name">Name</label>
        <input class="input-sharesub" id="name" name="name" value="{{ old('name', $brand->name) }}" required>
    </div>
    <div class="col-12 col-md-6 form-group">
        <label for="slug">Slug</label>
        <input class="input-sharesub" id="slug" name="slug" value="{{ old('slug', $brand->slug) }}" required>
    </div>
    <div class="col-12 col-md-4 form-group">
        <label for="price">Price</label>
        <input class="input-sharesub" id="price" name="price" type="number" step="0.01" min="0" value="{{ old('price', $brand->price) }}" required>
    </div>
    <div class="col-12 col-md-4 form-group">
        <label for="currency">Currency code</label>
        <input class="input-sharesub" id="currency" name="currency" maxlength="3" value="{{ old('currency', $brand->currency) }}">
    </div>
    <div class="col-12 col-md-4 form-group">
        <label for="currency_symbol">Currency symbol</label>
        <input class="input-sharesub" id="currency_symbol" name="currency_symbol" maxlength="5" value="{{ old('currency_symbol', $brand->currency_symbol) }}">
    </div>
    <div class="col-12 col-md-6 form-group">
        <label for="discount_percent">Participation percent</label>
        <input class="input-sharesub" id="discount_percent" name="discount_percent" type="number" min="0" max="100" value="{{ old('discount_percent', $brand->discount_percent) }}">
    </div>
    <div class="col-12 col-md-6 form-group">
        <label for="category_id">Category</label>
        <select class="input-sharesub" id="category_id" name="category_id">
            <option value="">No category</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((int) old('category_id', $brand->category_id) === $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12 col-md-6 form-group">
        <label for="background">Background</label>
        <input class="input-sharesub" id="background" name="background" value="{{ old('background', $brand->background) }}">
    </div>
    <div class="col-12 col-md-6 form-group">
        <label for="sort_order">Sort order</label>
        <input class="input-sharesub" id="sort_order" name="sort_order" type="number" value="{{ old('sort_order', $brand->sort_order ?? 0) }}">
    </div>
    <div class="col-12 form-group">
        <label for="short_description">Short description</label>
        <input class="input-sharesub" id="short_description" name="short_description" value="{{ old('short_description', $brand->short_description) }}">
    </div>
    <div class="col-12 form-group">
        <label for="tagline">Tagline</label>
        <input class="input-sharesub" id="tagline" name="tagline" value="{{ old('tagline', $brand->tagline) }}">
    </div>
    <div class="col-12 form-group">
        <label for="icon_svg">Icon SVG</label>
        <textarea class="input-sharesub" id="icon_svg" name="icon_svg" rows="3">{{ old('icon_svg', $brand->icon_svg) }}</textarea>
    </div>
    <div class="col-12 form-group">
        <label for="logo">Logo image <span class="text-muted">(jpg, png, webp or svg, max 2 MB)</span></label>
        @if ($brand->logo_path)
            <div class="mb-2"><img src="{{ asset('storage/'.$brand->logo_path) }}" alt="" style="max-height: 60px;"></div>
        @endif
        <input class="input-sharesub" id="logo" name="logo" type="file" accept=".jpg,.jpeg,.png,.webp,.svg">
    </div>
    <div class="col-12 col-md-6 form-group">
        <div class="filter-toggle justify-content-start">
            <div class="filter-toggle-wrapper">
                <input type="checkbox" id="is_featured" name="is_featured" value="1" class="checkbox-sharesub" @checked(old('is_featured', $brand->is_featured))>
                <label for="is_featured" class="label-sharesub">Toggle</label>
            </div>
            <div class="filter-toggle-text">Featured on the homepage</div>
        </div>
    </div>
    <div class="col-12 col-md-6 form-group">
        <div class="filter-toggle justify-content-start">
            <div class="filter-toggle-wrapper">
                <input type="checkbox" id="is_active" name="is_active" value="1" class="checkbox-sharesub" @checked(old('is_active', $brand->is_active))>
                <label for="is_active" class="label-sharesub">Toggle</label>
            </div>
            <div class="filter-toggle-text">Active</div>
        </div>
    </div>
</div>
