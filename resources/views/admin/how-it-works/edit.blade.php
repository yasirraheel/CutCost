<section class="card-sharesub p-4">
    @if (session('status'))
        <div class="alert alert-success" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
    @endif
    <form method="POST" action="{{ route('admin.how-it-works.update', $step) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('admin.how-it-works.form')
        <button type="submit" class="btn-sharesub bleu mx-auto my-2 d-block">Save step</button>
    </form>
    <div class="text-center mt-3"><a href="{{ route('admin.how-it-works.index') }}">Back to steps</a></div>
</section>
