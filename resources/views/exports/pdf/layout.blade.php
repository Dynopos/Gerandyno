<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: "DejaVu Sans", sans-serif;
            font-size: 11px;
            color: #0f172a;
        }
        .header {
            border-bottom: 2px solid #7a5717;
            padding-bottom: 10px;
            margin-bottom: 16px;
        }
        .brand {
            font-size: 16px;
            font-weight: bold;
            color: #7a5717;
        }
        .company {
            font-size: 12px;
            color: #475569;
            margin-top: 2px;
        }
        h1 {
            font-size: 15px;
            margin: 0 0 2px;
        }
        .meta {
            font-size: 10px;
            color: #64748b;
            margin-bottom: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        th, td {
            border: 1px solid #e2e8f0;
            padding: 5px 8px;
            text-align: left;
        }
        th {
            background: #f8fafc;
            font-size: 9px;
            text-transform: uppercase;
            color: #475569;
        }
        td.numeric, th.numeric {
            text-align: right;
        }
        tfoot td {
            font-weight: bold;
            background: #f8fafc;
        }
        .footer {
            margin-top: 16px;
            font-size: 9px;
            color: #94a3b8;
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">DynoPOS Cloud Report</div>
        <div class="company">{{ $companyName }}</div>
    </div>

    <h1>{{ $title }}</h1>
    @if ($subtitle ?? null)
        <div class="meta">{{ $subtitle }}</div>
    @endif

    @yield('content')

    <div class="footer">{{ now()->translatedFormat('d M Y, H:i') }}</div>
</body>
</html>
