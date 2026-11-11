<div class="row">
    <div class="col-12 col-md-6 form-group">
        <label for="brand_id">Brand</label>
        <select class="input-sharesub" id="brand_id" name="brand_id" required>
            <option value="">Select a brand</option>
            @foreach ($brands as $brand)
                <option value="{{ $brand->id }}" @selected((int) old('brand_id', $template->brand_id) === $brand->id)>{{ $brand->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12 col-md-6 form-group">
        <label for="name">Name</label>
        <input class="input-sharesub" id="name" name="name" value="{{ old('name', $template->name) }}" required>
    </div>
    <div class="col-12 col-md-6 form-group">
        <label for="slug">Slug</label>
        <input class="input-sharesub" id="slug" name="slug" value="{{ old('slug', $template->slug) }}" required>
    </div>
    <div class="col-12 col-md-6 form-group">
        <label for="currency">Currency code</label>
        <input class="input-sharesub" id="currency" name="currency" maxlength="3" value="{{ old('currency', $template->currency) }}" required>
    </div>
    <div class="col-12 col-md-4 form-group">
        <label for="price_with_fees">Price with fees</label>
        <input class="input-sharesub" id="price_with_fees" name="price_with_fees" type="number" step="0.01" min="0" value="{{ old('price_with_fees', $template->price_with_fees) }}" required>
    </div>
    <div class="col-12 col-md-4 form-group">
        <label for="retail_price">Retail price</label>
        <input class="input-sharesub" id="retail_price" name="retail_price" type="number" step="0.01" min="0" value="{{ old('retail_price', $template->retail_price) }}">
    </div>
    <div class="col-12 col-md-4 form-group">
        <label for="sort_order">Sort order</label>
        <input class="input-sharesub" id="sort_order" name="sort_order" type="number" value="{{ old('sort_order', $template->sort_order ?? 0) }}">
    </div>
    <div class="col-12 col-md-4 form-group">
        <label for="seats">Seats</label>
        <input class="input-sharesub" id="seats" name="seats" type="number" min="0" value="{{ old('seats', $template->seats) }}">
    </div>
    <div class="col-12 col-md-4 form-group">
        <label for="shareable_seats">Shareable seats</label>
        <input class="input-sharesub" id="shareable_seats" name="shareable_seats" type="number" min="0" value="{{ old('shareable_seats', $template->shareable_seats) }}">
    </div>
    <div class="col-12 col-md-4 form-group">
        <label for="available_seats">Available seats</label>
        <input class="input-sharesub" id="available_seats" name="available_seats" type="number" min="0" value="{{ old('available_seats', $template->available_seats) }}">
    </div>
    <div class="col-12 form-group">
        <label for="description">Description</label>
        <textarea class="input-sharesub" id="description" name="description" rows="3">{{ old('description', $template->description) }}</textarea>
    </div>
    <div class="col-12 form-group">
        <label for="description_seo">Description SEO</label>
        <textarea class="input-sharesub" id="description_seo" name="description_seo" rows="3">{{ old('description_seo', $template->description_seo) }}</textarea>
    </div>
    <div class="col-12 form-group">
        <label for="title_seo">Title SEO</label>
        <input class="input-sharesub" id="title_seo" name="title_seo" value="{{ old('title_seo', $template->title_seo) }}">
    </div>
    <div class="col-12 form-group">
        <label for="helper_create">Helper text for sharing</label>
        <textarea class="input-sharesub" id="helper_create" name="helper_create" rows="3">{{ old('helper_create', $template->helper_create) }}</textarea>
    </div>
    <div class="col-12 form-group">
        <label for="helper_join">Helper text for joining</label>
        <textarea class="input-sharesub" id="helper_join" name="helper_join" rows="3">{{ old('helper_join', $template->helper_join) }}</textarea>
    </div>
    <div class="col-12 col-md-6 form-group">
        <div class="filter-toggle justify-content-start">
            <div class="filter-toggle-wrapper">
                <input type="checkbox" id="is_active" name="is_active" value="1" class="checkbox-sharesub" @checked(old('is_active', $template->is_active))>
                <label for="is_active" class="label-sharesub">Toggle</label>
            </div>
            <div class="filter-toggle-text">Active</div>
        </div>
    </div>
</div>
