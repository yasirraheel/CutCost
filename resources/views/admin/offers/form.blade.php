<div class="row">
    <div class="col-12 col-md-6 form-group">
        <label for="brand_id">Brand</label>
        <select class="input-sharesub" id="brand_id" name="brand_id" required>
            <option value="">Select a brand</option>
            @foreach ($brands as $brand)
                <option value="{{ $brand->id }}" @selected((int) old('brand_id', $offer->brand_id) === $brand->id)>{{ $brand->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12 col-md-6 form-group">
        <label for="title">Plan title</label>
        <input class="input-sharesub" id="title" name="title" value="{{ old('title', $offer->title) }}" required>
    </div>
    <div class="col-12 col-md-4 form-group">
        <label for="price">Price</label>
        <input class="input-sharesub" id="price" name="price" type="number" step="0.01" min="0" value="{{ old('price', $offer->price) }}" required>
    </div>
    <div class="col-12 col-md-4 form-group">
        <label for="currency">Currency code</label>
        <input class="input-sharesub" id="currency" name="currency" maxlength="3" value="{{ old('currency', $offer->currency) }}" required>
    </div>
    <div class="col-12 col-md-4 form-group">
        <label for="seller_name">Seller name</label>
        <input class="input-sharesub" id="seller_name" name="seller_name" value="{{ old('seller_name', $offer->seller_name) }}">
    </div>
    <div class="col-12 col-md-6 form-group">
        <label for="seats_total">Total seats</label>
        <input class="input-sharesub" id="seats_total" name="seats_total" type="number" min="0" value="{{ old('seats_total', $offer->seats_total) }}">
    </div>
    <div class="col-12 col-md-6 form-group">
        <label for="seats_available">Seats available</label>
        <input class="input-sharesub" id="seats_available" name="seats_available" type="number" min="0" value="{{ old('seats_available', $offer->seats_available) }}">
    </div>
    <div class="col-12 col-md-6 form-group">
        <label for="sort_order">Sort order</label>
        <input class="input-sharesub" id="sort_order" name="sort_order" type="number" value="{{ old('sort_order', $offer->sort_order ?? 0) }}">
    </div>
    <div class="col-12 col-md-6 form-group">
        <div class="filter-toggle justify-content-start">
            <div class="filter-toggle-wrapper">
                <input type="checkbox" id="is_active" name="is_active" value="1" class="checkbox-sharesub" @checked(old('is_active', $offer->is_active))>
                <label for="is_active" class="label-sharesub">Toggle</label>
            </div>
            <div class="filter-toggle-text">Active</div>
        </div>
    </div>
</div>
