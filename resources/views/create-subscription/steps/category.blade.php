<form method="POST" action="{{ route('user.create-subscription.step') }}" id="category-step-form">
    @csrf
    <input type="hidden" name="step" value="category">
    <div class="row w-100">
        <div class="col-12 col-xl-10">
            <div class="subscription-category">
                <p class="subscription-category-title">Select a category for your subscription</p>
                <div class="row">
                    @foreach ($categories as $category)
                        <div class="col-6 col-md-3">
                            <label class="subscription-category-item{{ (int) old('category_id', $offer->category_id) === (int) $category->id ? ' selected' : '' }}">
                                <input type="radio" name="category_id" value="{{ $category->id }}" class="d-none" {{ (int) old('category_id', $offer->category_id) === (int) $category->id ? 'checked' : '' }} required>
                                <div class="subscription-category-item_icon">{!! $category->icon_svg !!}</div>
                                <span class="subscription-category-item_title">{{ $category->name }}</span>
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</form>
<script>
    (function () {
        var form = document.getElementById('category-step-form');
        if (!form) return;
        form.addEventListener('change', function (event) {
            if (event.target && event.target.name === 'category_id') {
                form.submit();
            }
        });
        // Clicking the already-selected tile fires no change event, so
        // advance here. The guard skips the synthetic click on the radio
        // itself, which would double-submit with the change handler.
        form.addEventListener('click', function (event) {
            var label = event.target.closest ? event.target.closest('label') : null;
            if (!label) return;
            var radio = label.querySelector('input[name="category_id"]');
            if (radio && event.target !== radio && radio.checked) {
                form.submit();
            }
        });
    })();
</script>
