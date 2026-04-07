@extends('print.layouts.document')

@section('title', 'Inventory Requisition')

@section('styles')
    .issue-history {
        margin-top: 6px;
        font-size: 10px;
        color: #475569;
    }
@endsection

@section('content')
    @include('print.partials.header', [
        'facilityName' => $requisition->branch?->name ?? config('app.name'),
        'documentTitle' => 'Inventory Requisition',
        'branchCode' => $requisition->branch?->branch_code,
    ])

    <table class="meta-grid section">
        <tr>
            <td>
                <span class="label">Requisition Number</span>
                <span class="value">{{ $requisition->requisition_number }}</span>
            </td>
            <td>
                <span class="label">Status</span>
                <span class="status-chip">{{ $requisition->status?->label() ?? 'Unknown' }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">Requesting Unit</span>
                <span class="value">
                    {{ $requisition->requestingLocation?->name ?? 'N/A' }}
                    @if($requisition->requestingLocation?->location_code)
                        ({{ $requisition->requestingLocation->location_code }})
                    @endif
                </span>
            </td>
            <td>
                <span class="label">Fulfilling Store</span>
                <span class="value">
                    {{ $requisition->fulfillingLocation?->name ?? 'N/A' }}
                    @if($requisition->fulfillingLocation?->location_code)
                        ({{ $requisition->fulfillingLocation->location_code }})
                    @endif
                </span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">Priority</span>
                <span class="value">{{ $requisition->priority?->label() ?? 'Routine' }}</span>
            </td>
            <td>
                <span class="label">Requisition Date</span>
                <span class="value">{{ $requisition->requisition_date?->format('d M Y') ?? 'N/A' }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">Submitted At</span>
                <span class="value">{{ $requisition->submitted_at?->format('d M Y H:i') ?? 'Not submitted' }}</span>
            </td>
            <td>
                <span class="label">Issued At</span>
                <span class="value">{{ $requisition->issued_at?->format('d M Y H:i') ?? 'Not issued' }}</span>
            </td>
        </tr>
    </table>

    <div class="section">
        <h2 class="section-title">Requested Items</h2>
        <table class="table-grid">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Requested</th>
                    <th>Approved</th>
                    <th>Issued</th>
                    <th>Remaining</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requisition->items as $item)
                    <tr>
                        <td>
                            <strong>{{ $item->inventoryItem?->generic_name ?: $item->inventoryItem?->name ?: 'Inventory item' }}</strong>
                            @if($item->inventoryItem?->generic_name && $item->inventoryItem?->name)
                                <div class="muted">{{ $item->inventoryItem->name }}</div>
                            @endif
                            @foreach($issueHistory[$item->id] ?? [] as $issue)
                                <div class="issue-history">
                                    Batch {{ $issue['batch_number'] ?? 'N/A' }}:
                                    {{ number_format((float) $issue['quantity'], 3) }}
                                    @if($issue['expiry_date'])
                                        | Exp {{ \Illuminate\Support\Carbon::parse($issue['expiry_date'])->format('d M Y') }}
                                    @endif
                                </div>
                            @endforeach
                        </td>
                        <td>{{ number_format((float) $item->requested_quantity, 3) }}</td>
                        <td>{{ number_format((float) $item->approved_quantity, 3) }}</td>
                        <td>{{ number_format((float) $item->issued_quantity, 3) }}</td>
                        <td>{{ number_format($item->remainingApprovedQuantity(), 3) }}</td>
                        <td>{{ $item->notes ?: '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">No requisition lines available.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($requisition->notes)
        <div class="section">
            <h2 class="section-title">Requester Notes</h2>
            <div class="note-box">{{ $requisition->notes }}</div>
        </div>
    @endif

    @if($requisition->approval_notes)
        <div class="section">
            <h2 class="section-title">Approval Notes</h2>
            <div class="note-box">{{ $requisition->approval_notes }}</div>
        </div>
    @endif

    @if($requisition->rejection_reason)
        <div class="section">
            <h2 class="section-title">Rejection Reason</h2>
            <div class="note-box">{{ $requisition->rejection_reason }}</div>
        </div>
    @endif

    @if($requisition->cancellation_reason)
        <div class="section">
            <h2 class="section-title">Cancellation Reason</h2>
            <div class="note-box">{{ $requisition->cancellation_reason }}</div>
        </div>
    @endif

    @if($requisition->issued_notes)
        <div class="section">
            <h2 class="section-title">Issue Notes</h2>
            <div class="note-box">{{ $requisition->issued_notes }}</div>
        </div>
    @endif

    @include('print.partials.footer', [
        'printedAt' => $printedAt,
    ])
@endsection
