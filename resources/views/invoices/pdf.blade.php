<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: DejaVu Sans, Helvetica, Arial, sans-serif; font-size: 12px; color: #1f2937; background: #fff; }
        .page { padding: 40px 48px; }
        .header { width: 100%; border-bottom: 2px solid #2563eb; padding-bottom: 20px; margin-bottom: 24px; }
        .header-table { width: 100%; }
        .workshop-name { font-size: 20px; font-weight: bold; color: #1d4ed8; }
        .workshop-meta { font-size: 10px; color: #6b7280; line-height: 1.7; margin-top: 4px; }
        .invoice-label { font-size: 24px; font-weight: bold; color: #111827; text-align: right; }
        .invoice-number { font-size: 14px; color: #2563eb; font-weight: bold; text-align: right; }
        .invoice-meta { font-size: 10px; color: #6b7280; text-align: right; line-height: 1.7; margin-top: 4px; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 10px; font-weight: bold; letter-spacing: 0.5px; }
        .badge-paid    { background: #dcfce7; color: #15803d; }
        .badge-partial { background: #fef9c3; color: #854d0e; }
        .badge-unpaid  { background: #fee2e2; color: #991b1b; }
        .info-cell { padding: 12px 14px; background: #f9fafb; border-radius: 6px; vertical-align: top; }
        .info-label { font-size: 9px; text-transform: uppercase; letter-spacing: 0.8px; color: #9ca3af; font-weight: bold; margin-bottom: 4px; }
        .info-value { font-size: 12px; font-weight: 600; color: #111827; }
        .info-sub   { font-size: 10px; color: #6b7280; margin-top: 2px; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table thead tr { background: #1d4ed8; color: #fff; }
        .items-table thead th { padding: 9px 12px; text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: bold; }
        .items-table thead th.right { text-align: right; }
        .items-table tbody tr { border-bottom: 1px solid #e5e7eb; }
        .items-table tbody tr.even { background: #f9fafb; }
        .items-table tbody td { padding: 9px 12px; font-size: 11px; }
        .items-table tbody td.right { text-align: right; }
        .items-label { font-size: 9px; color: #9ca3af; margin-top: 1px; }
        .totals-lbl { font-size: 11px; color: #6b7280; padding: 4px 0; }
        .totals-val { font-size: 11px; color: #111827; text-align: right; padding: 4px 0; font-weight: 500; }
        .section-title { font-size: 11px; font-weight: bold; color: #374151; text-transform: uppercase; letter-spacing: 0.5px; margin: 24px 0 8px; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px; }
        .payment-meta { font-size: 9px; color: #9ca3af; }
        .footer { margin-top: 36px; border-top: 1px solid #e5e7eb; padding-top: 14px; font-size: 9px; color: #9ca3af; text-align: center; line-height: 1.7; }
        .watermark { position: fixed; top: 36%; left: 8%; font-size: 80px; font-weight: bold; color: rgba(22, 163, 74, 0.07); transform: rotate(-30deg); text-transform: uppercase; letter-spacing: 10px; }
    </style>
</head>
<body>
<div class="page">

    @if($invoice->status === 'paid')
    <div class="watermark">PAID</div>
    @endif

    {{-- Header --}}
    <div class="header">
        <table class="header-table">
            <tr>
                <td style="width:60%; vertical-align:top;">
                    <div class="workshop-name">Teraju Setia Enterprise</div>
                    <div class="workshop-meta">
                        No 14, Jalan Perindustrian 7, Kawasan Perindustrian Senai<br>
                        81400 Senai, Johor, Malaysia<br>
                        Tel: 07-555 1234 &nbsp;|&nbsp; admin@terajusetia.my
                    </div>
                </td>
                <td style="width:40%; vertical-align:top;">
                    <div class="invoice-label">INVOICE</div>
                    <div class="invoice-number">{{ $invoice->invoice_number }}</div>
                    <div class="invoice-meta">
                        Issued: {{ $invoice->created_at->format('d M Y') }}<br>
                        @if($invoice->due_date)Due: {{ $invoice->due_date->format('d M Y') }}<br>@endif
                    </div>
                    <div style="text-align:right; margin-top:4px;">
                        <span class="badge {{ $invoice->status === 'paid' ? 'badge-paid' : ($invoice->status === 'partial' ? 'badge-partial' : 'badge-unpaid') }}">
                            {{ strtoupper($invoice->status) }}
                        </span>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Billed To / Vehicle --}}
    <table style="width:100%; margin-bottom:24px;">
        <tr>
            <td class="info-cell" style="width:48%;">
                <div class="info-label">Billed To</div>
                <div class="info-value">{{ $invoice->customer_name }}</div>
                @if($invoice->jobCard->appointment && !$invoice->jobCard->appointment->is_walkin)
                    @if($invoice->jobCard->appointment->user?->email)
                    <div class="info-sub">{{ $invoice->jobCard->appointment->user->email }}</div>
                    @endif
                    @if($invoice->jobCard->appointment->user?->contact_no)
                    <div class="info-sub">{{ $invoice->jobCard->appointment->user->contact_no }}</div>
                    @endif
                @elseif($invoice->jobCard->appointment?->is_walkin)
                    <div class="info-sub">{{ $invoice->jobCard->appointment->walkin_contact ?? '' }} (Walk-in)</div>
                @endif
            </td>
            <td style="width:4%;"></td>
            <td class="info-cell" style="width:48%;">
                <div class="info-label">Vehicle / Job Card</div>
                <div class="info-value">{{ $invoice->jobCard->vehicle->plate_number ?? '—' }}</div>
                <div class="info-sub">
                    {{ $invoice->jobCard->vehicle->brand ?? '' }} {{ $invoice->jobCard->vehicle->model ?? '' }}
                    &bull; Job Card #{{ $invoice->jobCard->id }}
                </div>
                <div class="info-sub">{{ $invoice->jobCard->jobType->name ?? $invoice->jobCard->appointment->service_type ?? '—' }}</div>
            </td>
        </tr>
    </table>

    {{-- Line Items --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:50%;">Description</th>
                <th class="right" style="width:10%;">Qty</th>
                <th class="right" style="width:20%;">Unit Price</th>
                <th class="right" style="width:20%;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->jobCard->parts as $i => $p)
            <tr class="{{ $i % 2 === 1 ? 'even' : '' }}">
                <td>
                    {{ $p->sparePart->name ?? 'Spare Part' }}
                    @if($p->sparePart?->part_number)
                    <div class="items-label">Part No: {{ $p->sparePart->part_number }}</div>
                    @endif
                </td>
                <td class="right">{{ $p->quantity }}</td>
                <td class="right">RM {{ number_format($p->unit_price, 2) }}</td>
                <td class="right">RM {{ number_format($p->quantity * $p->unit_price, 2) }}</td>
            </tr>
            @endforeach
            @foreach($invoice->jobCard->labourCharges as $i => $l)
            <tr class="{{ ($i + $invoice->jobCard->parts->count()) % 2 === 1 ? 'even' : '' }}">
                <td>
                    {{ $l->description }}
                    <div class="items-label">Labour charge</div>
                </td>
                <td class="right">1</td>
                <td class="right">RM {{ number_format($l->charge, 2) }}</td>
                <td class="right">RM {{ number_format($l->charge, 2) }}</td>
            </tr>
            @endforeach
            @if($invoice->jobCard->parts->isEmpty() && $invoice->jobCard->labourCharges->isEmpty())
            <tr>
                <td colspan="4" style="text-align:center; color:#9ca3af; padding:16px;">No line items recorded.</td>
            </tr>
            @endif
        </tbody>
    </table>

    {{-- Totals --}}
    <table style="width:100%;">
        <tr>
            <td style="width:55%;"></td>
            <td style="width:45%;">
                <table style="width:100%; border-collapse:collapse;">
                    <tr>
                        <td class="totals-lbl">Subtotal</td>
                        <td class="totals-val">RM {{ number_format($invoice->subtotal, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="totals-lbl">Tax ({{ $invoice->tax_rate }}%)</td>
                        <td class="totals-val">RM {{ number_format($invoice->tax_amount, 2) }}</td>
                    </tr>
                    <tr style="border-top: 2px solid #1d4ed8;">
                        <td style="font-size:13px; font-weight:bold; color:#111827; padding-top:8px;">Total</td>
                        <td style="font-size:13px; font-weight:bold; color:#1d4ed8; text-align:right; padding-top:8px;">RM {{ number_format($invoice->total, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="font-size:11px; color:#16a34a; font-weight:600; padding-top:4px;">Amount Paid</td>
                        <td style="font-size:11px; color:#16a34a; font-weight:600; text-align:right; padding-top:4px;">RM {{ number_format($invoice->amount_paid, 2) }}</td>
                    </tr>
                    <tr style="border-top: 1px solid #e5e7eb;">
                        <td style="font-size:11px; color:#dc2626; font-weight:600; padding-top:4px;">Balance Due</td>
                        <td style="font-size:11px; color:#dc2626; font-weight:600; text-align:right; padding-top:4px;">RM {{ number_format($invoice->balance, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Payment History --}}
    @if($invoice->payments->isNotEmpty())
    <div class="section-title">Payment History</div>
    <table style="width:100%; border-collapse:collapse;">
        @foreach($invoice->payments as $pay)
        <tr style="border-bottom: 1px solid #f3f4f6;">
            <td style="padding:5px 0; font-size:11px;">
                RM {{ number_format($pay->amount, 2) }} — {{ ucfirst(str_replace('_', ' ', $pay->method)) }}
                <div class="payment-meta">
                    {{ $pay->paid_at->format('d M Y, H:i') }}
                    @if($pay->reference_no) &bull; Ref: {{ $pay->reference_no }} @endif
                    @if($pay->recorder) &bull; by {{ $pay->recorder->name }} @endif
                </div>
            </td>
            <td style="padding:5px 0; font-size:11px; text-align:right; font-weight:500;">
                RM {{ number_format($pay->amount, 2) }}
            </td>
        </tr>
        @endforeach
    </table>
    @endif

    {{-- Footer --}}
    <div class="footer">
        <strong>Teraju Setia Enterprise</strong> &nbsp;|&nbsp; No 14, Jalan Perindustrian 7, 81400 Senai, Johor &nbsp;|&nbsp; Tel: 07-555 1234<br>
        This invoice was generated electronically and is valid without a physical signature.<br>
        Thank you for choosing Teraju Setia Enterprise.
    </div>

</div>
</body>
</html>
