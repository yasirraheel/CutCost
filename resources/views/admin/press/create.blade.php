<section class="card-sharesub p-4">
    @if (session('status'))
        <div class="alert alert-success" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
    @endif
    <form method="POST" action="{{ route('admin.press.store') }}" enctype="multipart/form-data">
        @csrf
        @include('admin.press.form')
        <button type="submit" class="btn-sharesub bleu mx-auto my-2 d-block">Save press item</button>
    </form>
    <div class="text-center mt-3"><a href="{{ route('admin.press.index') }}">Back to press</a></div>
</section>
