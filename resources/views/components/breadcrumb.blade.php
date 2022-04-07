<nav class="{{ $breadcrumb_class ?? 'bg-primary text-white' }} small" aria-label="breadcrumb" role="navigation">
    <div class="{{ $container_class ?? 'container' }}">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('dashboard.home') }}">@lang('Home')</a>
            </li>
            @foreach ((array) $breadcrumb_items as $item)
                @if ($loop->last)
                    <li class="breadcrumb-item active" aria-current="page">{{ $item['name'] ?? $item }}</li>
                @else
                    <li class="breadcrumb-item">
                        <a href="{{ $item['url'] }}">{{ $item['name'] }}</a>
                    </li>
                @endif
            @endforeach
        </ol>
    </div>
</nav>
