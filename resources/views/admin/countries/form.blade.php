<div class="row">
    <div class="col-12 col-md-6 form-group">
        <label for="code">Code <span class="text-muted">(2 letters, for example GB)</span></label>
        <input class="input-sharesub" id="code" name="code" value="{{ old('code', $country->code) }}" required maxlength="2">
    </div>
    <div class="col-12 col-md-6 form-group">
        <label for="name">Name</label>
        <input class="input-sharesub" id="name" name="name" value="{{ old('name', $country->name) }}" required>
    </div>
    <div class="col-12 col-md-6 form-group">
        <label for="fee_flat">Flat fee</label>
        <input class="input-sharesub" id="fee_flat" name="fee_flat" type="number" step="0.001" value="{{ old('fee_flat', $country->fee_flat ?? 0) }}">
    </div>
    <div class="col-12 col-md-6 form-group">
        <label for="fee_percent">Fee percent <span class="text-muted">(for example 0.080)</span></label>
        <input class="input-sharesub" id="fee_percent" name="fee_percent" type="number" step="0.001" value="{{ old('fee_percent', $country->fee_percent ?? 0) }}">
    </div>
    <div class="col-12 col-md-6 form-group">
        <label for="currency">Currency</label>
        <select class="input-sharesub" id="currency" name="currency">
            @foreach ($currencies as $currencyOption)
                <option value="{{ $currencyOption->code }}" @selected(old('currency', $country->currency) === $currencyOption->code)>{{ $currencyOption->code }} ({{ $currencyOption->symbol }}) {{ $currencyOption->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12 col-md-6 form-group">
        <label for="sort_order">Sort order</label>
        <input class="input-sharesub" id="sort_order" name="sort_order" type="number" value="{{ old('sort_order', $country->sort_order ?? 0) }}">
    </div>
    <div class="col-12 col-md-6 form-group">
        <label for="postal_regex">Postal code pattern</label>
        <input class="input-sharesub" id="postal_regex" name="postal_regex" value="{{ old('postal_regex', $country->postal_regex) }}">
    </div>
    <div class="col-12 col-md-6 form-group">
        <label for="postal_example">Postal code example</label>
        <input class="input-sharesub" id="postal_example" name="postal_example" value="{{ old('postal_example', $country->postal_example) }}">
    </div>
    <div class="col-12 col-md-6 form-group">
        <div class="filter-toggle justify-content-start">
            <div class="filter-toggle-wrapper">
                <input type="checkbox" id="is_active" name="is_active" value="1" class="checkbox-sharesub" @checked(old('is_active', $country->is_active))>
                <label for="is_active" class="label-sharesub">Toggle</label>
            </div>
            <div class="filter-toggle-text">Active</div>
        </div>
    </div>
</div>
