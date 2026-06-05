<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip - {{ $item->employee->last_name }}, {{ $item->employee->first_name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #111; }
        .container { padding: 24px; }

        /* Header */
        .header { text-align: center; border-bottom: 2px solid #111; padding-bottom: 12px; margin-bottom: 16px; }
        .header .company-name { font-size: 16px; font-weight: bold; text-transform: uppercase; }
        .header .title { font-size: 13px; margin-top: 4px; letter-spacing: 2px; text-transform: uppercase; }
        .header .period { font-size: 10px; margin-top: 4px; color: #444; }

        /* Employee info */
        .info-grid { display: table; width: 100%; margin-bottom: 16px; }
        .info-col { display: table-cell; width: 50%; vertical-align: top; }
        .info-row { margin-bottom: 4px; }
        .info-label { font-weight: bold; display: inline-block; width: 120px; }

        /* Tables */
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th { background-color: #222; color: #fff; padding: 5px 8px; text-align: left; font-size: 10px; text-transform: uppercase; }
        td { padding: 4px 8px; border-bottom: 1px solid #e0e0e0; }
        tr:nth-child(even) td { background-color: #f8f8f8; }
        .amount { text-align: right; }
        .section-title { font-weight: bold; font-size: 11px; margin-bottom: 4px; margin-top: 12px; text-transform: uppercase; letter-spacing: 1px; }

        /* Summary */
        .summary { margin-top: 16px; border-top: 2px solid #111; padding-top: 12px; }
        .summary-row { display: table; width: 100%; margin-bottom: 6px; }
        .summary-label { display: table-cell; font-weight: bold; }
        .summary-value { display: table-cell; text-align: right; }
        .net-pay { font-size: 15px; font-weight: bold; background-color: #111; color: #fff; padding: 8px 12px; margin-top: 10px; }
        .net-pay .summary-label, .net-pay .summary-value { color: #fff; }

        /* Footer */
        .footer { margin-top: 32px; border-top: 1px solid #ccc; padding-top: 12px; font-size: 10px; color: #666; text-align: center; }
        .signatures { display: table; width: 100%; margin-top: 32px; }
        .sig-col { display: table-cell; width: 33%; text-align: center; }
        .sig-line { border-top: 1px solid #111; margin: 0 16px; margin-top: 32px; padding-top: 4px; }
    </style>
</head>
<body>
<div class="container">

    {{-- Header --}}
    <div class="header">
        <div class="company-name">{{ $item->employee->company->name ?? 'Company' }}</div>
        <div class="title">Employee Payslip</div>
        <div class="period">
            Pay Period: {{ $item->period->start_date->format('F d, Y') }} &ndash; {{ $item->period->end_date->format('F d, Y') }}
            &nbsp;|&nbsp;
            Pay Date: {{ $item->period->pay_date->format('F d, Y') }}
        </div>
    </div>

    {{-- Employee Information --}}
    <div class="info-grid">
        <div class="info-col">
            <div class="info-row">
                <span class="info-label">Employee:</span>
                {{ $item->employee->last_name }}, {{ $item->employee->first_name }}
                {{ $item->employee->middle_name ? $item->employee->middle_name[0].'.' : '' }}
            </div>
            <div class="info-row">
                <span class="info-label">Employee ID:</span>
                {{ $item->employee->employee_id }}
            </div>
            <div class="info-row">
                <span class="info-label">Department:</span>
                {{ $item->employee->department->name ?? '—' }}
            </div>
            <div class="info-row">
                <span class="info-label">Position:</span>
                {{ $item->employee->position->name ?? '—' }}
            </div>
        </div>
        <div class="info-col">
            <div class="info-row">
                <span class="info-label">SSS No.:</span>
                {{ $item->employee->sss_number ?? '—' }}
            </div>
            <div class="info-row">
                <span class="info-label">PhilHealth No.:</span>
                {{ $item->employee->philhealth_number ?? '—' }}
            </div>
            <div class="info-row">
                <span class="info-label">Pag-IBIG No.:</span>
                {{ $item->employee->pagibig_number ?? '—' }}
            </div>
            <div class="info-row">
                <span class="info-label">TIN:</span>
                {{ $item->employee->tin ?? '—' }}
            </div>
        </div>
    </div>

    {{-- Attendance Summary --}}
    <div class="section-title">Attendance Summary</div>
    <table>
        <thead>
            <tr>
                <th>Days Worked</th>
                <th>Days Absent</th>
                <th>Total Hours</th>
                <th>Minutes Late</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ number_format($item->days_worked, 1) }}</td>
                <td>{{ number_format($item->days_absent, 1) }}</td>
                <td>{{ number_format($item->total_hours, 2) }}</td>
                <td>{{ $item->minutes_late }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Taxable Earnings --}}
    <div class="section-title">Taxable Earnings</div>
    <table>
        <thead>
            <tr>
                <th style="width:60%">Description</th>
                <th>Hours</th>
                <th class="amount">Amount (₱)</th>
            <tr>
                <td>{{ $earning->description }}</td>
                <td>{{ $earning->hours !== null ? number_format($earning->hours, 2) : '—' }}</td>
                <td class="amount">{{ number_format($earning->amount, 2) }}</td>
            </tr>
            @endforeach
            @if ($item->earnings->where('is_taxable', true)->isEmpty())
            <tr><td colspan="3" style="text-align:center;color:#888;">None</td></tr>
            @endif
        </tbody>
    </table>

    {{-- Non-Taxable Benefits --}}
    @if ($item->earnings->where('is_taxable', false)->isNotEmpty())
    <div class="section-title">Non-Taxable Benefits</div>
    <table>
        <thead>
            <tr>
                <th style="width:60%">Description</th>
                <th>Hours</th>
                <th class="amount">Amount (₱)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($item->earnings->where('is_taxable', false) as $earning)
            <tr>
                <td>{{ $earning->description }}</td>
                <td>{{ $earning->hours !== null ? number_format($earning->hours, 2) : '—' }}</td>
                <td class="amount">{{ number_format($earning->amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- Deductions --}}
    <div class="section-title">Deductions</div>
    <table>
        <thead>
            <tr>
                <th style="width:70%">Description</th>
                <th class="amount">Amount (₱)</th>
            <tr>
                <td>{{ $deduction->description }}</td>
                <td class="amount">{{ number_format($deduction->amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Net Pay Summary --}}
    <div class="summary">
        <div class="summary-row">
            <span class="summary-label">Gross Pay</span>
            <span class="summary-value">₱ {{ number_format($item->gross_pay, 2) }}</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Total Deductions</span>
            <span class="summary-value">₱ {{ number_format($item->total_deductions, 2) }}</span>
        </div>
        <div class="summary-row net-pay">
            <span class="summary-label">NET PAY</span>
            <span class="summary-value">₱ {{ number_format($item->net_pay, 2) }}</span>
        </div>
    </div>

    {{-- Signatures --}}
    <div class="signatures">
        <div class="sig-col">
            <div class="sig-line">Prepared by</div>
        </div>
        <div class="sig-col">
            <div class="sig-line">Approved by</div>
        </div>
        <div class="sig-col">
            <div class="sig-line">Received by / Employee Signature</div>
        </div>
    </div>

    <div class="footer">
        This payslip is computer-generated and is valid without signature unless otherwise indicated.
        Generated on {{ now()->format('F d, Y \a\t h:i A') }}.
    </div>

</div>
</body>
</html>
