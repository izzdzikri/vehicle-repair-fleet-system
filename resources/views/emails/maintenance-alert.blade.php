<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Alert</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f3f4f6; padding: 24px 12px; margin: 0;">
    <table role="presentation" width="100%" style="max-width: 580px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
        <!-- Header -->
        <tr>
            <td style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%); padding: 28px 28px 24px; color: #ffffff;">
                <table role="presentation" width="100%">
                    <tr>
                        <td>
                            <p style="margin: 0; font-size: 11px; text-transform: uppercase; letter-spacing: 0.1em; color: #93c5fd; font-weight: 700;">Teraju Setia Workshop & Fleet</p>
                            <h1 style="margin: 6px 0 0; font-size: 20px; font-weight: 700; color: #ffffff;">Predictive Maintenance Alert</h1>
                        </td>
                        <td style="text-align: right; vertical-align: top;">
                            <span style="display: inline-block; background-color: {{ $alert->urgency === 'high' ? '#ef4444' : '#f59e0b' }}; color: #ffffff; padding: 4px 10px; border-radius: 9999px; font-size: 11px; font-weight: 700; text-transform: uppercase;">
                                {{ ucfirst($alert->urgency) }} Priority
                            </span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <!-- Body -->
        <tr>
            <td style="padding: 28px;">
                <p style="font-size: 15px; color: #1f2937; margin: 0 0 16px;">
                    Hi {{ $vehicle->owner->name ?? 'Valued Customer' }},
                </p>

                <div style="background-color: {{ $alert->urgency === 'high' ? '#fef2f2' : '#fefce8' }}; border-left: 4px solid {{ $alert->urgency === 'high' ? '#ef4444' : '#f59e0b' }}; padding: 14px 16px; border-radius: 4px; margin-bottom: 22px;">
                    <p style="margin: 0; font-size: 14px; line-height: 1.5; color: {{ $alert->urgency === 'high' ? '#991b1b' : '#854d0e' }}; font-weight: 500;">
                        {{ $alert->recommendation }}
                    </p>
                </div>

                <!-- Vehicle Card -->
                <table role="presentation" width="100%" style="border-collapse: collapse; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 24px;">
                    <tr>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-size: 13px;">Vehicle</td>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; text-align: right; font-weight: 700; color: #0f172a; font-size: 14px;">
                            {{ $vehicle->plate_number }} ({{ $vehicle->brand }} {{ $vehicle->model }})
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-size: 13px;">Scheduled Service</td>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; text-align: right; font-weight: 600; color: #2563eb; font-size: 13px;">
                            {{ $jobType->name }}
                        </td>
                    </tr>
                    @if($alert->predicted_due_date)
                    <tr>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-size: 13px;">Target Due Date</td>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; text-align: right; font-weight: 600; color: #0f172a; font-size: 13px;">
                            {{ \Carbon\Carbon::parse($alert->predicted_due_date)->format('d M Y') }}
                        </td>
                    </tr>
                    @endif
                    <tr>
                        <td style="padding: 12px 16px; color: #64748b; font-size: 13px;">Recorded Odometer</td>
                        <td style="padding: 12px 16px; text-align: right; color: #0f172a; font-size: 13px;">
                            {{ number_format($vehicle->mileage) }} km
                        </td>
                    </tr>
                </table>

                <!-- Action Button -->
                <table role="presentation" width="100%" style="margin: 20px 0 10px;">
                    <tr>
                        <td style="text-align: center;">
                            <a href="{{ config('app.url') }}/{{ ($vehicle->owner && $vehicle->owner->role === 'corporate') ? 'client' : 'customer' }}/appointments/create?vehicle_id={{ $vehicle->id }}"
                               style="display: inline-block; background-color: #2563eb; color: #ffffff; padding: 12px 28px; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 14px; box-shadow: 0 2px 4px rgba(37, 99, 235, 0.3);">
                                📅 Book Maintenance Appointment Now
                            </a>
                        </td>
                    </tr>
                </table>

                <p style="font-size: 12px; color: #94a3b8; text-align: center; margin: 16px 0 0;">
                    Preventive servicing keeps your vehicle safe and prevents costly unexpected breakdowns.
                </p>
            </td>
        </tr>

        <!-- Footer -->
        <tr>
            <td style="background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 18px 28px; font-size: 12px; color: #94a3b8; line-height: 1.5;">
                <p style="margin: 0 0 4px; font-weight: 600; color: #64748b;">Teraju Setia Enterprise</p>
                <p style="margin: 0;">No. 1, Jalan Malinja 2, Taman Perindustrian Matahari, 86000 Kluang, Johor Darul Ta'zim</p>
                <p style="margin: 4px 0 0;">Inquiries / Hotline: <strong>07-787 8728</strong></p>
            </td>
        </tr>
    </table>
</body>
</html>
