<div class="d-flex align-items-center justify-content-md-end" style="gap: 8px;">
    @foreach ($extraLinks ?? [] as $extraLink)
        <a class="btn btn-light btn-sm" href="{{ $extraLink['url'] }}">{{ $extraLink['label'] }}</a>
    @endforeach
    <a class="btn btn-light btn-sm" href="{{ $editUrl }}">{{ $label ?? 'Edit' }}</a>
    @if ($deleteUrl ?? null)
        <form class="d-inline m-0" method="POST" action="{{ $deleteUrl }}" onsubmit="return confirm('{{ $deleteConfirm ?? 'Delete this item permanently?' }}');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn-sharesub red outline small">Delete</button>
        </form>
    @endif
</div>
