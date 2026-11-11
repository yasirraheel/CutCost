<form method="POST" action="{{ route('user.create-subscription.step') }}">
    @csrf
    <input type="hidden" name="step" value="position">
    <p class="text-muted text-sm">Add the position where nearby members can find your subscription. Enter the latitude and longitude, or keep the default position.</p>
    <div class="row">
        <div class="col-12 col-md-4">
            <label for="lat">Latitude</label>
            <input type="number" name="lat" id="lat" value="{{ old('lat', $offer->lat ?? $defaultLat) }}" step="any" min="-90" max="90" class="form-control" required>
        </div>
        <div class="col-12 col-md-4 mt-2 mt-md-0">
            <label for="lng">Longitude</label>
            <input type="number" name="lng" id="lng" value="{{ old('lng', $offer->lng ?? $defaultLng) }}" step="any" min="-180" max="180" class="form-control" required>
        </div>
    </div>
    <div class="mt-3">
        <button type="submit" class="btn btn-sharesub green">Continue</button>
    </div>
</form>
