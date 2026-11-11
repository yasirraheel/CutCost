<div class="row">
    <div class="col-12 form-group">
        <label for="title">Title</label>
        <input class="input-sharesub" id="title" name="title" value="{{ old('title', $step->title) }}" required>
    </div>
    <div class="col-12 form-group">
        <label for="text">Text</label>
        <textarea class="input-sharesub" id="text" name="text" rows="3">{{ old('text', $step->text) }}</textarea>
    </div>
    <div class="col-12 form-group">
        <label for="icon_svg">Icon SVG</label>
        <textarea class="input-sharesub" id="icon_svg" name="icon_svg" rows="3">{{ old('icon_svg', $step->icon_svg) }}</textarea>
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
