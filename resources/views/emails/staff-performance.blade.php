<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family: Arial, sans-serif; background:#f3f4f6; padding:24px; margin:0;">
<table role="presentation" width="100%" style="max-width:640px;margin:0 auto;background:#ffffff;border-radius:8px;overflow:hidden;">
<tr><td style="background:#111827;padding:20px 24px;">
<h1 style="color:#fff;font-size:18px;margin:0;">Staff Performance Report</h1>
<p style="color:#9ca3af;font-size:13px;margin:4px 0 0;">{{ $periodLabel }} · Teraju Setia Enterprise</p>
</td></tr>
<tr><td style="padding:24px;">
<table role="presentation" width="100%" style="font-size:13px;color:#374151;border-collapse:collapse;">
<tr style="text-align:left;color:#9ca3af;border-bottom:1px solid #e5e7eb;">
<th style="padding:8px 4px;">Staff</th>
<th style="padding:8px 4px;">Completed</th>
<th style="padding:8px 4px;">On-Time</th>
<th style="padding:8px 4px;">Overdue</th>
<th style="padding:8px 4px;">Avg Turnaround</th>
<th style="padding:8px 4px;">Attendance</th>
</tr>
@foreach($report as $r)
<tr style="border-bottom:1px solid #f3f4f6;">
<td style="padding:8px 4px;font-weight:bold;">{{ $r->staff->name }}</td>
<td style="padding:8px 4px;">{{ $r->total_jobs }}</td>
<td style="padding:8px 4px;color:#16a34a;">{{ $r->on_time }}</td>
<td style="padding:8px 4px;color:#dc2626;">{{ $r->overdue }}</td>
<td style="padding:8px 4px;">{{ $r->avg_hours !== null ? $r->avg_hours.' hrs' : '—' }}</td>
<td style="padding:8px 4px;">{{ $r->attendance_rate !== null ? $r->attendance_rate.'%' : '—' }}</td>
</tr>
@endforeach
</table>
<p style="font-size:12px;color:#9ca3af;margin-top:16px;">Generated automatically from the Teraju Setia Enterprise workshop management system.</p>
</td></tr>
</table>
</body>
</html>