<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Purchase Order {{ $purchaseOrder->po_number }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: DejaVu Sans, Helvetica, Arial, sans-serif; font-size: 11px; color: #1f2937; background: #fff; line-height: 1.4; }
        .page { padding: 32px 36px; }
        .header { width: 100%; border-bottom: 2px solid #2563eb; padding-bottom: 16px; margin-bottom: 18px; }
        .header-table { width: 100%; }
        .workshop-name { font-size: 18px; font-weight: bold; color: #1d4ed8; }
        .workshop-meta { font-size: 9.5px; color: #6b7280; line-height: 1.6; margin-top: 3px; }
        .doc-label { font-size: 18px; font-weight: bold; color: #111827; text-align: right; }
        .doc-number { font-size: 13px; color: #2563eb; font-weight: bold; text-align: right; margin-top: 2px; }
        .doc-meta { font-size: 9.5px; color: #6b7280; text-align: right; line-height: 1.6; margin-top: 3px; }
        .badge { display: inline-block; padding: 2.5px 8px; border-radius: 12px; font-size: 9px; font-weight: bold; letter-spacing: 0.5px; }
        .badge-received  { background: #dcfce7; color: #15803d; }
        .badge-ordered   { background: #dbeafe; color: #1d4ed8; }
        .badge-cancelled { background: #fee2e2; color: #991b1b; }
        .badge-draft     { background: #fef9c3; color: #854d0e; }
        .info-table { width: 100%; margin-bottom: 18px; }
        .info-cell { padding: 10px 12px; background: #f9fafb; border-radius: 6px; vertical-align: top; border: 1px solid #f3f4f6; }
        .info-label { font-size: 8.5px; text-transform: uppercase; letter-spacing: 0.8px; color: #9ca3af; font-weight: bold; margin-bottom: 3px; }
        .info-value { font-size: 11.5px; font-weight: bold; color: #111827; }
        .info-sub   { font-size: 9.5px; color: #6b7280; margin-top: 2px; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .items-table thead tr { background: #1d4ed8; color: #fff; }
        .items-table thead th { padding: 8px 10px; text-align: left; font-size: 9.5px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: bold; }
        .items-table thead th.right { text-align: right; }
        .items-table tbody tr { border-bottom: 1px solid #e5e7eb; }
        .items-table tbody td { padding: 8px 10px; font-size: 10.5px; }
        .items-table tbody td.right { text-align: right; }
        .totals-table { width: 100%; border-collapse: collapse; }
        .totals-lbl { font-size: 10px; color: #6b7280; padding: 3px 0; }
        .totals-val { font-size: 10px; color: #111827; text-align: right; padding: 3px 0; font-weight: 600; }
        .signatures-table { width: 100%; margin-top: 24px; padding-top: 12px; border-top: 1px solid #e5e7eb; }
        .sig-box { vertical-align: top; }
        .sig-label { font-size: 9px; font-weight: bold; text-transform: uppercase; color: #4b5563; margin-bottom: 30px; }
        .sig-line { width: 160px; border-bottom: 1px solid #9ca3af; margin-bottom: 3px; }
        .sig-name { font-size: 10px; font-weight: bold; color: #111827; }
        .sig-sub  { font-size: 8.5px; color: #9ca3af; }
        .footer { margin-top: 20px; border-top: 1px solid #e5e7eb; padding-top: 10px; font-size: 8.5px; color: #9ca3af; text-align: center; }
        .watermark { position: fixed; top: 35%; left: 10%; font-size: 70px; font-weight: bold; color: rgba(22, 163, 74, 0.06); transform: rotate(-30deg); text-transform: uppercase; letter-spacing: 8px; }
    </style>
</head>
<body>
<div class="page">

    @if($purchaseOrder->status === 'received')
    <div class="watermark">RECEIVED</div>
    @endif

    {{-- Header --}}
    <div class="header">
        <table class="header-table">
            <tr>
                <td style="width:62%; vertical-align:top;">
                    <table style="width:100%; border:none; border-collapse:collapse; margin:0; padding:0;">
                        <tr>
                            @php
                                $logoPath = public_path('images/invoice-logo.png');
                                $logoBase64 = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : null;
                            @endphp
                            @if($logoBase64)
                            <td style="width:80px; vertical-align:top; padding-right:10px;">
                                <img src="data:image/png;base64,{{ $logoBase64 }}" style="width:72px; height:auto;">
                            </td>
                            @endif
                            <td style="vertical-align:top;">
                                <div class="workshop-name">Teraju Setia Enterprise</div>
                                <div class="workshop-meta">
                                    No. 1, Jalan Malinja 2, Taman Perindustrian Matahari<br>
                                    86000 Kluang, Johor Darul Ta'zim, Malaysia<br>
                                    Tel: 07-787 8728 &nbsp;|&nbsp; Reg: 202103198822 (003189282-M)
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td style="width:38%; vertical-align:top;">
                    <div class="doc-label">PURCHASE ORDER</div>
                    <div class="doc-number">{{ $purchaseOrder->po_number }}</div>
                    <div class="doc-meta">
                        Issued: {{ $purchaseOrder->created_at->format('d M Y') }}<br>
                        @if($purchaseOrder->received_at)
                        Delivered: {{ $purchaseOrder->received_at->format('d M Y') }}<br>
                        @endif
                    </div>
                    <div style="text-align:right; margin-top:4px;">
                        <span class="badge {{ $purchaseOrder->status === 'received' ? 'badge-received' : ($purchaseOrder->status === 'ordered' ? 'badge-ordered' : ($purchaseOrder->status === 'cancelled' ? 'badge-cancelled' : 'badge-draft')) }}">
                            {{ strtoupper($purchaseOrder->status === 'received' ? 'Received & Stocked' : $purchaseOrder->status) }}
                        </span>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Info Cards --}}
    <table class="info-table">
        <tr>
            <td class="info-cell" style="width:48%;">
                <div class="info-label">Supplier / Vendor</div>
                <div class="info-value">{{ $purchaseOrder->supplier->name ?? 'Johor Motor Supplies' }}</div>
                @if($purchaseOrder->supplier?->contact_person)
                <div class="info-sub">Attn: {{ $purchaseOrder->supplier->contact_person }}</div>
                @endif
                @if($purchaseOrder->supplier?->phone)
                <div class="info-sub">Tel: {{ $purchaseOrder->supplier->phone }}</div>
                @endif
                @if($purchaseOrder->supplier?->email)
                <div class="info-sub">{{ $purchaseOrder->supplier->email }}</div>
                @endif
            </td>
            <td style="width:4%;"></td>
            <td class="info-cell" style="width:48%;">
                <div class="info-label">Delivery & Warehouse Target</div>
                <div class="info-value">Main Parts Store &bull; Bay A</div>
                <div class="info-sub">Rack: {{ $purchaseOrder->sparePart->shelf_location ?? '01' }} &bull; Shelf Stock: {{ $purchaseOrder->sparePart->stock ?? 0 }} units</div>
                <div class="info-sub">
                    {{ $purchaseOrder->auto_generated ? 'Automated Low-Stock Heuristic' : 'Manual Workshop Requisition' }}
                    &bull; By {{ $purchaseOrder->creator->name ?? 'Administrator' }}
                </div>
            </td>
        </tr>
    </table>

    {{-- Line Items --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:48%;">Component Specification</th>
                <th style="width:20%;">SKU / Part No.</th>
                <th class="right" style="width:10%;">Qty</th>
                <th class="right" style="width:11%;">Unit Cost</th>
                <th class="right" style="width:11%;">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <strong>{{ $purchaseOrder->sparePart->name ?? 'Spare Part Component' }}</strong>
                    @if($purchaseOrder->sparePart && $purchaseOrder->sparePart->brand)
                    <div style="font-size:8.5px; color:#6b7280;">Brand: {{ $purchaseOrder->sparePart->brand }}</div>
                    @endif
                </td>
                <td style="font-family:DejaVu Sans Mono, monospace; font-size:9.5px; color:#4b5563;">
                    {{ $purchaseOrder->sparePart->part_number ?? '—' }}
                </td>
                <td class="right" style="font-weight:bold;">
                    {{ number_format($purchaseOrder->quantity) }}
                </td>
                <td class="right">
                    RM {{ number_format($purchaseOrder->unit_cost ?? 0, 2) }}
                </td>
                <td class="right" style="font-weight:bold;">
                    RM {{ number_format($purchaseOrder->total_cost, 2) }}
                </td>
            </tr>
        </tbody>
    </table>

    {{-- Financial Totals --}}
    <table style="width:100%; margin-top:4px;">
        <tr>
            <td style="width:55%;"></td>
            <td style="width:45%;">
                <table class="totals-table">
                    <tr>
                        <td class="totals-lbl">Subtotal</td>
                        <td class="totals-val">RM {{ number_format($purchaseOrder->total_cost, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="totals-lbl">Workshop SST / Tax (0%)</td>
                        <td class="totals-val">RM 0.00</td>
                    </tr>
                    <tr style="border-top: 2px solid #1d4ed8;">
                        <td style="font-size:11.5px; font-weight:bold; color:#111827; padding-top:6px;">Total Payable</td>
                        <td style="font-size:11.5px; font-weight:bold; color:#1d4ed8; text-align:right; padding-top:6px;">RM {{ number_format($purchaseOrder->total_cost, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="font-size:9.5px; color:#16a34a; font-weight:600; padding-top:3px;">Status</td>
                        <td style="font-size:9.5px; color:#16a34a; font-weight:600; text-align:right; padding-top:3px;">
                            {{ $purchaseOrder->status === 'received' ? 'Fully Received & Stocked' : ucfirst($purchaseOrder->status) }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Official Accounting Signatures --}}
    <table class="signatures-table">
        <tr>
            <td class="sig-box" style="width:50%;">
                <div class="sig-label">Received & Stocked By:</div>
                <div class="sig-line"></div>
                <div class="sig-name">Storekeeper / Technician</div>
                <div class="sig-sub">Date: {{ $purchaseOrder->received_at ? $purchaseOrder->received_at->format('d M Y') : '____ / ____ / ________' }}</div>
            </td>
            <td class="sig-box" style="width:50%; text-align:right;">
                <div class="sig-label">Authorized Workshop Manager:</div>
                <div class="sig-line" style="margin-left:auto;"></div>
                <div class="sig-name">Teraju Setia Management</div>
                <div class="sig-sub">Approved for Accounts Payable</div>
            </td>
        </tr>
    </table>

    {{-- Footer --}}
    <div class="footer">
        <strong>Teraju Setia Enterprise</strong> &nbsp;|&nbsp; No. 1, Jalan Malinja 2, Taman Perindustrian Matahari, 86000 Kluang, Johor &nbsp;|&nbsp; Tel: 07-787 8728<br>
        Computer-generated Goods Receipt & Purchase Requisition Note &bull; Page 1 of 1
    </div>

</div>
</body>
</html>
