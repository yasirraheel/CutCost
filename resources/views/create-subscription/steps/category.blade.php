<form method="POST" action="{{ route('user.create-subscription.step') }}">
    @csrf
    <input type="hidden" name="step" value="category">
    <ul class="dashboard-profile-steps mt-3">
        @forelse ($categories as $category)
            <li class="row align-items-center">
                <div class="col-1">
                    <input type="radio" name="category_id" id="category-{{ $category->id }}" value="{{ $category->id }}" {{ (int) old('category_id', $offer->category_id) === (int) $category->id ? 'checked' : '' }} required>
                </div>
                <div class="col">
                    <label for="category-{{ $category->id }}">{!! $category->icon_svg !!} {{ $category->name }}</label>
                </div>
            </li>
        @empty
            <li class="text-muted">No category is available.</li>
        @endforelse
    </ul>
    <div class="mt-3">
        <button type="submit" class="btn btn-sharesub green">Continue</button>
    </div>
</form>
