<section class="card-sharesub p-4">
    @if (session('status'))
        <div class="alert alert-success" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
    @endif
    <form method="POST" action="{{ route('admin.countries.update', $country) }}">
        @csrf
        @method('PUT')
        @include('admin.countries.form')
        <button type="submit" class="btn-sharesub bleu mx-auto my-2 d-block">Save country</button>
    </form>
    <div class="text-center mt-3"><a href="{{ route('admin.countries.index') }}">Back to countries</a></div>
</section>
