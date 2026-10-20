<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Missing Expense Entry Alert</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f8fafc; padding: 24px; color: #1e293b;">
    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
        <div style="background: #dc2626; color: #ffffff; padding: 18px 24px;">
            <h2 style="margin: 0; font-size: 20px;">ACI Distribution Expense Monitoring</h2>
            <p style="margin: 4px 0 0; font-size: 13px; opacity: 0.9;">Automated Daily Cut-off Alert System</p>
        </div>
        <div style="padding: 24px;">
            <p style="font-size: 15px; line-height: 1.5;">Dear Depot In-Charge & HO Distribution Monitoring Team,</p>
            <p style="font-size: 14px; line-height: 1.6; color: #475569;">
                Our daily cut-off verification scheduler has detected that <strong>{{ $depot->name }}</strong> depot has logged <strong>0 expense entries</strong> for today (<strong>{{ $date }}</strong>).
            </p>
            
            <table style="width: 100%; border-collapse: collapse; margin: 20px 0; background: #f1f5f9; border-radius: 6px;">
                <tr>
                    <td style="padding: 10px 16px; font-weight: bold; width: 40%; border-bottom: 1px solid #e2e8f0;">Depot Location:</td>
                    <td style="padding: 10px 16px; border-bottom: 1px solid #e2e8f0;">{{ $depot->name }} ({{ $depot->code }})</td>
                </tr>
                <tr>
                    <td style="padding: 10px 16px; font-weight: bold; border-bottom: 1px solid #e2e8f0;">Contact Email:</td>
                    <td style="padding: 10px 16px; border-bottom: 1px solid #e2e8f0;">{{ $depot->contact_email }}</td>
                </tr>
                <tr>
                    <td style="padding: 10px 16px; font-weight: bold;">Status:</td>
                    <td style="padding: 10px 16px; color: #dc2626; font-weight: bold;">Non-Compliant (0 Entries Recorded)</td>
                </tr>
            </table>

            <p style="font-size: 13px; color: #64748b; line-height: 1.5;">
                Please ensure all physical vouchers and daily logistics costs incurred today are submitted immediately. Remember that backdated entry submission is strictly blocked by the system once the calendar day elapses.
            </p>

            <div style="margin-top: 24px; text-align: center;">
                <a href="{{ config('app.url') }}/expenses/create" style="display: inline-block; background: #1e3a8a; color: #ffffff; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 14px;">Log Expense Entry Now</a>
            </div>
        </div>
        <div style="background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 12px 24px; font-size: 12px; color: #94a3b8; text-align: center;">
            ACI Distribution & Showroom Expense Management System &bull; Automated Cron Engine
        </div>
    </div>
</body>
</html>
