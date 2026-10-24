<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Update</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f3f4f6; padding: 24px 12px; margin: 0;">
    <table role="presentation" width="100%" style="max-width: 580px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
        <!-- Header -->
        <tr>
            <td style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%); padding: 26px 28px 22px; color: #ffffff;">
                <p style="margin: 0; font-size: 11px; text-transform: uppercase; letter-spacing: 0.1em; color: #93c5fd; font-weight: 700;">Teraju Setia Workshop & Fleet</p>
                <h1 style="margin: 6px 0 0; font-size: 20px; font-weight: 700; color: #ffffff;">Appointment {{ $statusLabel }}</h1>
            </td>
        </tr>

        <!-- Content -->
        <tr>
            <td style="padding: 28px;">
                <p style="font-size: 15px; color: #1f2937; margin: 0 0 16px;">
                    Hi {{ $appointment->user->name ?? ($appointment->walkin_name ?? 'there') }},
                </p>

                <p style="font-size: 14px; color: #4b5563; line-height: 1.6; margin: 0 0 20px;">
                    Your service appointment at Teraju Setia Enterprise has been updated to 
                    <strong style="color: #2563eb; text-transform: uppercase;">{{ $statusLabel }}</strong>.
                </p>

                <table role="presentation" width="100%" style="border-collapse: collapse; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 24px;">
                    <tr>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-size: 13px;">Booking ID</td>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; text-align: right; font-weight: 700; color: #0f172a; font-size: 13px;">#APT-{{ str_pad($appointment->id, 5, '0', STR_PAD_LEFT) }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-size: 13px;">Requested Service</td>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; text-align: right; font-weight: 600; color: #2563eb; font-size: 13px;">{{ $appointment->service_type }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-size: 13px;">Vehicle</td>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; text-align: right; font-weight: 600; color: #0f172a; font-size: 13px;">{{ $appointment->vehicle->plate_number ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-size: 13px;">Scheduled Slot</td>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; text-align: right; font-weight: 600; color: #0f172a; font-size: 13px;">
                            {{ \Carbon\Carbon::parse($appointment->date)->format('d M Y') }} at {{ $appointment->time }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 12px 16px; color: #64748b; font-size: 13px;">Status</td>
                        <td style="padding: 12px 16px; text-align: right; font-weight: 700; color: {{ $statusLabel === 'Cancelled' ? '#dc2626' : ($statusLabel === 'Confirmed' ? '#16a34a' : '#2563eb') }}; font-size: 13px;">
                            {{ $statusLabel }}
                        </td>
                    </tr>
                </table>

                @if($appointment->notes)
                <div style="background-color: #f1f5f9; padding: 12px 16px; border-radius: 6px; margin-bottom: 20px;">
                    <p style="margin: 0; font-size: 12px; color: #475569;"><strong>Notes:</strong> {{ $appointment->notes }}</p>
                </div>
                @endif

                <table role="presentation" width="100%" style="margin: 20px 0 10px;">
                    <tr>
                        <td style="text-align: center;">
                            @php
                                $rolePath = ($appointment->user && $appointment->user->role === 'corporate') ? 'client' : 'customer';
                            @endphp
                            <a href="{{ config('app.url') }}/{{ $rolePath }}/appointments/{{ $appointment->id }}"
                               style="display: inline-block; background-color: #2563eb; color: #ffffff; padding: 11px 26px; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 13px;">
                                View Appointment Details
                            </a>
                        </td>
                    </tr>
                </table>

                <p style="font-size: 13px; color: #94a3b8; text-align: center; margin: 16px 0 0;">
                    Need to reschedule or have questions? Contact us at <strong>07-787 8728</strong>.
                </p>
            </td>
        </tr>

        <!-- Footer -->
        <tr>
            <td style="background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 18px 28px; font-size: 12px; color: #94a3b8; line-height: 1.5;">
                <p style="margin: 0 0 4px; font-weight: 600; color: #64748b;">Teraju Setia Enterprise</p>
                <p style="margin: 0;">No. 1, Jalan Malinja 2, Taman Perindustrian Matahari, 86000 Kluang, Johor Darul Ta'zim</p>
            </td>
        </tr>
    </table>
</body>
</html>
