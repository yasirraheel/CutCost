<section class="card-sharesub p-4">
    <h3 class="dashboard-progress-title">Site settings</h3>
    @if (session('status'))
        <div class="alert alert-success" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
    @endif
    <form method="POST" action="{{ route('admin.settings.update') }}">
        @csrf
        @method('PUT')
        <div class="row">
            @foreach ($keys as $key => $meta)
                <div class="col-12 form-group">
                    <label for="settings-{{ $key }}">{{ $meta['label'] }} <span class="text-muted">({{ $key }})</span></label>
                    @if ($meta['type'] === 'textarea')
                        <textarea class="input-sharesub{{ str_ends_with($key, '_html') ? ' rich-text-editor' : '' }}" id="settings-{{ $key }}" name="settings[{{ $key }}]" rows="3">{{ old('settings.'.$key, $settings[$key] ?? '') }}</textarea>
                    @elseif ($meta['type'] === 'currency')
                        @php $currentCurrency = old('settings.'.$key, $settings[$key] ?? ''); @endphp
                        <select class="input-sharesub" id="settings-{{ $key }}" name="settings[{{ $key }}]">
                            @if (! $currencies->contains('code', $currentCurrency))
                                <option value="{{ $currentCurrency }}" selected>{{ $currentCurrency }}</option>
                            @endif
                            @foreach ($currencies as $currencyOption)
                                <option value="{{ $currencyOption->code }}" @selected($currentCurrency === $currencyOption->code)>{{ $currencyOption->code }} ({{ $currencyOption->symbol }}) {{ $currencyOption->name }}</option>
                            @endforeach
                        </select>
                    @elseif ($meta['type'] === 'locale')
                        @php $currentLocale = old('settings.'.$key, $settings[$key] ?? ''); @endphp
                        <select class="input-sharesub" id="settings-{{ $key }}" name="settings[{{ $key }}]">
                            @if (! collect($locales)->contains('code', $currentLocale))
                                <option value="{{ $currentLocale }}" selected>{{ $currentLocale }}</option>
                            @endif
                            @foreach ($locales as $localeOption)
                                <option value="{{ $localeOption['code'] }}" @selected($currentLocale === $localeOption['code'])>{{ $localeOption['label'] }} ({{ $localeOption['code'] }})</option>
                            @endforeach
                        </select>
                    @elseif ($meta['type'] === 'brand_slug')
                        @php $currentSlug = old('settings.'.$key, $settings[$key] ?? ''); @endphp
                        <select class="input-sharesub" id="settings-{{ $key }}" name="settings[{{ $key }}]">
                            @if (! $brands->contains('slug', $currentSlug))
                                <option value="{{ $currentSlug }}" selected>{{ $currentSlug }}</option>
                            @endif
                            @foreach ($brands as $brandOption)
                                <option value="{{ $brandOption->slug }}" @selected($currentSlug === $brandOption->slug)>{{ $brandOption->name }} ({{ $brandOption->slug }})</option>
                            @endforeach
                        </select>
                    @else
                        <input class="input-sharesub" id="settings-{{ $key }}" name="settings[{{ $key }}]" value="{{ old('settings.'.$key, $settings[$key] ?? '') }}">
                    @endif
                </div>
            @endforeach
        </div>
        <button type="submit" class="btn-sharesub bleu mx-auto my-2 d-block">Save settings</button>
    </form>
    @include('admin.partials.rich-text-editor')
</section>
