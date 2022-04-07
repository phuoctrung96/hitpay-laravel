<div class="media">
    <div class="media-body">
        <p class="small mb-1">{{ $item['created_at']->format('Y-m-d, h:i:s A') }}</p>
        @if ($item['amount'] < 0)
            <span class="float-right font-weight-bold text-danger">- {{ getFormattedAmount($item['currency'], abs($item['amount']))  }}</span>
        @else
            <span class="float-right font-weight-bold text-success">{{ getFormattedAmount($item['currency'], $item['amount'])  }}</span>
        @endif
        @if ($item['charge'] instanceof \App\Business\Charge)
            <p class="font-weight-bold mb-1">{{ $item['description'] }}</p>
            <p class="small mb-0">
                <a href="{{ route('dashboard.business.charge.show', [
                    $business->getKey(),
                    $item['charge']->id,
                ]) }}" target="_blank">View Charge</a>
            </p>
        @else
            <p class="font-weight-bold mb-0">{{ $item['description'] }}</p>
        @endif
    </div>
</div>
