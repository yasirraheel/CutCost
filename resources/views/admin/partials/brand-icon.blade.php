{{-- Brand logo or icon for an admin list icon slot. Same logic as
CapturedPageController::brandIconHtml(): logo_path wins (img, same inline
sizing as the brands list already uses), else the stored icon_svg rendered
as-is (the homepage renders it raw at its stored 24x24 size), else the
fallback emoji passed in. --}}
@if ($brand?->logo_path)
    <img src="{{ str_starts_with($brand->logo_path, '/') || str_starts_with($brand->logo_path, 'http') ? asset($brand->logo_path) : asset('storage/'.$brand->logo_path) }}" alt="" style="max-height: 40px; max-width: 60px; vertical-align: middle;">
@elseif ($brand?->icon_svg)
    {!! $brand->icon_svg !!}
@else
    {{ $fallback ?? '' }}
@endif
