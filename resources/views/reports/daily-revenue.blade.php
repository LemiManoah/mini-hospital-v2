@extends('print.layouts.document')

@section('title', 'Daily Revenue Report')

@section('styles')
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

    .summary-sub {
        margin-top: 4px;
        color: #64748b;
        font-size: 11px;
    }

    .metric-list div {
        margin-bottom: 4px;
        font-size: 11px;
    }

    .money-negative {
        color: #991b1b;
    }

    .empty-state {
        border: 1px dashed #cbd5e1;
        padding: 18px;
        color: #64748b;
        background: #f8fafc;
    }

    .align-right {
        text-align: right;
    }

    tr { page-break-inside: avoid; }
    thead { display: table-header-group; }
@endsection

@section('content')
    @php
        $formatMoney = static fn (float $value): string => ($currency ?? 'UGX').' '.number_format($value, 2);
    @endphp

    @include('print.partials.header', [
        'facilityName' => $facilityName,
        'documentTitle' => $reportTitle,
    ])

    <table class="meta-grid section">
        <tr>
            <td>
                <span class="label">Report Date</span>
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
                    <span class="summary-label">Total Collected</span>
                    <div class="summary-value">{{ $formatMoney($total_amount) }}</div>
                    <div class="summary-sub">{{ $total_count }} transaction(s)</div>
                </td>
                <td>
                    <span class="summary-label">Refunds</span>
                    <div class="summary-value money-negative">{{ $formatMoney($refund_amount) }}</div>
                </td>
                <td>
                    <span class="summary-label">Net Revenue</span>
                    <div class="summary-value">{{ $formatMoney($net_amount) }}</div>
                </td>
                <td>
                    <span class="summary-label">By Payment Method</span>
                    <div class="metric-list">
                        @foreach($by_method as $method => $amount)
                            <div>{{ str($method)->replace('_', ' ')->title() }}: {{ $formatMoney($amount) }}</div>
                        @endforeach
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h2 class="section-title">Transaction Detail</h2>

        @if($rows->isNotEmpty())
            <table class="table-grid">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Receipt No.</th>
                        <th>Patient</th>
                        <th>Visit No.</th>
                        <th>Method</th>
                        <th>Reference</th>
                        <th class="align-right">Amount</th>
                        <th>Time</th>
                        <th>Type</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $index => $payment)
                        @php
                            $patientName = trim(
                                ($payment->visit?->patient?->first_name ?? '').' '.
                                ($payment->visit?->patient?->middle_name ?? '').' '.
                                ($payment->visit?->patient?->last_name ?? '')
                            ) ?: 'Unknown patient';
                        @endphp
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $payment->receipt_number ?? 'Not issued' }}</td>
                            <td>
                                <div>{{ $patientName }}</div>
                                <div class="muted">{{ $payment->visit?->patient?->patient_number ?? 'No patient number' }}</div>
                            </td>
                            <td>{{ $payment->visit?->visit_number ?? 'Not linked' }}</td>
                            <td>{{ str($payment->payment_method ?? 'unknown')->replace('_', ' ')->title() }}</td>
                            <td>{{ $payment->reference_number ?? 'None' }}</td>
                            <td class="align-right">{{ $formatMoney((float) $payment->amount) }}</td>
                            <td>{{ $payment->payment_date?->format('H:i') ?? 'Unknown' }}</td>
                            <td>
                                <span class="status-chip {{ $payment->is_refund ? 'money-negative' : '' }}">
                                    {{ $payment->is_refund ? 'Refund' : 'Payment' }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="empty-state">No payments were recorded for the selected date.</div>
        @endif
    </div>

    @include('print.partials.footer', [
        'printedAt' => now(),
        'printedBy' => $generatedBy,
    ])
@endsection
