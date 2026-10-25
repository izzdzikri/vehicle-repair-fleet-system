<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Fleet Statement - {{ $company->name ?? 'Corporate Fleet' }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: DejaVu Sans, Helvetica, Arial, sans-serif; font-size: 11px; color: #1f2937; background: #fff; }
        .page { padding: 32px 36px; }
        .header-table { width: 100%; border-bottom: 2px solid #0f172a; padding-bottom: 16px; margin-bottom: 18px; }
        .workshop-title { font-size: 18px; font-weight: bold; color: #0f172a; }
        .workshop-meta { font-size: 9.5px; color: #64748b; line-height: 1.5; margin-top: 4px; }
        .statement-title { font-size: 18px; font-weight: bold; color: #0f172a; text-align: right; }
        .period-label { font-size: 9px; text-transform: uppercase; font-weight: bold; color: #64748b; text-align: right; margin-top: 4px; }
        .period-value { font-size: 12px; font-weight: bold; color: #0f172a; text-align: right; }
        .meta-table { width: 100%; margin-bottom: 18px; }
        .meta-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 12px; vertical-align: top; }
        .meta-label { font-size: 8.5px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: bold; color: #94a3b8; }
        .meta-val-primary { font-size: 12px; font-weight: bold; color: #0f172a; margin-top: 2px; }
        .meta-val-sub { font-size: 9.5px; color: #475569; margin-top: 2px; line-height: 1.4; }
        .stat-grid { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .stat-cell { width: 25%; padding: 10px; background: #f1f5f9; border: 1px solid #e2e8f0; text-align: center; }
        .stat-lbl { font-size: 8px; text-transform: uppercase; font-weight: bold; color: #64748b; }
        .stat-val { font-size: 14px; font-weight: bold; color: #0f172a; margin-top: 3px; }
        .section-header { font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; color: #0f172a; margin: 16px 0 8px; border-bottom: 1px solid #cbd5e1; padding-bottom: 4px; }
        .table-data { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .table-data thead tr { background: #0f172a; color: #fff; }
        .table-data thead th { padding: 6px 8px; font-size: 8.5px; text-transform: uppercase; font-weight: bold; text-align: left; }
        .table-data thead th.right { text-align: right; }
        .table-data thead th.center { text-align: center; }
        .table-data tbody tr { border-bottom: 1px solid #e2e8f0; }
        .table-data tbody tr.even { background: #f8fafc; }
        .table-data tbody td { padding: 6px 8px; font-size: 9.5px; }
        .table-data tbody td.right { text-align: right; }
        .table-data tbody td.center { text-align: center; }
        .table-data tfoot tr { background: #f1f5f9; font-weight: bold; border-top: 1.5px solid #0f172a; }
        .table-data tfoot td { padding: 6px 8px; font-size: 9.5px; }
        .footer { margin-top: 24px; border-top: 1px solid #e2e8f0; padding-top: 10px; font-size: 8.5px; color: #94a3b8; text-align: center; line-height: 1.5; }
    </style>
</head>
<body>
<div class="page">

    {{-- Statement Header --}}
    <table class="header-table">
        <tr>
            @php
                $logoPath = public_path('images/invoice-logo.png');
                $logoBase64 = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : null;
            @endphp
            @if($logoBase64)
            <td style="width: 65px; vertical-align: top; padding-right: 10px;">
                <img src="data:image/png;base64,{{ $logoBase64 }}" style="width: 55px; height: auto;">
            </td>
            @endif
            <td style="vertical-align: top;">
                <div class="workshop-title">TERAJU SETIA ENTERPRISE</div>
                <div class="workshop-meta">
                    Fleet Maintenance Operations & Logistics Support<br>
                    No. 1, Jalan Malinja 2, Taman Perindustrian Matahari, 86000 Kluang, Johor<br>
                    Tel: 07-787 8728 / 019-728 8728 &bull; Email: fleet@terajusetia.com.my
                </div>
            </td>
            <td style="vertical-align: top; text-align: right; width: 35%;">
                <div class="statement-title">FLEET STATEMENT</div>
                <div class="period-label">Period</div>
                <div class="period-value">
                    {{ $month !== 'all' ? \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') : 'All Time Historical' }}
                </div>
                <div style="font-size: 8.5px; color: #94a3b8; margin-top: 3px;">
                    Date: {{ now()->format('d M Y') }}
                </div>
            </td>
        </tr>
    </table>

    {{-- Meta Card --}}
    <table class="meta-table">
        <tr>
            <td class="meta-card" style="width: 50%;">
                <div class="meta-label">Client Account</div>
                <div class="meta-val-primary">{{ $company->name ?? auth()->user()->name }}</div>
                <div class="meta-val-sub">
                    {{ $company->address ?? 'Corporate Account' }}<br>
                    @if($company && $company->registration_no) Reg No: {{ $company->registration_no }} @endif
                </div>
            </td>
            <td style="width: 4%;"></td>
            <td class="meta-card" style="width: 46%;">
                <div class="meta-label">Account Representative</div>
                <div class="meta-val-primary">{{ auth()->user()->name }}</div>
                <div class="meta-val-sub">
                    Email: {{ auth()->user()->email }}<br>
                    Contact: {{ auth()->user()->contact_no ?? 'N/A' }}<br>
                    Fleet Size: {{ $vehicles->count() }} active assets
                </div>
            </td>
        </tr>
    </table>

    {{-- Executive Summary Financials --}}
    <table class="stat-grid">
        <tr>
            <td class="stat-cell">
                <div class="stat-lbl">Total Maintenance Cost</div>
                <div class="stat-val">RM {{ number_format($totalSpent, 2) }}</div>
            </td>
            <td class="stat-cell">
                <div class="stat-lbl">Total Invoiced</div>
                <div class="stat-val" style="color: #1d4ed8;">RM {{ number_format($totalInvoiced, 2) }}</div>
            </td>
            <td class="stat-cell">
                <div class="stat-lbl">Total Settled (Paid)</div>
                <div class="stat-val" style="color: #15803d;">RM {{ number_format($totalPaid, 2) }}</div>
            </td>
            <td class="stat-cell">
                <div class="stat-lbl">Outstanding Balance</div>
                <div class="stat-val" style="color: {{ $outstandingBalance > 0 ? '#b91c1c' : '#0f172a' }};">
                    RM {{ number_format($outstandingBalance, 2) }}
                </div>
            </td>
        </tr>
    </table>

    {{-- Table 1: Per-Vehicle Breakdown --}}
    <div class="section-header">1. Vehicle Expense Allocation Breakdown</div>
    <table class="table-data">
        <thead>
            <tr>
                <th>Vehicle Plate</th>
                <th>Make & Model</th>
                <th>Odometer</th>
                <th class="center">Services</th>
                <th>Last Service</th>
                <th class="right">Total Incurred</th>
                <th class="right">% of Fleet</th>
            </tr>
        </thead>
        <tbody>
            @forelse($vehicleSummary as $i => $row)
            @php
                $pct = $totalSpent > 0 ? round(($row['total_spend'] / $totalSpent) * 100, 1) : 0;
            @endphp
            <tr class="{{ $i % 2 === 1 ? 'even' : '' }}">
                <td style="font-weight: bold; font-family: monospace;">{{ $row['vehicle']->plate_number }}</td>
                <td>{{ $row['vehicle']->brand }} {{ $row['vehicle']->model }} ({{ $row['vehicle']->year }})</td>
                <td>{{ number_format($row['vehicle']->mileage) }} km</td>
                <td class="center">{{ $row['service_count'] }}</td>
                <td>{{ $row['last_service'] ? \Carbon\Carbon::parse($row['last_service'])->format('d M Y') : '—' }}</td>
                <td class="right" style="font-weight: bold;">RM {{ number_format($row['total_spend'], 2) }}</td>
                <td class="right">{{ $pct }}%</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="center" style="padding: 12px; color: #94a3b8;">No fleet vehicles recorded.</td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3">FLEET TOTALS</td>
                <td class="center">{{ $vehicleSummary->sum('service_count') }}</td>
                <td></td>
                <td class="right">RM {{ number_format($totalSpent, 2) }}</td>
                <td class="right">100.0%</td>
            </tr>
        </tfoot>
    </table>

    {{-- Table 2: Itemized Services --}}
    <div class="section-header">2. Itemized Service & Repair Ledger</div>
    <table class="table-data">
        <thead>
            <tr>
                <th>Date</th>
                <th>Plate</th>
                <th>Service Description</th>
                <th class="center">Job #</th>
                <th class="right">Cost (RM)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($serviceHistory as $i => $entry)
            <tr class="{{ $i % 2 === 1 ? 'even' : '' }}">
                <td style="white-space: nowrap;">{{ \Carbon\Carbon::parse($entry->service_date)->format('d M Y') }}</td>
                <td style="font-weight: bold; font-family: monospace; white-space: nowrap;">{{ $entry->vehicle->plate_number ?? '—' }}</td>
                <td>{{ $entry->description }}</td>
                <td class="center">{{ $entry->job_card_id ? '#'.$entry->job_card_id : '—' }}</td>
                <td class="right" style="font-weight: bold;">RM {{ number_format($entry->cost, 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="center" style="padding: 12px; color: #94a3b8;">No service history records found for this period.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Footer --}}
    <div class="footer">
        Teraju Setia Enterprise &bull; Computer-Generated Fleet Statement &bull; Page 1 of 1<br>
        For inquiries regarding account reconciliation or billing, contact accounts@terajusetia.com.my.
    </div>

</div>
</body>
</html>
