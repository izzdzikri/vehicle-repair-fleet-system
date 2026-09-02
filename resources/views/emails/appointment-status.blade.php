<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family: Arial, sans-serif; background:#f3f4f6; padding:24px; margin:0;">
<table role="presentation" width="100%" style="max-width:520px;margin:0 auto;background:#ffffff;border-radius:8px;overflow:hidden;">
<tr><td style="background:#2563eb;padding:20px 24px;">
<h1 style="color:#fff;font-size:18px;margin:0;">Teraju Setia Enterprise</h1>
</td></tr>
<tr><td style="padding:24px;">
<p style="font-size:15px;color:#111827;margin-top:0;">Hi {{ $appointment->user->name ?? 'there' }},</p>
<p style="font-size:14px;color:#374151;">Your appointment has been <strong>{{ $statusLabel }}</strong>.</p>
<table role="presentation" width="100%" style="font-size:14px;color:#374151;border-collapse:collapse;margin:16px 0;">
<tr><td style="padding:6px 0;color:#9ca3af;">Service</td><td style="padding:6px 0;text-align:right;">{{ $appointment->service_type }}</td></tr>
<tr><td style="padding:6px 0;color:#9ca3af;">Vehicle</td><td style="padding:6px 0;text-align:right;">{{ $appointment->vehicle->plate_number ?? '—' }}</td></tr>
<tr><td style="padding:6px 0;color:#9ca3af;">Date</td><td style="padding:6px 0;text-align:right;">{{ \Carbon\Carbon::parse($appointment->date)->format('d M Y') }} at {{ $appointment->time }}</td></tr>
<tr><td style="padding:6px 0;color:#9ca3af;">Status</td><td style="padding:6px 0;text-align:right;font-weight:bold;">{{ $statusLabel }}</td></tr>
</table>
<p style="font-size:13px;color:#9ca3af;">If you have any questions, call us at 07-5551234.</p>
</td></tr>
<tr><td style="background:#f9fafb;padding:16px 24px;font-size:11px;color:#9ca3af;">
Teraju Setia Enterprise · No 14, Jalan Perindustrian 7, Senai, Johor
</td></tr>
</table>
</body>
</html>