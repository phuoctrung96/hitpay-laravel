<li class="mb-3">
    <div class="float-right">
        @if ($rate > 0)
            {{ $rate_display }}
        @else
            <span class="badge badge-danger font-weight-normal">Free!</span>
        @endif
    </div>
    <span class="text-dark">{{ $country_name }}</span>
    <p class="text-muted small mb-0">({{ $name }}, {{ $calculation_name }})</p>
    @if ($description)
        <p class="text-black-50 small mb-0">{{ $description }}</p>
    @endif
</li>
