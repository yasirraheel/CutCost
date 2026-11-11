@if ($offer->brand_id !== null)
    <form method="POST" action="{{ route('user.create-subscription.step') }}">
        @csrf
        <input type="hidden" name="step" value="template">
        <ul class="dashboard-profile-steps mt-3">
            @forelse ($templates as $template)
                <li class="row align-items-center">
                    <div class="col-1">
                        <input type="radio" name="template_id" id="template-{{ $template->id }}" value="{{ $template->id }}" {{ (int) old('template_id', $offer->template_id) === (int) $template->id ? 'checked' : '' }}>
                    </div>
                    <div class="col">
                        <label for="template-{{ $template->id }}"><strong>{{ $template->name }}</strong></label>
                        <div class="text-muted text-sm">
                            Up to {{ min($template->shareable_seats ?? 1, ($template->seats ?? 2) - 1) + 1 }} users
                            @if ($template->retail_price !== null)
                                · {{ $template->currency === 'EUR' ? '€' : '£' }}{{ number_format((float) $template->retail_price, 2) }} / month
                            @endif
                        </div>
                    </div>
                </li>
            @empty
                <li class="text-muted">This brand has no plan listed yet. Continue without a plan to share it as a private subscription.</li>
            @endforelse
            @if ($templates->isEmpty())
                <li class="row align-items-center">
                    <div class="col-1">
                        <input type="radio" name="template_id" id="template-none" value="" {{ old('template_id', $offer->template_id) === null ? 'checked' : '' }}>
                    </div>
                    <div class="col">
                        <label for="template-none"><strong>No plan</strong></label>
                    </div>
                </li>
            @endif
        </ul>
        <div class="mt-3">
            <button type="submit" class="btn btn-sharesub green">Continue</button>
        </div>
    </form>
@else
    <form method="POST" action="{{ route('user.create-subscription.step') }}">
        @csrf
        <input type="hidden" name="step" value="template">
        <div class="row">
            <div class="col-12 col-md-8">
                <label for="name">Name of your subscription</label>
                <input type="text" name="name" id="name" value="{{ old('name', $offer->name) }}" maxlength="80" class="form-control" required>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-12 col-md-8">
                <label for="description">Description</label>
                <textarea name="description" id="description" cols="30" rows="4" class="form-control">{{ old('description', $offer->description) }}</textarea>
            </div>
        </div>
        <div class="mt-3">
            <button type="submit" class="btn btn-sharesub green">Continue</button>
        </div>
    </form>
@endif
