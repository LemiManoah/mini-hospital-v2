@extends('print.layouts.document')

@section('title', 'Visit Summary')

@section('content')
    @php
        $patient = $visit->patient;
        $patientName = trim(($patient?->first_name ?? '').' '.($patient?->middle_name ?? '').' '.($patient?->last_name ?? '')) ?: 'Unknown patient';
        $latestVitals = $visit->triage?->vitalSigns?->first();
    @endphp

    @include('print.partials.header', [
        'facilityName' => $visit->branch?->name ?? config('app.name'),
        'documentTitle' => 'Visit Summary',
        'branchCode' => $visit->branch?->branch_code,
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
                <span class="value">{{ $visit->visit_number }}</span>
            </td>
            <td>
                <span class="label">Visit Type</span>
                <span class="value">{{ $visit->visit_type?->label() ?? 'N/A' }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">Clinic</span>
                <span class="value">{{ $visit->clinic?->clinic_name ?? 'Not assigned' }}</span>
            </td>
            <td>
                <span class="label">Doctor</span>
                <span class="value">
                    @if($visit->doctor)
                        {{ $visit->doctor->first_name }} {{ $visit->doctor->last_name }}
                    @else
                        Not assigned
                    @endif
                </span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">Registered At</span>
                <span class="value">{{ $visit->registered_at?->format('d M Y H:i') ?? 'N/A' }}</span>
            </td>
            <td>
                <span class="label">Status</span>
                <span class="value">{{ $visit->status?->label() ?? 'N/A' }}</span>
            </td>
        </tr>
    </table>

    @if($visit->triage)
        <div class="section">
            <h2 class="section-title">Triage Summary</h2>
            <table class="meta-grid">
                <tr>
                    <td>
                        <span class="label">Chief Complaint</span>
                        <span class="value">{{ $visit->triage->chief_complaint ?? 'N/A' }}</span>
                    </td>
                    <td>
                        <span class="label">Triage Grade</span>
                        <span class="value">{{ $visit->triage->triage_grade?->label() ?? 'N/A' }}</span>
                    </td>
                </tr>
                <tr>
                    <td>
                        <span class="label">Attendance Type</span>
                        <span class="value">{{ $visit->triage->attendance_type?->label() ?? 'N/A' }}</span>
                    </td>
                    <td>
                        <span class="label">Latest Vitals</span>
                        <span class="value">
                            @if($latestVitals)
                                Temp {{ $latestVitals->temperature ?? '-' }},
                                Pulse {{ $latestVitals->pulse_rate ?? '-' }},
                                BP {{ $latestVitals->systolic_bp ?? '-' }}/{{ $latestVitals->diastolic_bp ?? '-' }}
                            @else
                                No vitals recorded
                            @endif
                        </span>
                    </td>
                </tr>
            </table>
            @if($visit->triage->history_of_presenting_illness)
                <div class="note-box">{{ $visit->triage->history_of_presenting_illness }}</div>
            @endif
        </div>
    @endif

    @if($visit->consultation)
        <div class="section">
            <h2 class="section-title">Consultation Summary</h2>
            <table class="meta-grid">
                <tr>
                    <td>
                        <span class="label">Primary Diagnosis</span>
                        <span class="value">{{ $visit->consultation->primary_diagnosis ?? 'N/A' }}</span>
                    </td>
                    <td>
                        <span class="label">ICD-10 Code</span>
                        <span class="value">{{ $visit->consultation->primary_icd10_code ?? 'N/A' }}</span>
                    </td>
                </tr>
            </table>
            @if($visit->consultation->assessment)
                <div class="section">
                    <span class="label">Assessment</span>
                    <div class="note-box">{{ $visit->consultation->assessment }}</div>
                </div>
            @endif
            @if($visit->consultation->plan)
                <div class="section">
                    <span class="label">Plan</span>
                    <div class="note-box">{{ $visit->consultation->plan }}</div>
                </div>
            @endif
        </div>
    @endif

    <div class="section">
        <h2 class="section-title">Orders and Requests</h2>
        <table class="table-grid">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Count</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Laboratory Requests</td>
                    <td>{{ $visit->labRequests->count() }}</td>
                    <td>
                        {{ $visit->labRequests->flatMap(fn ($request) => $request->items->map(fn ($item) => $item->test?->test_name))->filter()->take(3)->implode(', ') ?: '-' }}
                    </td>
                </tr>
                <tr>
                    <td>Imaging Requests</td>
                    <td>{{ $visit->imagingRequests->count() }}</td>
                    <td>{{ $visit->imagingRequests->take(3)->map(fn ($item) => $item->modality.' '.$item->body_part)->implode(', ') ?: '-' }}</td>
                </tr>
                <tr>
                    <td>Prescriptions</td>
                    <td>{{ $visit->prescriptions->count() }}</td>
                    <td>
                        {{ $visit->prescriptions->flatMap(fn ($prescription) => $prescription->items->map(fn ($item) => $item->inventoryItem?->generic_name ?: $item->inventoryItem?->name))->filter()->take(3)->implode(', ') ?: '-' }}
                    </td>
                </tr>
                <tr>
                    <td>Facility Services</td>
                    <td>{{ $visit->facilityServiceOrders->count() }}</td>
                    <td>{{ $visit->facilityServiceOrders->take(3)->map(fn ($item) => $item->service?->name)->filter()->implode(', ') ?: '-' }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    @if($visit->billing)
        <div class="section">
            <h2 class="section-title">Billing Snapshot</h2>
            <table class="meta-grid">
                <tr>
                    <td>
                        <span class="label">Gross Amount</span>
                        <span class="value">{{ number_format((float) $visit->billing->gross_amount, 2) }}</span>
                    </td>
                    <td>
                        <span class="label">Paid Amount</span>
                        <span class="value">{{ number_format((float) $visit->billing->paid_amount, 2) }}</span>
                    </td>
                </tr>
                <tr>
                    <td>
                        <span class="label">Balance</span>
                        <span class="value">{{ number_format((float) $visit->billing->balance_amount, 2) }}</span>
                    </td>
                    <td>
                        <span class="label">Recent Receipt</span>
                        <span class="value">{{ $visit->billing->payments->first()?->receipt_number ?? 'No payment yet' }}</span>
                    </td>
                </tr>
            </table>
        </div>
    @endif

    @include('print.partials.footer', [
        'printedAt' => $printedAt,
    ])
@endsection
