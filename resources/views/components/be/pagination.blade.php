@props([
    'paginator',
    'label' => null,
])

@if($paginator->hasPages())
    <div {{ $attributes->merge(['class' => 'be-pagination']) }}>
        <div class="be-pagination__meta">
            {{ $label ?? 'PAGE '.$paginator->currentPage().' / '.$paginator->lastPage() }}
        </div>
        <div class="be-pagination__actions">
            @if($paginator->onFirstPage())
                <span class="be-btn be-btn--neutral be-btn--md is-disabled">Prev</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="be-btn be-btn--neutral be-btn--md">Prev</a>
            @endif

            @if($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="be-btn be-btn--primary be-btn--md">Next</a>
            @else
                <span class="be-btn be-btn--primary be-btn--md is-disabled">Next</span>
            @endif
        </div>
    </div>
@endif
