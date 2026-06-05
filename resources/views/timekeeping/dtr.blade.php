<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Daily Time Record – {{ $employee->full_name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9pt;
            color: #000;
            background: #fff;
            padding: 20px 28px;
        }
        .form-header {
            text-align: center;
            margin-bottom: 8px;
        }
        .form-header .cs-form {
            font-size: 7.5pt;
            letter-spacing: 0.5px;
        }
        .form-header h1 {
            font-size: 13pt;
            font-weight: bold;
            letter-spacing: 1px;
            margin: 2px 0;
        }
        .form-header h2 {
            font-size: 10pt;
            font-weight: bold;
            letter-spacing: 2px;
            margin: 2px 0;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }
        .info-table td {
            padding: 2px 0;
            font-size: 9pt;
            vertical-align: bottom;
        }
        .info-table .label {
            font-size: 8pt;
            color: #444;
            padding-right: 4px;
            white-space: nowrap;
        }
        .info-table .value {
            border-bottom: 1px solid #000;
            min-width: 120px;
            padding-bottom: 1px;
        }
        .info-table .gap {
            width: 20px;
        }
        .dtr-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        .dtr-table th,
        .dtr-table td {
            border: 1px solid #000;
            text-align: center;
            padding: 2px 3px;
            font-size: 8.5pt;
        }
        .dtr-table th {
            font-weight: bold;
            background: #f0f0f0;
        }
        .dtr-table .group-header th {
            font-size: 8pt;
        }
        .dtr-table .sub-header th {
            font-size: 7.5pt;
            font-weight: normal;
        }
        .dtr-table td.day {
            font-weight: bold;
            width: 28px;
        }
        .dtr-table td.time-cell {
            width: 52px;
        }
        .dtr-table td.undertime-cell {
            width: 36px;
        }
        .dtr-table tr.total-row td {
            font-weight: bold;
            background: #f0f0f0;
        }
        .certification {
            margin-top: 10px;
            font-size: 8.5pt;
            line-height: 1.6;
        }
        .certification p {
            margin-bottom: 4px;
        }
        .sig-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }
        .sig-table td {
            width: 50%;
            vertical-align: top;
            padding-top: 6px;
            font-size: 8.5pt;
        }
        .sig-line {
            border-top: 1px solid #000;
            margin-top: 24px;
            padding-top: 2px;
            text-align: center;
        }
        .official-time {
            font-size: 8.5pt;
            margin-bottom: 2px;
        }
    </style>
</head>
<body>
    <div class="form-header">
        <div class="cs-form">Civil Service Form No. 48</div>
        <h1>DAILY TIME RECORD</h1>
    </div>

    <!-- Employee Info -->
    <table class="info-table">
        <tr>
            <td class="label">Name:</td>
            <td class="value" colspan="5">{{ strtoupper($employee->full_name) }}</td>
        </tr>
        <tr>
            <td class="label">Office/Department:</td>
            <td class="value">{{ $employee->department?->name ?? '' }}</td>
            <td class="gap"></td>
            <td class="label">For the Month of:</td>
            <td class="value">{{ strtoupper($month_name) }}</td>
        </tr>
        <tr>
            <td class="label">Position:</td>
            <td class="value">{{ $employee->position?->name ?? '' }}</td>
            <td class="gap"></td>
            <td class="label">Official Hours of Work:</td>
            <td></td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td class="gap"></td>
            <td>
                <div class="official-time">Regular Days: AM {{ $official_am }}</div>
                <div class="official-time">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;PM {{ $official_pm }}</div>
                <div class="official-time">Saturday &amp; Sunday: AS REQUIRED</div>
            </td>
            <td></td>
        </tr>
    </table>

    <!-- DTR Table -->
    <table class="dtr-table">
        <thead>
            <tr class="group-header">
                <th rowspan="2">Day</th>
                <th colspan="2">A.M.</th>
                <th colspan="2">P.M.</th>
                <th colspan="2">UNDERTIME</th>
            </tr>
            <tr class="sub-header">
                <th>Arrival</th>
                <th>Departure</th>
                <th>Arrival</th>
                <th>Departure</th>
                <th>Hours</th>
                <th>Minutes</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
            <tr>
                <td class="day">{{ $row['day'] }}</td>
                <td class="time-cell">{{ $row['morning_arrival'] }}</td>
                <td class="time-cell">{{ $row['morning_departure'] }}</td>
                <td class="time-cell">{{ $row['afternoon_arrival'] }}</td>
                <td class="time-cell">{{ $row['afternoon_departure'] }}</td>
                <td class="undertime-cell">{{ $row['undertime_hours'] > 0 ? $row['undertime_hours'] : '' }}</td>
                <td class="undertime-cell">{{ $row['undertime_minutes'] > 0 ? $row['undertime_minutes'] : '' }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="5" style="text-align:right; padding-right: 6px;">TOTAL</td>
                <td class="undertime-cell">{{ $total_undertime_hours > 0 ? $total_undertime_hours : '' }}</td>
                <td class="undertime-cell">{{ $total_undertime_minutes > 0 ? $total_undertime_minutes : '' }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Certification -->
    <div class="certification">
        <p>
            I CERTIFY on my honor that the above is a true and correct report of the hours of work
            performed, record of which was made daily at the time of arrival and departure from office.
        </p>

        <table class="sig-table">
            <tr>
                <td>
                    <div class="sig-line">{{ strtoupper($employee->full_name) }}</div>
                    <div style="text-align:center; font-size:7.5pt;">Employee's Signature</div>
                </td>
                <td style="padding-left: 24px;">
                    <p style="font-size: 8pt; margin-bottom: 6px;">
                        VERIFIED as to the prescribed office hours:
                    </p>
                    <div class="sig-line">{{ strtoupper($employee->supervisor?->full_name ?? '') }}</div>
                    <div style="text-align:center; font-size:7.5pt;">In-charge / Supervisor</div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
