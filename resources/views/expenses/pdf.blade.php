<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Expense Voucher #{{ $expense->id }}</title>
<style>
    /* Print-tuned for dompdf: core-font-safe glyphs only (no Bengali ৳ — use BDT) */
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1e293b; }
    .page { padding: 34px 40px; }

    .brandbar { background: #1149B0; color: #ffffff; border-radius: 10px; padding: 16px 22px; }
    .brandbar .company { font-size: 17px; font-weight: bold; letter-spacing: 1px; }
    .brandbar .tagline { font-size: 9px; color: #c7dbff; margin-top: 3px; letter-spacing: 0.6px; }
    .brandbar .doc-ref { text-align: right; font-size: 9px; color: #c7dbff; line-height: 1.7; }
    .brandbar .doc-ref strong { color: #ffffff; font-size: 11px; }

    h1 { font-size: 15px; margin: 18px 0 4px; color: #0f172a; }
    .sub { font-size: 10px; color: #64748b; margin-bottom: 14px; }

    .amount-band { border: 1.5px solid #1149B0; border-radius: 10px; background: #f4f8ff; padding: 14px 20px; margin-bottom: 16px; }
    .amount-band .label { font-size: 9px; font-weight: bold; color: #1149B0; letter-spacing: 1.6px; }
    .amount-band .amount { font-size: 24px; font-weight: bold; color: #0f172a; margin-top: 3px; }
    .amount-band .meta { font-size: 9px; color: #64748b; margin-top: 2px; }

    table.details { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    table.details td { border: 1px solid #e2e8f0; padding: 8px 12px; vertical-align: top; }
    table.details td.k { background: #f8fafc; width: 24%; font-size: 8.5px; font-weight: bold; color: #64748b; text-transform: uppercase; letter-spacing: 1px; }
    table.details td.v { font-size: 11.5px; font-weight: bold; color: #0f172a; width: 26%; }
    table.details td.v span { display: block; font-weight: normal; font-size: 9px; color: #64748b; margin-top: 1px; }

    .remarks { border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 14px; margin-bottom: 16px; }
    .remarks .k { font-size: 8.5px; font-weight: bold; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px; }
    .remarks p { font-size: 11px; line-height: 1.55; color: #334155; }

    .attachment { font-size: 10px; color: #334155; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 8px 12px; margin-bottom: 18px; }
    .attachment strong { color: #1149B0; }

    table.signatures { width: 100%; border-collapse: collapse; margin-top: 26px; }
    table.signatures td { width: 33%; text-align: center; padding: 0 10px; }
    table.signatures .line { border-bottom: 1px solid #94a3b8; height: 30px; margin-bottom: 5px; }
    table.signatures .role { font-size: 9.5px; font-weight: bold; color: #334155; }
    table.signatures .hint { font-size: 8px; color: #94a3b8; }

    .footer { position: fixed; bottom: 24px; left: 40px; right: 40px; border-top: 1px solid #e2e8f0; padding-top: 7px; font-size: 8px; color: #94a3b8; display: block; }
</style>
</head>
<body>
<div class="page">

    <table style="width:100%; border-collapse:collapse;">
        <tr>
            <td style="width:60%;">
                <div class="brandbar">
                    <div class="company">EXPENSE ADMIN</div>
                    <div class="tagline">DISTRIBUTION &amp; SHOWROOM EXPENSE MANAGEMENT SYSTEM</div>
                </div>
            </td>
            <td style="width:4%;"></td>
            <td style="width:36%;">
                <div class="brandbar" style="text-align:right;">
                    <div class="doc-ref">
                        <strong>EXPENSE VOUCHER</strong><br>
                        Tx ID: #{{ $expense->id }}<br>
                        Billing Period: {{ $expense->month }} {{ $expense->year }}
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <h1>Daily Expense Audit Record</h1>
    <div class="sub">Recorded on {{ $expense->date->format('l, d F Y') }} &bull; System entry timestamp: {{ $expense->created_at->format('Y-m-d H:i:s') }}</div>

    <div class="amount-band">
        <div class="label">INCURRED AMOUNT</div>
        <div class="amount">BDT {{ number_format($expense->amount, 2) }}</div>
        <div class="meta">Bangladeshi Taka &bull; In words exempt (system-generated figure)</div>
    </div>

    <table class="details">
        <tr>
            <td class="k">Depot / Location</td>
            <td class="v">{{ $expense->depot?->name }}<span>{{ $expense->depot?->code }} &bull; {{ $expense->depot?->contact_email }}</span></td>
            <td class="k">Business Unit</td>
            <td class="v">{{ $expense->business?->name }}<span>Code: {{ $expense->business?->code }}</span></td>
        </tr>
        <tr>
            <td class="k">Expense Head</td>
            <td class="v">{{ $expense->expenseHead?->name }}<span>Category Code: {{ $expense->expenseHead?->code }}</span></td>
            <td class="k">Logged Date</td>
            <td class="v">{{ $expense->date->format('Y-m-d') }}<span>Financial period: {{ $expense->month }} {{ $expense->year }}</span></td>
        </tr>
        <tr>
            <td class="k">Entered By</td>
            <td class="v">{{ $expense->user?->name ?? 'System' }}<span>{{ $expense->created_at->format('Y-m-d H:i:s') }}</span></td>
            <td class="k">Record Reference</td>
            <td class="v">EXP-{{ str_pad($expense->id, 6, '0', STR_PAD_LEFT) }}<span>System-generated voucher reference</span></td>
        </tr>
    </table>

    <div class="remarks">
        <div class="k">Remarks &amp; Details</div>
        <p>{{ $expense->remarks ?? 'No additional remarks provided.' }}</p>
    </div>

    @if($expense->voucher_path)
    <div class="attachment">
        <strong>Voucher receipt attached:</strong> {{ basename($expense->voucher_path) }} — available in the system (storage/app/public).
    </div>
    @endif

    <table class="signatures">
        <tr>
            <td><div class="line"></div><div class="role">Prepared By</div><div class="hint">Depot In-Charge</div></td>
            <td><div class="line"></div><div class="role">Verified By</div><div class="hint">Internal Audit</div></td>
            <td><div class="line"></div><div class="role">Approved By</div><div class="hint">Head Office Finance</div></td>
        </tr>
    </table>

    <div class="footer">
        System-generated voucher (Tx #{{ $expense->id }}) &bull; Generated {{ now()->format('Y-m-d H:i') }} &bull; Expense Admin — Distribution &amp; Showroom Expense Management System
    </div>

</div>
</body>
</html>
