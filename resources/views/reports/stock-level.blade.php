@extends('print.layouts.document')

@section('title', 'Stock Level Report')

@section('styles')
    body {
        font-size: 11px;
    }

    .summary-grid,
    .table-grid {
        width: 100%;
        border-collapse: collapse;
    }

    .summary-grid td {
        width: 25%;
        border: 1px solid #cbd5e1;
        padding: 10px 12px;
        vertical-align: top;
        background: #f8fafc;
    }

    .summary-label {
        display: block;
        margin-bottom: 4px;
        font-size: 10px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #64748b;
    }

    .summary-value {
        font-size: 20px;
        font-weight: 700;
        color: #0f172a;
    }

    .legend {
        margin-top: 8px;
    }

    .legend span {
        display: inline-block;
        margin-right: 8px;
        margin-bottom: 6px;
    }

    .status-red { background: #fee2e2; color: #991b1b; }
    .status-orange { background: #ffedd5; color: #9a3412; }
    .status-yellow { background: #fef9c3; color: #854d0e; }
    .status-green { background: #dcfce7; color: #166534; }

    .empty-state {
        border: 1px dashed #cbd5e1;
        padding: 18px;
        color: #64748b;
        background: #f8fafc;
    }

    .align-right {
        text-align: right;
    }

    .compact-table th,
    .compact-table td {
        font-size: 10px;
        padding: 6px 7px;
    }

    tr { page-break-inside: avoid; }
    thead { display: table-header-group; }
@endsection

@section('content')
    @include('print.partials.header', [
        'facilityName' => $facilityName,
        'documentTitle' => $reportTitle,
    ])

    <table class="meta-grid section">
        <tr>
            <td>
                <span class="label">Report Snapshot</span>
                <span class="value">{{ $reportPeriod }}</span>
            </td>
            <td>
                <span class="label">Prepared By</span>
                <span class="value">{{ $generatedBy }}</span>
            </td>
        </tr>
    </table>

    @if(! empty($appliedFilters))
        <div class="section">
            <h2 class="section-title">Applied Filters</h2>
            <div class="note-box">
                @foreach($appliedFilters as $label => $value)
                    <div><strong>{{ $label }}:</strong> {{ $value }}</div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="section">
        <h2 class="section-title">Summary</h2>
        <table class="summary-grid">
            <tr>
                <td>
                    <span class="summary-label">Total Items</span>
                    <div class="summary-value">{{ $total_items }}</div>
                </td>
                <td>
                    <span class="summary-label">Low or Critical</span>
                    <div class="summary-value">{{ $low_stock_count }}</div>
                </td>
                <td>
                    <span class="summary-label">Out of Stock</span>
                    <div class="summary-value">{{ $out_of_stock_count }}</div>
                </td>
                <td>
                    <span class="summary-label">Adequate</span>
                    <div class="summary-value">{{ $total_items - $low_stock_count - $out_of_stock_count }}</div>
                </td>
            </tr>
        </table>

        <div class="legend">
            <span class="status-chip status-red">Out of Stock</span>
            <span class="status-chip status-orange">Critical</span>
            <span class="status-chip status-yellow">Low</span>
            <span class="status-chip status-green">Adequate</span>
        </div>
    </div>

    <div class="section">
        <h2 class="section-title">Item Stock Levels</h2>

        @if($rows->isNotEmpty())
            <table class="table-grid compact-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Item Name</th>
                        <th>Dosage / Form</th>
                        <th>Location</th>
                        <th class="align-right">Min. Level</th>
                        <th class="align-right">Reorder Level</th>
                        <th class="align-right">Current Qty</th>
                        <th>Unit</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $index => $row)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $row['item_name'] }}</td>
                            <td>{{ $row['dosage_info'] !== '' ? $row['dosage_info'] : '-' }}</td>
                            <td>{{ $row['location_name'] }} ({{ $row['location_code'] }})</td>
                            <td class="align-right">{{ number_format($row['minimum_stock_level'], 0) }}</td>
                            <td class="align-right">{{ number_format($row['reorder_level'], 0) }}</td>
                            <td class="align-right">{{ number_format($row['quantity'], 2) }}</td>
                            <td>{{ $row['unit'] ?? '-' }}</td>
                            <td>
                                @if($row['status'] === 'out_of_stock')
                                    <span class="status-chip status-red">Out of Stock</span>
                                @elseif($row['status'] === 'critical')
                                    <span class="status-chip status-orange">Critical</span>
                                @elseif($row['status'] === 'low')
                                    <span class="status-chip status-yellow">Low</span>
                                @else
                                    <span class="status-chip status-green">Adequate</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="empty-state">No stock data was found for the active branch.</div>
        @endif
    </div>

    @include('print.partials.footer', [
        'printedAt' => now(),
        'printedBy' => $generatedBy,
    ])
@endsection
