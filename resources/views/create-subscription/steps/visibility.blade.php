<form method="POST" action="{{ route('user.create-subscription.step') }}">
    @csrf
    <input type="hidden" name="step" value="visibility">
    <ul class="dashboard-profile-steps mt-3">
        <li class="row align-items-center">
            <div class="col-1">
                <input type="radio" name="visibility" id="visibility-public" value="1" {{ (int) old('visibility', $offer->visibility) === 1 ? 'checked' : '' }} required>
            </div>
            <div class="col">
                <label for="visibility-public"><strong>Public</strong></label>
                <div class="text-muted text-sm">Anyone can find and join your subscription.</div>
            </div>
        </li>
        <li class="row align-items-center">
            <div class="col-1">
                <input type="radio" name="visibility" id="visibility-nearby" value="2" {{ (int) old('visibility', $offer->visibility) === 2 ? 'checked' : '' }}>
            </div>
            <div class="col">
                <label for="visibility-nearby"><strong>Nearby</strong></label>
                <div class="text-muted text-sm">Only people near your position can join.</div>
            </div>
        </li>
        <li class="row align-items-center">
            <div class="col-1">
                <input type="radio" name="visibility" id="visibility-private" value="0" {{ old('visibility', $offer->visibility) !== null && (int) old('visibility', $offer->visibility) === 0 ? 'checked' : '' }}>
            </div>
            <div class="col">
                <label for="visibility-private"><strong>Private</strong></label>
                <div class="text-muted text-sm">Only people you invite can join.</div>
            </div>
        </li>
    </ul>
    <div class="row mt-3">
        <div class="col-12">
            <label>
                <input type="checkbox" name="confirm_private" value="1" {{ old('confirm_private') ? 'checked' : '' }}>
                I understand a private subscription stays hidden until I invite someone.
            </label>
            <div class="text-muted text-sm">Tick this box only if you chose Private above.</div>
        </div>
    </div>
    <div class="mt-3">
        <button type="submit" class="btn btn-sharesub green">Continue</button>
    </div>
</form>
