@extends('print.layouts.document')

@section('title', 'Appointment Schedule')

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

    .pill {
        display: inline-block;
        margin-right: 6px;
        margin-bottom: 6px;
        padding: 4px 8px;
        border-radius: 999px;
        font-size: 10px;
        font-weight: 700;
        background: #e2e8f0;
        color: #1e293b;
    }

    .status-green { background: #dcfce7; color: #166534; }
    .status-blue { background: #dbeafe; color: #1d4ed8; }
    .status-red { background: #fee2e2; color: #991b1b; }
    .status-orange { background: #ffedd5; color: #9a3412; }
    .status-slate { background: #e2e8f0; color: #334155; }

    .empty-state {
        border: 1px dashed #cbd5e1;
        padding: 18px;
        color: #64748b;
        background: #f8fafc;
    }

    .muted-text {
        color: #64748b;
        font-size: 10px;
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
                    <span class="summary-label">Total Appointments</span>
                    <div class="summary-value">{{ $total }}</div>
                    <div class="summary-sub">{{ $day_of_week }}</div>
                </td>
                <td colspan="3">
                    <span class="summary-label">Status Breakdown</span>
                    @foreach(['scheduled' => 'Scheduled', 'confirmed' => 'Confirmed', 'checked_in' => 'Checked In', 'completed' => 'Completed', 'no_show' => 'No Show', 'cancelled' => 'Cancelled'] as $key => $label)
                        @if(isset($by_status[$key]) && $by_status[$key] > 0)
                            <span class="pill">{{ $label }}: {{ $by_status[$key] }}</span>
                        @endif
                    @endforeach
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h2 class="section-title">Appointment List</h2>

        @if($rows->isNotEmpty())
            <table class="table-grid">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Time</th>
                        <th>Patient</th>
                        <th>Doctor</th>
                        <th>Clinic</th>
                        <th>Category</th>
                        <th>Mode</th>
                        <th>Status</th>
                        <th>Chief Complaint</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $index => $appointment)
                        @php
                            $patientName = trim(
                                ($appointment->patient?->first_name ?? '').' '.
                                ($appointment->patient?->middle_name ?? '').' '.
                                ($appointment->patient?->last_name ?? '')
                            ) ?: 'Unknown patient';
                            $doctorName = trim(
                                ($appointment->doctor?->first_name ?? '').' '.
                                ($appointment->doctor?->last_name ?? '')
                            ) ?: 'Unassigned doctor';
                            $statusClass = match($appointment->status->value) {
                                'completed' => 'status-green',
                                'confirmed', 'checked_in', 'in_progress' => 'status-blue',
                                'cancelled' => 'status-red',
                                'no_show' => 'status-orange',
                                default => 'status-slate',
                            };
                        @endphp
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                <strong>{{ \Carbon\Carbon::parse($appointment->start_time)->format('H:i') }}</strong>
                                @if($appointment->end_time)
                                    <div class="muted-text">to {{ \Carbon\Carbon::parse($appointment->end_time)->format('H:i') }}</div>
                                @endif
                            </td>
                            <td>
                                <div>{{ $patientName }}</div>
                                <div class="muted-text">{{ $appointment->patient?->patient_number ?? 'No patient number' }}</div>
                            </td>
                            <td>{{ $doctorName }}</td>
                            <td>{{ $appointment->clinic?->clinic_name ?? 'Not set' }}</td>
                            <td>{{ $appointment->category?->name ?? 'Not set' }}</td>
                            <td>
                                {{ $appointment->mode?->name ?? 'Not set' }}
                                @if($appointment->mode?->is_virtual)
                                    <div class="muted-text">Virtual</div>
                                @endif
                            </td>
                            <td><span class="status-chip {{ $statusClass }}">{{ str($appointment->status->value)->replace('_', ' ')->title() }}</span></td>
                            <td>{{ $appointment->chief_complaint ?? $appointment->reason_for_visit ?? 'None documented' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="empty-state">No appointments were scheduled for the selected date.</div>
        @endif
    </div>

    @include('print.partials.footer', [
        'printedAt' => now(),
        'printedBy' => $generatedBy,
    ])
@endsection
