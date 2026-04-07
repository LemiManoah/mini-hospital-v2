<div class="header">
    <h1>{{ $facilityName ?? config('app.name') }}</h1>
    <p>{{ $documentTitle }}</p>
    @if(filled($branchCode ?? null))
        <p>Branch Code: {{ $branchCode }}</p>
    @endif
</div>
