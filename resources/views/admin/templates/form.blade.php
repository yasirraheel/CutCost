@php
    $selectedBrandId = (int) old('brand_id', $template->brand_id);
    $selectedCurrency = old('currency', $template->currency);
    $selectedCredentialType = (int) old('credentials_type', $template->credentials_type ?? 0);
    $selectedFrequency = (int) old('frequency', $template->frequency ?? 2);
    $selectedCostSharing = (int) old('cost_sharing', $template->cost_sharing ?? 1);
@endphp
<div class="row">
    <div class="col-12 col-md-6 form-group">
        <label for="brand_id">Brand</label>
        <select class="input-sharesub" id="brand_id" name="brand_id" required>
            <option value="">Select a brand</option>
            @if ($selectedBrandId && ! $brands->contains('id', $selectedBrandId))
                <option value="{{ $selectedBrandId }}" selected>Brand #{{ $selectedBrandId }} (no longer in brands)</option>
            @endif
            @foreach ($brands as $brand)
                <option value="{{ $brand->id }}" @selected($selectedBrandId === $brand->id)>{{ $brand->name }}</option>
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
        <select class="input-sharesub" id="currency" name="currency" required>
            @if ($selectedCurrency && ! $currencies->contains('code', $selectedCurrency))
                <option value="{{ $selectedCurrency }}" selected>{{ $selectedCurrency }} (stored value, not in currencies)</option>
            @endif
            @foreach ($currencies as $currencyOption)
                <option value="{{ $currencyOption->code }}" @selected($selectedCurrency === $currencyOption->code)>{{ $currencyOption->code }} ({{ $currencyOption->symbol }}) {{ $currencyOption->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12 col-md-4 form-group">
        <label for="credentials_type">Credentials type</label>
        <select class="input-sharesub" id="credentials_type" name="credentials_type">
            @foreach ($credentialTypes as $value => $label)
                <option value="{{ $value }}" @selected($selectedCredentialType === (int) $value)>{{ $label }}</option>
            @endforeach
            @if (! array_key_exists($selectedCredentialType, $credentialTypes))
                <option value="{{ $selectedCredentialType }}" selected>{{ $selectedCredentialType }} (stored value, type not defined in app)</option>
            @endif
        </select>
    </div>
    <div class="col-12 col-md-4 form-group">
        <label for="frequency">Billing frequency</label>
        <select class="input-sharesub" id="frequency" name="frequency">
            @foreach ($frequencies as $value => $label)
                <option value="{{ $value }}" @selected($selectedFrequency === (int) $value)>{{ $label }}</option>
            @endforeach
            @if (! array_key_exists($selectedFrequency, $frequencies))
                <option value="{{ $selectedFrequency }}" selected>{{ $selectedFrequency }} (stored value, not in the list)</option>
            @endif
        </select>
    </div>
    <div class="col-12 col-md-4 form-group">
        <label for="cost_sharing">Cost sharing</label>
        <select class="input-sharesub" id="cost_sharing" name="cost_sharing">
            @foreach ($costSharingOptions as $value => $label)
                <option value="{{ $value }}" @selected($selectedCostSharing === (int) $value)>{{ $label }}</option>
            @endforeach
            @if (! array_key_exists($selectedCostSharing, $costSharingOptions))
                <option value="{{ $selectedCostSharing }}" selected>{{ $selectedCostSharing }} (stored value, not in the list)</option>
            @endif
        </select>
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
