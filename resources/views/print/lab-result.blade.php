@extends('print.layouts.document')

@section('title', 'Laboratory Result')

@section('content')
    @include('print.partials.header', [
        'facilityName' => $visit?->branch?->name ?? config('app.name'),
        'documentTitle' => 'Released Laboratory Result',
        'branchCode' => $visit?->branch?->branch_code,
    ])

    <table class="meta-grid section">
        <tr>
            <td>
                <span class="label">Patient</span>
                <span class="value">{{ trim(($patient?->first_name ?? '').' '.($patient?->middle_name ?? '').' '.($patient?->last_name ?? '')) ?: 'Unknown patient' }}</span>
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
                <span class="label">Requested At</span>
                <span class="value">{{ $labRequest?->request_date?->format('d M Y H:i') ?? 'N/A' }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">Test</span>
                <span class="value">{{ $test?->test_name ?? 'N/A' }} @if($test?->test_code) ({{ $test->test_code }}) @endif</span>
            </td>
            <td>
                <span class="label">Priority</span>
                <span class="value">{{ $labRequest?->priority?->label() ?? 'Normal' }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">Specimen</span>
                <span class="value">{{ $labRequestItem->specimen?->specimen_type_name ?? 'N/A' }}</span>
            </td>
            <td>
                <span class="label">Accession Number</span>
                <span class="value">{{ $labRequestItem->specimen?->accession_number ?? 'N/A' }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">Released At</span>
                <span class="value">{{ $labRequestItem->resultEntry?->released_at?->format('d M Y H:i') ?? ($labRequestItem->approved_at?->format('d M Y H:i') ?? 'N/A') }}</span>
            </td>
            <td>
                <span class="label">Released By</span>
                <span class="value">
                    {{ $labRequestItem->resultEntry?->approvedBy ? trim($labRequestItem->resultEntry->approvedBy->first_name.' '.$labRequestItem->resultEntry->approvedBy->last_name) : 'N/A' }}
                </span>
            </td>
        </tr>
    </table>

    <div class="section">
        <h2 class="section-title">Result Values</h2>
        <table class="table-grid">
            <thead>
                <tr>
                    <th>Parameter</th>
                    <th>Result</th>
                    <th>Unit</th>
                    <th>Reference Range</th>
                </tr>
            </thead>
            <tbody>
                @forelse(($labRequestItem->resultEntry?->values ?? []) as $value)
                    <tr>
                        <td>{{ $value->label ?? 'Result' }}</td>
                        <td>{{ $value->display_value ?? $value->value_text ?? $value->value_numeric ?? 'N/A' }}</td>
                        <td>{{ $value->unit ?? '-' }}</td>
                        <td>{{ $value->reference_range ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">No released result values available.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($labRequestItem->resultEntry?->result_notes)
        <div class="section">
            <h2 class="section-title">Bench Notes</h2>
            <div class="note-box">{{ $labRequestItem->resultEntry->result_notes }}</div>
        </div>
    @endif

    @if($labRequestItem->resultEntry?->review_notes)
        <div class="section">
            <h2 class="section-title">Review Notes</h2>
            <div class="note-box">{{ $labRequestItem->resultEntry->review_notes }}</div>
        </div>
    @endif

    @if($labRequestItem->resultEntry?->approval_notes)
        <div class="section">
            <h2 class="section-title">Approval Notes</h2>
            <div class="note-box">{{ $labRequestItem->resultEntry->approval_notes }}</div>
        </div>
    @endif

    @if($labRequest?->clinical_notes)
        <div class="section">
            <h2 class="section-title">Clinical Notes</h2>
            <div class="note-box">{{ $labRequest->clinical_notes }}</div>
        </div>
    @endif

    @include('print.partials.footer', [
        'printedAt' => $printedAt,
        'printedBy' => $printedBy?->email,
    ])
@endsection
