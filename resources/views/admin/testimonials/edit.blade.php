<section class="card-sharesub p-4">
    @if (session('status'))
        <div class="alert alert-success" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
    @endif
    <form method="POST" action="{{ route('admin.testimonials.update', $testimonial) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('admin.testimonials.form')
        <button type="submit" class="btn-sharesub bleu mx-auto my-2 d-block">Save testimonial</button>
    </form>
    <div class="text-center mt-3"><a href="{{ route('admin.testimonials.index') }}">Back to testimonials</a></div>
</section>
