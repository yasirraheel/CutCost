<div class="row">
    <div class="col-12 col-md-4 form-group">
        <label for="code">Code <span class="text-muted">(3 letters)</span></label>
        <input class="input-sharesub" id="code" name="code" value="{{ old('code', $currency->code) }}" required maxlength="3">
    </div>
    <div class="col-12 col-md-4 form-group">
        <label for="symbol">Symbol</label>
        <input class="input-sharesub" id="symbol" name="symbol" value="{{ old('symbol', $currency->symbol) }}" required maxlength="5">
    </div>
    <div class="col-12 col-md-4 form-group">
        <label for="iso_numeric">ISO numeric code</label>
        <input class="input-sharesub" id="iso_numeric" name="iso_numeric" type="number" value="{{ old('iso_numeric', $currency->iso_numeric) }}" required>
    </div>
    <div class="col-12 col-md-6 form-group">
        <label for="name">Name</label>
        <input class="input-sharesub" id="name" name="name" value="{{ old('name', $currency->name) }}" required>
    </div>
    <div class="col-12 col-md-6 form-group">
        <label for="sort_order">Sort order</label>
        <input class="input-sharesub" id="sort_order" name="sort_order" type="number" value="{{ old('sort_order', $currency->sort_order ?? 0) }}">
    </div>
    <div class="col-12 col-md-4 form-group">
        <div class="filter-toggle justify-content-start">
            <div class="filter-toggle-wrapper">
                <input type="checkbox" id="is_available_in_wizard" name="is_available_in_wizard" value="1" class="checkbox-sharesub" @checked(old('is_available_in_wizard', $currency->is_available_in_wizard))>
                <label for="is_available_in_wizard" class="label-sharesub">Toggle</label>
            </div>
            <div class="filter-toggle-text">Offered in wizard as a country currency</div>
        </div>
    </div>
    <div class="col-12 col-md-4 form-group">
        <div class="filter-toggle justify-content-start">
            <div class="filter-toggle-wrapper">
                <input type="checkbox" id="always_offered_in_wizard" name="always_offered_in_wizard" value="1" class="checkbox-sharesub" @checked(old('always_offered_in_wizard', $currency->always_offered_in_wizard))>
                <label for="always_offered_in_wizard" class="label-sharesub">Toggle</label>
            </div>
            <div class="filter-toggle-text">Always offered in wizard</div>
        </div>
    </div>
    <div class="col-12 col-md-4 form-group">
        <div class="filter-toggle justify-content-start">
            <div class="filter-toggle-wrapper">
                <input type="checkbox" id="is_active" name="is_active" value="1" class="checkbox-sharesub" @checked(old('is_active', $currency->is_active))>
                <label for="is_active" class="label-sharesub">Toggle</label>
            </div>
            <div class="filter-toggle-text">Active</div>
        </div>
    </div>
</div>
