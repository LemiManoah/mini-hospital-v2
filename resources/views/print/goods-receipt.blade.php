@extends('print.layouts.document')

@section('title', 'Goods Receipt')

@section('content')
    @include('print.partials.header', [
        'facilityName' => $goodsReceipt->branch?->name ?? config('app.name'),
        'documentTitle' => 'Goods Receipt',
        'branchCode' => $goodsReceipt->branch?->branch_code,
    ])

    <table class="meta-grid section">
        <tr>
            <td>
                <span class="label">Receipt Number</span>
                <span class="value">{{ $goodsReceipt->receipt_number }}</span>
            </td>
            <td>
                <span class="label">Status</span>
                <span class="status-chip">{{ $goodsReceipt->status?->label() ?? 'Unknown' }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">Purchase Order</span>
                <span class="value">{{ $goodsReceipt->purchaseOrder?->order_number ?? 'N/A' }}</span>
            </td>
            <td>
                <span class="label">Supplier</span>
                <span class="value">{{ $goodsReceipt->purchaseOrder?->supplier?->name ?? 'N/A' }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">Receipt Date</span>
                <span class="value">{{ $goodsReceipt->receipt_date?->format('d M Y') ?? 'N/A' }}</span>
            </td>
            <td>
                <span class="label">Receiving Location</span>
                <span class="value">
                    {{ $goodsReceipt->inventoryLocation?->name ?? 'N/A' }}
                    @if($goodsReceipt->inventoryLocation?->location_code)
                        ({{ $goodsReceipt->inventoryLocation->location_code }})
                    @endif
                </span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">Supplier Invoice Number</span>
                <span class="value">{{ $goodsReceipt->supplier_invoice_number ?? 'N/A' }}</span>
            </td>
            <td>
                <span class="label">Posted At</span>
                <span class="value">{{ $goodsReceipt->posted_at?->format('d M Y H:i') ?? 'Not posted' }}</span>
            </td>
        </tr>
    </table>

    <div class="section">
        <h2 class="section-title">Received Items</h2>
        <table class="table-grid">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Qty Received</th>
                    <th>Ordered Qty</th>
                    <th>Unit Cost</th>
                    <th>Batch</th>
                    <th>Expiry Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($goodsReceipt->items as $item)
                    <tr>
                        <td>{{ $item->inventoryItem?->generic_name ?: $item->inventoryItem?->name ?: 'Inventory item' }}</td>
                        <td>{{ number_format((float) $item->quantity_received, 3) }}</td>
                        <td>{{ number_format((float) ($item->purchaseOrderItem?->quantity_ordered ?? 0), 3) }}</td>
                        <td>{{ number_format((float) $item->unit_cost, 2) }}</td>
                        <td>{{ $item->batch_number ?? '-' }}</td>
                        <td>{{ $item->expiry_date?->format('d M Y') ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">No goods receipt items available.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($goodsReceipt->notes)
        <div class="section">
            <h2 class="section-title">Notes</h2>
            <div class="note-box">{{ $goodsReceipt->notes }}</div>
        </div>
    @endif

    @include('print.partials.footer', [
        'printedAt' => $printedAt,
    ])
@endsection
