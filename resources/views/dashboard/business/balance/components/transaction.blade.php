<div class="media">
    <div class="media-body">
        <p class="small mb-1">{{ $item->created_at->format('Y-m-d, h:i:s A') }} -
            <a href="{{ route('dashboard.business.balance.wallet', [
                $item->wallet->business->id,
                $item->wallet->currency,
                $item->wallet->type,
            ]) }}">{{ strtoupper($item->wallet->currency) }} @ {{ ucfirst($item->wallet->type) }}</a>
        </p>
        @if ($item->amount < 0)
            <span class="float-right font-weight-bold text-danger">- {{ getFormattedAmount($item->wallet->currency, abs($item->amount))  }}</span>
        @else
            <span class="float-right font-weight-bold text-success">{{ getFormattedAmount($item->wallet->currency, $item->amount)  }}</span>
        @endif
        @if ($buttonText = $item->getRelatableButtonText())
            <p class="font-weight-bold mb-1">{{ $item->description }}</p>
            <p class="small mb-0">
                <a href="{{ $item->getRelatableUrl() }}" target="_blank">{{ $buttonText }}</a>
            </p>
        @else
            <p class="font-weight-bold mb-0">{{ $item->description }}</p>
        @endif
    </div>
</div>
