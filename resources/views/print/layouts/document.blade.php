<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Print Document')</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #0f172a;
            margin: 28px;
            line-height: 1.45;
        }

        .header,
        .section {
            margin-bottom: 20px;
        }

        .header h1 {
            margin: 0 0 4px;
            font-size: 22px;
        }

        .header p {
            margin: 0;
            color: #475569;
        }

        .meta-grid,
        .table-grid {
            width: 100%;
            border-collapse: collapse;
        }

        .meta-grid td {
            width: 50%;
            vertical-align: top;
            padding: 0 12px 10px 0;
        }

        .table-grid th,
        .table-grid td {
            border: 1px solid #cbd5e1;
            padding: 8px 10px;
            text-align: left;
            vertical-align: top;
        }

        .table-grid th {
            background: #e2e8f0;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .label {
            display: block;
            margin-bottom: 2px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #64748b;
        }

        .value {
            font-size: 12px;
            font-weight: 600;
        }

        .section-title {
            margin: 0 0 10px;
            font-size: 14px;
            font-weight: 700;
        }

        .note-box {
            border: 1px solid #cbd5e1;
            padding: 10px 12px;
            border-radius: 6px;
            background: #f8fafc;
        }

        .status-chip {
            display: inline-block;
            padding: 4px 8px;
            border: 1px solid #cbd5e1;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            background: #f8fafc;
        }

        .muted {
            color: #64748b;
        }

        .footer {
            margin-top: 28px;
            font-size: 10px;
            color: #64748b;
        }

        @yield('styles')
    </style>
</head>
<body>
    @yield('content')
</body>
</html>
