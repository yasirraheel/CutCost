<div class="row">
    <div class="col-12 col-md-6 form-group">
        <label for="outlet_name">Outlet name</label>
        <input class="input-sharesub" id="outlet_name" name="outlet_name" value="{{ old('outlet_name', $pressItem->outlet_name) }}" required>
    </div>
    <div class="col-12 col-md-6 form-group">
        <label for="logo_text">Logo text <span class="text-muted">(used when no logo is uploaded)</span></label>
        <input class="input-sharesub" id="logo_text" name="logo_text" value="{{ old('logo_text', $pressItem->logo_text) }}">
    </div>
    <div class="col-12 col-md-6 form-group">
        <label for="link_url">Link URL</label>
        <input class="input-sharesub" id="link_url" name="link_url" type="url" value="{{ old('link_url', $pressItem->link_url) }}">
    </div>
    <div class="col-12 col-md-6 form-group">
        <label for="sort_order">Sort order</label>
        <input class="input-sharesub" id="sort_order" name="sort_order" type="number" value="{{ old('sort_order', $pressItem->sort_order ?? 0) }}">
    </div>
    <div class="col-12 form-group">
        <label for="logo">Logo image <span class="text-muted">(jpg, png, webp or svg, max 2 MB)</span></label>
        @if ($pressItem->logo_path)
            <div class="mb-2"><img src="{{ str_starts_with($pressItem->logo_path, '/') || str_starts_with($pressItem->logo_path, 'http') ? asset($pressItem->logo_path) : asset('storage/'.$pressItem->logo_path) }}" alt="" style="max-height: 60px;"></div>
        @endif
        <input class="input-sharesub" id="logo" name="logo" type="file" accept=".jpg,.jpeg,.png,.webp,.svg">
    </div>
    <div class="col-12 form-group">
        <div class="filter-toggle justify-content-start">
            <div class="filter-toggle-wrapper">
                <input type="checkbox" id="is_active" name="is_active" value="1" class="checkbox-sharesub" @checked(old('is_active', $pressItem->is_active))>
                <label for="is_active" class="label-sharesub">Toggle</label>
            </div>
            <div class="filter-toggle-text">Active</div>
        </div>
    </div>
</div>
