@extends('print.layouts.document')

@section('title', 'Dispense Slip')

@section('content')
    @php
        $patientName = trim(($patient?->first_name ?? '').' '.($patient?->middle_name ?? '').' '.($patient?->last_name ?? '')) ?: 'Unknown patient';
    @endphp

    @include('print.partials.header', [
        'facilityName' => $visit?->branch?->name ?? config('app.name'),
        'documentTitle' => 'Dispense Slip',
        'branchCode' => $visit?->branch?->branch_code,
    ])

    <table class="meta-grid section">
        <tr>
            <td>
                <span class="label">Dispense Number</span>
                <span class="value">{{ $dispensingRecord->dispense_number }}</span>
            </td>
            <td>
                <span class="label">Status</span>
                <span class="status-chip">{{ $dispensingRecord->status?->label() ?? 'Unknown' }}</span>
            </td>
        </tr>
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
                <span class="label">Dispensing Location</span>
                <span class="value">{{ $dispensingRecord->inventoryLocation?->name ?? 'N/A' }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">Dispensed At</span>
                <span class="value">{{ $dispensingRecord->dispensed_at?->format('d M Y H:i') ?? 'N/A' }}</span>
            </td>
            <td>
                <span class="label">Dispensed By</span>
                <span class="value">{{ $dispenserName }}</span>
            </td>
        </tr>
        @if($dispensingRecord->prescription?->primary_diagnosis)
            <tr>
                <td colspan="2">
                    <span class="label">Diagnosis</span>
                    <span class="value">{{ $dispensingRecord->prescription->primary_diagnosis }}</span>
                </td>
            </tr>
        @endif
    </table>

    <div class="section">
        <h2 class="section-title">Dispensed Items</h2>
        <table class="table-grid">
            <thead>
                <tr>
                    <th>Medication</th>
                    <th>Prescribed Qty</th>
                    <th>Dispensed Qty</th>
                    <th>Balance</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($dispensingRecord->items as $item)
                    @php
                        $inventoryItem = $item->inventoryItem;
                        $itemName = $inventoryItem?->generic_name ?? $inventoryItem?->name ?? 'Medication';
                        $descriptor = collect([
                            $inventoryItem?->brand_name,
                            $inventoryItem?->strength,
                            $inventoryItem?->dosage_form?->value,
                        ])->filter()->implode(' | ');
                        $pItem = $item->prescriptionItem;
                        $directions = collect([
                            $pItem?->dosage,
                            $pItem?->frequency,
                            $pItem?->route,
                            $pItem?->duration_days ? sprintf('%s day(s)', $pItem->duration_days) : null,
                        ])->filter()->implode(', ');
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $itemName }}</strong>
                            @if($descriptor !== '')
                                <br><span class="muted">{{ $descriptor }}</span>
                            @endif
                            @if($directions !== '')
                                <br><span class="muted">{{ $directions }}</span>
                            @endif
                            @if($pItem?->instructions)
                                <br><span class="muted">{{ $pItem->instructions }}</span>
                            @endif
                            @if($item->substitutionInventoryItem)
                                <br><span class="muted">Substitution: {{ $item->substitutionInventoryItem->generic_name ?? $item->substitutionInventoryItem->name }}</span>
                            @endif
                            @if($item->external_pharmacy)
                                <br><span class="muted">External pharmacy{{ $item->external_reason ? ': '.$item->external_reason : '' }}</span>
                            @endif
                        </td>
                        <td>{{ number_format((float) $item->prescribed_quantity, 3) }}</td>
                        <td>{{ number_format((float) $item->dispensed_quantity, 3) }}</td>
                        <td>{{ number_format((float) $item->balance_quantity, 3) }}</td>
                        <td>{{ $item->dispense_status?->label() ?? 'N/A' }}</td>
                    </tr>
                    @if($item->allocations->isNotEmpty())
                        <tr>
                            <td colspan="5" style="padding-left: 1.5rem; font-size: 0.82em; color: #555;">
                                <em>Batches:</em>
                                @foreach($item->allocations as $allocation)
                                    {{ $allocation->batch_number_snapshot ?? 'No batch' }}
                                    (Qty: {{ number_format((float) $allocation->quantity, 3) }}{{ $allocation->expiry_date_snapshot ? ', Exp: '.$allocation->expiry_date_snapshot->format('d M Y') : '' }}){{ $loop->last ? '' : ';' }}
                                @endforeach
                            </td>
                        </tr>
                    @endif
                    @if($item->notes)
                        <tr>
                            <td colspan="5" style="padding-left: 1.5rem; font-size: 0.82em; color: #555;">
                                Note: {{ $item->notes }}
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="5">No dispensed items recorded.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($dispensingRecord->notes)
        <div class="section">
            <h2 class="section-title">Dispense Notes</h2>
            <div class="note-box">{{ $dispensingRecord->notes }}</div>
        </div>
    @endif

    @if($dispensingRecord->prescription?->pharmacy_notes)
        <div class="section">
            <h2 class="section-title">Pharmacy Notes</h2>
            <div class="note-box">{{ $dispensingRecord->prescription->pharmacy_notes }}</div>
        </div>
    @endif

    @include('print.partials.footer', ['printedAt' => $printedAt])
@endsection
