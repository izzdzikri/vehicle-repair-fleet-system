<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }}</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f3f4f6; padding: 24px 12px; margin: 0;">
    <table role="presentation" width="100%" style="max-width: 580px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
        <!-- Header -->
        <tr>
            <td style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%); padding: 26px 28px 22px; color: #ffffff;">
                <table role="presentation" width="100%">
                    <tr>
                        <td>
                            <p style="margin: 0; font-size: 11px; text-transform: uppercase; letter-spacing: 0.1em; color: #93c5fd; font-weight: 700;">Teraju Setia Workshop & Fleet</p>
                            <h1 style="margin: 6px 0 0; font-size: 20px; font-weight: 700; color: #ffffff;">Invoice {{ $invoice->invoice_number }}</h1>
                        </td>
                        <td style="text-align: right; vertical-align: middle;">
                            <span style="display: inline-block; background-color: {{ $invoice->balance <= 0 ? '#16a34a' : '#f59e0b' }}; color: #ffffff; padding: 5px 12px; border-radius: 9999px; font-size: 11px; font-weight: 700; text-transform: uppercase;">
                                {{ $invoice->balance <= 0 ? 'PAID' : 'DUE' }}
                            </span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <!-- Content -->
        <tr>
            <td style="padding: 28px;">
                <p style="font-size: 15px; color: #1f2937; margin: 0 0 16px;">
                    Hi {{ $invoice->customer_name }},
                </p>

                <p style="font-size: 14px; color: #4b5563; line-height: 1.6; margin: 0 0 20px;">
                    Thank you for your business! Your official invoice for recent maintenance/repair work has been generated. A copy of your PDF invoice is also attached to this email.
                </p>

                <table role="presentation" width="100%" style="border-collapse: collapse; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 24px;">
                    <tr>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-size: 13px;">Vehicle</td>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; text-align: right; font-weight: 600; color: #0f172a; font-size: 13px;">
                            {{ $invoice->jobCard->vehicle->plate_number ?? '—' }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-size: 13px;">Subtotal</td>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; text-align: right; color: #0f172a; font-size: 13px;">
                            RM {{ number_format($invoice->subtotal, 2) }}
                        </td>
                    </tr>
                    @if($invoice->tax_amount > 0)
                    <tr>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-size: 13px;">Tax ({{ $invoice->tax_rate }}%)</td>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; text-align: right; color: #0f172a; font-size: 13px;">
                            RM {{ number_format($invoice->tax_amount, 2) }}
                        </td>
                    </tr>
                    @endif
                    <tr>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; color: #0f172a; font-size: 14px; font-weight: 700;">Grand Total</td>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; text-align: right; font-weight: 700; color: #0f172a; font-size: 14px;">
                            RM {{ number_format($invoice->total, 2) }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; color: #16a34a; font-size: 13px; font-weight: 600;">Amount Paid</td>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; text-align: right; font-weight: 600; color: #16a34a; font-size: 13px;">
                            RM {{ number_format($invoice->amount_paid, 2) }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 12px 16px; color: {{ $invoice->balance > 0 ? '#dc2626' : '#16a34a' }}; font-size: 14px; font-weight: 700;">Balance Due</td>
                        <td style="padding: 12px 16px; text-align: right; font-weight: 700; color: {{ $invoice->balance > 0 ? '#dc2626' : '#16a34a' }}; font-size: 15px;">
                            RM {{ number_format($invoice->balance, 2) }}
                        </td>
                    </tr>
                </table>

                @if($invoice->due_date)
                <p style="font-size: 13px; color: #64748b; margin-bottom: 20px;">
                    📅 Due Date: <strong>{{ $invoice->due_date->format('d M Y') }}</strong>
                </p>
                @endif

                <p style="font-size: 13px; color: #94a3b8; text-align: center; margin: 16px 0 0;">
                    Questions regarding this invoice? Contact our accounts team at <strong>07-787 8728</strong>.
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
