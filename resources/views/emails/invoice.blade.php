<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family: Arial, sans-serif; background:#f3f4f6; padding:24px; margin:0;">
<table role="presentation" width="100%" style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:8px;overflow:hidden;">
<tr><td style="background:#2563eb;padding:20px 24px;">
<h1 style="color:#fff;font-size:18px;margin:0;">Teraju Setia Enterprise</h1>
<p style="color:#dbeafe;font-size:13px;margin:4px 0 0;">Invoice {{ $invoice->invoice_number }}</p>
</td></tr>
<tr><td style="padding:24px;">
<p style="font-size:15px;color:#111827;margin-top:0;">Hi {{ $invoice->customer_name }},</p>
<p style="font-size:14px;color:#374151;">Here is your invoice for the recent service at Teraju Setia Enterprise.</p>
<table role="presentation" width="100%" style="font-size:14px;color:#374151;border-collapse:collapse;margin:16px 0;">
<tr><td style="padding:6px 0;color:#9ca3af;">Vehicle</td><td style="padding:6px 0;text-align:right;">{{ $invoice->jobCard->vehicle->plate_number ?? '—' }}</td></tr>
<tr><td style="padding:6px 0;color:#9ca3af;">Subtotal</td><td style="padding:6px 0;text-align:right;">RM {{ number_format($invoice->subtotal, 2) }}</td></tr>
<tr><td style="padding:6px 0;color:#9ca3af;">Tax ({{ $invoice->tax_rate }}%)</td><td style="padding:6px 0;text-align:right;">RM {{ number_format($invoice->tax_amount, 2) }}</td></tr>
<tr><td style="padding:8px 0;color:#111827;font-weight:bold;border-top:1px solid #e5e7eb;">Total</td><td style="padding:8px 0;text-align:right;font-weight:bold;border-top:1px solid #e5e7eb;">RM {{ number_format($invoice->total, 2) }}</td></tr>
<tr><td style="padding:6px 0;color:#16a34a;">Paid</td><td style="padding:6px 0;text-align:right;color:#16a34a;">RM {{ number_format($invoice->amount_paid, 2) }}</td></tr>
<tr><td style="padding:6px 0;color:#dc2626;font-weight:bold;">Balance Due</td><td style="padding:6px 0;text-align:right;color:#dc2626;font-weight:bold;">RM {{ number_format($invoice->balance, 2) }}</td></tr>
</table>
@if($invoice->due_date)
<p style="font-size:13px;color:#6b7280;">Due date: {{ $invoice->due_date->format('d M Y') }}</p>
@endif
<p style="font-size:13px;color:#9ca3af;">Please log in to your account to view the full itemised invoice or make a payment. For questions, call 07-5551234.</p>
</td></tr>
<tr><td style="background:#f9fafb;padding:16px 24px;font-size:11px;color:#9ca3af;">
Teraju Setia Enterprise · No 14, Jalan Perindustrian 7, Senai, Johor
</td></tr>
</table>
</body>
</html>