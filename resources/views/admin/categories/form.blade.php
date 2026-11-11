<div class="row">
    <div class="col-12 col-md-6 form-group">
        <label for="name">Name</label>
        <input class="input-sharesub" id="name" name="name" value="{{ old('name', $category->name) }}" required>
    </div>
    <div class="col-12 col-md-6 form-group">
        <label for="slug">Slug</label>
        <input class="input-sharesub" id="slug" name="slug" value="{{ old('slug', $category->slug) }}" required>
    </div>
    <div class="col-12 col-md-6 form-group">
        <label for="sort_order">Sort order</label>
        <input class="input-sharesub" id="sort_order" name="sort_order" type="number" value="{{ old('sort_order', $category->sort_order ?? 0) }}">
    </div>
    <div class="col-12 col-md-6 form-group">
        <div class="filter-toggle justify-content-start">
            <div class="filter-toggle-wrapper">
                <input type="checkbox" id="is_active" name="is_active" value="1" class="checkbox-sharesub" @checked(old('is_active', $category->is_active))>
                <label for="is_active" class="label-sharesub">Toggle</label>
            </div>
            <div class="filter-toggle-text">Active</div>
        </div>
    </div>
    <div class="col-12 form-group">
        <label for="icon_svg">Icon SVG</label>
        <textarea class="input-sharesub" id="icon_svg" name="icon_svg" rows="3">{{ old('icon_svg', $category->icon_svg) }}</textarea>
    </div>
</div>
