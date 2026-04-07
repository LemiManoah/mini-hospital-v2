@extends('print.layouts.document')

@section('title', 'Payment Receipt')

@section('content')
    @php
        $patientName = trim(($patient?->first_name ?? '').' '.($patient?->middle_name ?? '').' '.($patient?->last_name ?? '')) ?: 'Unknown patient';
        $currencyCode = $branch?->currency?->code ?? 'UGX';
        $currencySymbol = $branch?->currency?->symbol ?? $currencyCode;
        $formatMoney = static fn ($amount): string => $currencySymbol.' '.number_format((float) ($amount ?? 0), 2);
    @endphp

    @include('print.partials.header', [
        'facilityName' => $branch?->name ?? config('app.name'),
        'documentTitle' => 'Official Payment Receipt',
        'branchCode' => $branch?->branch_code,
    ])

    <table class="meta-grid section">
        <tr>
            <td>
                <span class="label">Patient</span>
                <span class="value">{{ $patientName }}</span>
            </td>
            <td>
                <span class="label">Patient Number</span>
                <span class="value">{{ $patient?->patient_number ?? 'N/A' }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">Visit Number</span>
                <span class="value">{{ $visit?->visit_number ?? 'N/A' }}</span>
            </td>
            <td>
                <span class="label">Receipt Number</span>
                <span class="value">{{ $payment->receipt_number ?? 'N/A' }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">Payment Date</span>
                <span class="value">{{ $payment->payment_date?->format('d M Y H:i') ?? 'N/A' }}</span>
            </td>
            <td>
                <span class="label">Payment Method</span>
                <span class="value">{{ $payment->payment_method ? str($payment->payment_method)->replace('_', ' ')->title() : 'N/A' }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">Reference Number</span>
                <span class="value">{{ $payment->reference_number ?? 'N/A' }}</span>
            </td>
            <td>
                <span class="label">Billing Type</span>
                <span class="value">
                    {{ $payer?->billing_type?->label() ?? 'Cash' }}
                    @if($payer?->insuranceCompany?->name)
                        - {{ $payer->insuranceCompany->name }}
                    @endif
                </span>
            </td>
        </tr>
    </table>

    <div class="section">
        <h2 class="section-title">Receipt Summary</h2>
        <table class="table-grid">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Visit payment receipt</td>
                    <td>{{ $formatMoney($payment->amount) }}</td>
                </tr>
                <tr>
                    <td>Billing paid to date</td>
                    <td>{{ $formatMoney($billing?->paid_amount) }}</td>
                </tr>
                <tr>
                    <td>Billing balance after payment</td>
                    <td>{{ $formatMoney($billing?->balance_amount) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    @if($payment->notes)
        <div class="section">
            <h2 class="section-title">Payment Notes</h2>
            <div class="note-box">{{ $payment->notes }}</div>
        </div>
    @endif

    @include('print.partials.footer', [
        'printedAt' => $printedAt,
    ])
@endsection
