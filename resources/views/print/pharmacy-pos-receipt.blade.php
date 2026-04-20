@extends('print.layouts.document')

@section('title', 'POS Receipt')

@section('content')
    @php
        $currencySymbol = $sale->branch?->currency?->symbol ?? ($sale->branch?->currency?->code ?? 'UGX');
        $formatMoney = static fn ($amount): string => $currencySymbol . ' ' . number_format((float) ($amount ?? 0), 2);
    @endphp

    @include('print.partials.header', [
        'facilityName' => $sale->branch?->name ?? config('app.name'),
        'documentTitle' => 'Pharmacy POS Receipt',
        'branchCode' => $sale->branch?->branch_code,
    ])

    <table class="meta-grid section">
        <tr>
            <td>
                <span class="label">Sale Number</span>
                <span class="value">{{ $sale->sale_number }}</span>
            </td>
            <td>
                <span class="label">Date</span>
                <span class="value">{{ $sale->sold_at?->format('d M Y H:i') ?? now()->format('d M Y H:i') }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">Customer</span>
                <span class="value">{{ $sale->customer_name ?? 'Walk-in Customer' }}</span>
            </td>
            <td>
                <span class="label">Location</span>
                <span class="value">{{ $sale->inventoryLocation?->name ?? 'N/A' }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">Served By</span>
                <span class="value">{{ $sale->createdBy?->name ?? 'N/A' }}</span>
            </td>
            <td>
                <span class="label">Status</span>
                <span class="value">{{ $sale->status?->label() ?? 'N/A' }}</span>
            </td>
        </tr>
    </table>

    <div class="section">
        <h2 class="section-title">Items</h2>
        <table class="table-grid">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sale->items as $item)
                    <tr>
                        <td>{{ $item->inventoryItem?->name ?? 'Unknown' }}</td>
                        <td>{{ number_format((float) $item->quantity, 0) }}</td>
                        <td>{{ $formatMoney($item->unit_price) }}</td>
                        <td>{{ $formatMoney($item->line_total) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2 class="section-title">Summary</h2>
        <table class="table-grid">
            <tbody>
                <tr>
                    <td>Subtotal</td>
                    <td>{{ $formatMoney($sale->gross_amount) }}</td>
                </tr>
                @if ((float) $sale->discount_amount > 0)
                    <tr>
                        <td>Discount</td>
                        <td>- {{ $formatMoney($sale->discount_amount) }}</td>
                    </tr>
                @endif
                <tr>
                    <td><strong>Total</strong></td>
                    <td><strong>{{ $formatMoney((float) $sale->gross_amount - (float) $sale->discount_amount) }}</strong></td>
                </tr>
                <tr>
                    <td>Amount Paid</td>
                    <td>{{ $formatMoney($sale->paid_amount) }}</td>
                </tr>
                @if ((float) $sale->change_amount > 0)
                    <tr>
                        <td>Change</td>
                        <td>{{ $formatMoney($sale->change_amount) }}</td>
                    </tr>
                @endif
                @if ((float) $sale->balance_amount > 0)
                    <tr>
                        <td>Balance Due</td>
                        <td>{{ $formatMoney($sale->balance_amount) }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    @if ($sale->payments->isNotEmpty())
        <div class="section">
            <h2 class="section-title">Payments</h2>
            <table class="table-grid">
                <thead>
                    <tr>
                        <th>Method</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sale->payments as $payment)
                        <tr>
                            <td>{{ str($payment->payment_method)->replace('_', ' ')->title() }}</td>
                            <td>{{ $formatMoney($payment->amount) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @include('print.partials.footer', [
        'printedAt' => $printedAt,
    ])
@endsection
