<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>@yield('title', 'Report')</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #0f172a; line-height: 1.4; }

        /* ── Page header ─────────────────────────────────── */
        .rpt-header { border-bottom: 2px solid #1d4ed8; padding-bottom: 8px; margin-bottom: 14px; }
        .rpt-header .facility { font-size: 15px; font-weight: bold; color: #1d4ed8; }
        .rpt-header .title { font-size: 12px; font-weight: bold; color: #0f172a; margin-top: 3px; }
        .rpt-header .meta { font-size: 8.5px; color: #64748b; margin-top: 4px; }

        /* ── Applied-filters bar ──────────────────────────── */
        .rpt-filters { background: #f1f5f9; border-radius: 3px; padding: 5px 10px; margin-bottom: 12px; font-size: 8.5px; color: #334155; }
        .rpt-filters .filter-item { display: inline-block; margin-right: 18px; }
        .rpt-filters strong { color: #0f172a; }

        /* ── KPI summary boxes ────────────────────────────── */
        .rpt-summary { margin-bottom: 14px; }
        .rpt-summary table { width: 100%; border-collapse: collapse; }
        .rpt-summary td { width: 25%; padding: 8px 10px; border: 1px solid #e2e8f0; background: #f8fafc; }
        .rpt-summary .kpi-label { font-size: 7.5px; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; }
        .rpt-summary .kpi-value { font-size: 18px; font-weight: bold; color: #1d4ed8; margin-top: 2px; }
        .rpt-summary .kpi-sub { font-size: 8px; color: #64748b; margin-top: 1px; }

        /* ── Section heading ──────────────────────────────── */
        .rpt-section { font-size: 10px; font-weight: bold; color: #1d4ed8; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; margin: 14px 0 8px; }

        /* ── Data table ───────────────────────────────────── */
        .rpt-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .rpt-table thead tr { background: #1e40af; color: #fff; }
        .rpt-table thead th { padding: 5px 8px; text-align: left; font-size: 8.5px; font-weight: bold; letter-spacing: 0.03em; }
        .rpt-table thead th.right { text-align: right; }
        .rpt-table tbody tr:nth-child(even) { background: #f8fafc; }
        .rpt-table tbody td { padding: 4px 8px; font-size: 9px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        .rpt-table tbody td.right { text-align: right; }
        .rpt-table tbody td.center { text-align: center; }
        .rpt-table tfoot tr { background: #e2e8f0; font-weight: bold; }
        .rpt-table tfoot td { padding: 5px 8px; font-size: 9px; border-top: 2px solid #94a3b8; }
        .rpt-table tfoot td.right { text-align: right; }

        /* ── Status badges ────────────────────────────────── */
        .badge { display: inline-block; padding: 1px 6px; border-radius: 9px; font-size: 7.5px; font-weight: bold; }
        .badge-green  { background: #dcfce7; color: #166534; }
        .badge-red    { background: #fee2e2; color: #991b1b; }
        .badge-yellow { background: #fef9c3; color: #854d0e; }
        .badge-blue   { background: #dbeafe; color: #1e40af; }
        .badge-orange { background: #ffedd5; color: #9a3412; }
        .badge-gray   { background: #f1f5f9; color: #475569; }

        /* ── Note / alert box ─────────────────────────────── */
        .rpt-note { border-left: 3px solid #facc15; background: #fefce8; padding: 6px 10px; font-size: 8.5px; color: #713f12; margin-bottom: 12px; }

        /* ── Empty state ──────────────────────────────────── */
        .rpt-empty { text-align: center; padding: 20px; color: #94a3b8; font-size: 9px; }

        /* ── Footer ───────────────────────────────────────── */
        .rpt-footer { margin-top: 20px; border-top: 1px solid #e2e8f0; padding-top: 5px; font-size: 8px; color: #94a3b8; }

        /* Avoid breaking rows across pages */
        tr { page-break-inside: avoid; }
        thead { display: table-header-group; }

        @page { margin: 16mm 13mm 14mm; }

        @yield('styles')
    </style>
</head>
<body>

    {{-- ── Header ─────────────────────────────────────────── --}}
    <div class="rpt-header">
        <div class="facility">{{ $facilityName }}</div>
        <div class="title">{{ $reportTitle }}</div>
        <div class="meta">
            Generated: {{ now()->format('d M Y, H:i') }}
            &nbsp;|&nbsp; By: {{ $generatedBy }}
            @if(!empty($reportPeriod))
                &nbsp;|&nbsp; Period: {{ $reportPeriod }}
            @endif
        </div>
    </div>

    {{-- ── Applied filters ─────────────────────────────────── --}}
    @if(!empty($appliedFilters))
    <div class="rpt-filters">
        @foreach($appliedFilters as $label => $value)
            <span class="filter-item"><strong>{{ $label }}:</strong> {{ $value }}</span>
        @endforeach
    </div>
    @endif

    {{-- ── Report body ──────────────────────────────────────── --}}
    @yield('body')

    {{-- ── Footer ──────────────────────────────────────────── --}}
    <div class="rpt-footer">
        <table style="width:100%;border-collapse:collapse;">
            <tr>
                <td>{{ $facilityName }} &mdash; {{ $reportTitle }}</td>
                <td style="text-align:right;">Confidential</td>
            </tr>
        </table>
    </div>

    {{-- DomPDF page numbers --}}
    <script type="text/php">
        if (isset($pdf)) {
            $w = $pdf->get_width();
            $h = $pdf->get_height();
            $pdf->page_text($w - 55, $h - 20, "Page {PAGE_NUM} of {PAGE_COUNT}", null, 7, [0.58, 0.65, 0.74]);
        }
    </script>
</body>
</html>
