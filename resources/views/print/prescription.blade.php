@extends('print.layouts.document')

@section('title', 'Prescription')

@section('content')
    @php
        $patientName = trim(($patient?->first_name ?? '').' '.($patient?->middle_name ?? '').' '.($patient?->last_name ?? '')) ?: 'Unknown patient';
        $prescriberName = $prescription->prescribedBy
            ? trim($prescription->prescribedBy->first_name.' '.$prescription->prescribedBy->last_name)
            : 'Not recorded';
    @endphp

    @include('print.partials.header', [
        'facilityName' => $visit?->branch?->name ?? config('app.name'),
        'documentTitle' => 'Prescription',
        'branchCode' => $visit?->branch?->branch_code,
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
                <span class="label">Prescription Date</span>
                <span class="value">{{ $prescription->prescription_date?->format('d M Y H:i') ?? 'N/A' }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">Primary Diagnosis</span>
                <span class="value">{{ $prescription->primary_diagnosis ?? 'N/A' }}</span>
            </td>
            <td>
                <span class="label">Prescribed By</span>
                <span class="value">{{ $prescriberName }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">Status</span>
                <span class="value">{{ $prescription->status?->label() ?? 'N/A' }}</span>
            </td>
            <td>
                <span class="label">Flags</span>
                <span class="value">
                    @php
                        $flags = collect([
                            $prescription->is_discharge_medication ? 'Discharge medication' : null,
                            $prescription->is_long_term ? 'Long term' : null,
                        ])->filter()->values();
                    @endphp
                    {{ $flags->isNotEmpty() ? $flags->implode(', ') : 'None' }}
                </span>
            </td>
        </tr>
    </table>

    <div class="section">
        <h2 class="section-title">Medication Orders</h2>
        <table class="table-grid">
            <thead>
                <tr>
                    <th>Medication</th>
                    <th>Directions</th>
                    <th>Quantity</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($prescription->items as $item)
                    @php
                        $inventoryItem = $item->inventoryItem;
                        $itemName = $inventoryItem?->generic_name
                            ?? $inventoryItem?->name
                            ?? 'Medication';
                        $descriptor = collect([
                            $inventoryItem?->brand_name,
                            $inventoryItem?->strength,
                            $inventoryItem?->dosage_form?->value,
                        ])->filter()->implode(' | ');
                        $directions = collect([
                            $item->dosage,
                            $item->frequency,
                            $item->route,
                            $item->duration_days ? sprintf('%s day(s)', $item->duration_days) : null,
                            $item->is_prn ? 'PRN' : null,
                        ])->filter()->implode(', ');
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $itemName }}</strong>
                            @if($descriptor !== '')
                                <br>
                                <span class="muted">{{ $descriptor }}</span>
                            @endif
                            @if($item->instructions)
                                <br>
                                <span class="muted">{{ $item->instructions }}</span>
                            @endif
                            @if($item->is_external_pharmacy)
                                <br>
                                <span class="muted">External pharmacy</span>
                            @endif
                        </td>
                        <td>{{ $directions !== '' ? $directions : 'N/A' }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ $item->status?->label() ?? 'N/A' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">No medication lines recorded.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($prescription->pharmacy_notes)
        <div class="section">
            <h2 class="section-title">Pharmacy Notes</h2>
            <div class="note-box">{{ $prescription->pharmacy_notes }}</div>
        </div>
    @endif

    @include('print.partials.footer', [
        'printedAt' => $printedAt,
    ])
@endsection
