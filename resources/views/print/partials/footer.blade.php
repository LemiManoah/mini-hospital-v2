<div class="footer">
    Generated on {{ $printedAt->format('d M Y H:i') }}
    @if(filled($printedBy ?? null))
        by {{ $printedBy }}
    @endif
</div>
