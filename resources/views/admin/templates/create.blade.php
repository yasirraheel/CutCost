<section class="card-sharesub p-4">
    @if (session('status'))
        <div class="alert alert-success" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
    @endif
    <form method="POST" action="{{ route('admin.templates.store') }}">
        @csrf
        @include('admin.templates.form')
        <button type="submit" class="btn-sharesub bleu mx-auto my-2 d-block">Save template</button>
    </form>
    <div class="text-center mt-3"><a href="{{ route('admin.templates.index') }}">Back to templates</a></div>
</section>
