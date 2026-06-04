<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt - {{ $receipt->receipt_number }}</title>
    <style>
        @page {
            size: 80mm auto;
            margin: 0;
        }
        body {
            font-family: 'Courier New', Courier, monospace;
            width: 74mm;
            margin: 3mm;
            font-size: 12px;
            line-height: 1.4;
            color: #000;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .divider {
            border-top: 1px dashed #000;
            margin: 5px 0;
        }
        .header h2 { margin: 2px 0; font-size: 16px; }
        .header p { margin: 2px 0; font-size: 11px; }
        .details-table { width: 100%; margin-top: 8px; }
        .details-table td { padding: 2px 0; }
        .total-section { font-size: 13px; font-weight: bold; }
        @media print {
            body { margin: 0; width: 80mm; }
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print();">

    <div class="no-print" style="padding: 10px; background: #f0f0f0; margin-bottom: 10px; text-align: center;">
        <button onclick="window.print()">Print Receipt</button>
        <a href="{{ route('fees.receipts.show', $receipt->receipt_id) }}">Back to Receipt</a>
    </div>
           <div class="header text-center">

    <img src="{{ public_path('build/assets/school/logo.png') }}"
         alt="School Logo"
         style="width:60px;height:60px;margin-bottom:5px;">

    <h2>VIKAS HIGH SCHOOL</h2>

    <p>Main Road, School Campus</p>
    <p>Contact: +91 XXXXXXXXXX</p>

    <h3>FEE RECEIPT</h3>

    @if($receipt->status === 'CANCELLED')
        <h2 style="color: red; border: 2px solid red; padding: 3px; display: inline-block;">
            ** CANCELLED **
        </h2>
    @endif

    @if($receipt->is_duplicate)
        <div style="border: 1px solid #000; padding: 2px; margin: 5px 0;">
            *** DUPLICATE RECEIPT ***<br>
            Print Count: {{ $receipt->printed_count }}<br>
            Original: {{ $receipt->generated_datetime->format('d-M-Y H:i') }}
        </div>
    @endif

</div>
        <p>Main Road, School Campus</p>
        <p>Contact: +91 XXXXXXXXXX</p>
        <h3>FEE RECEIPT</h3>
        @if($receipt->status === 'CANCELLED')
            <h2 style="color: red; border: 2px solid red; padding: 3px; display: inline-block;">** CANCELLED **</h2>
        @endif
        @if($receipt->is_duplicate)
            <div style="border: 1px solid #000; padding: 2px; margin: 5px 0;">
                *** DUPLICATE RECEIPT ***<br>
                Print Count: {{ $receipt->printed_count }}<br>
                Original: {{ $receipt->generated_datetime->format('d-M-Y H:i') }}
            </div>
        @endif
    </div>

    <div class="divider"></div>

    <table class="details-table">
        <tr>
            <td>Receipt No:</td>
            <td class="text-right"><strong>{{ $receipt->receipt_number }}</strong></td>
        </tr>
        <tr>
            <td>Date:</td>
            <td class="text-right">{{ $receipt->payment?->payment_date?->format('d-M-Y h:i A') ?? '-' }}</td>
        </tr>
        <tr>
            <td>Student Name:</td>
            <td class="text-right">{{ $receipt->payment?->feeAccount?->student?->student_name ?? '-' }}</td>
        </tr>
        <tr>
            <td>Admission No:</td>
            <td class="text-right">{{ $receipt->payment?->feeAccount?->student?->admission_no ?? '-' }}</td>
        </tr>
        <tr>
            <td>Academic Year:</td>
            <td class="text-right">{{ $receipt->payment?->feeAccount?->academicYear?->year_name ?? '-' }}</td>
        </tr>
    </table>

    <div class="divider"></div>

    <table class="details-table">
        <tr style="font-weight: bold;">
            <td>Description</td>
            <td class="text-right">Amount</td>
        </tr>
        <tr>
            <td>Fee Payment ({{ $receipt->payment?->payment_mode ?? '-' }})</td>
            <td class="text-right">₹{{ number_format($receipt->payment?->amount ?? 0, 2) }}</td>
        </tr>
        @if($receipt->payment?->transaction_reference)
            <tr>
                <td colspan="2" style="font-size: 10px; color: #555;">Ref: {{ $receipt->payment->transaction_reference }}</td>
            </tr>
        @endif
    </table>

    <div class="divider"></div>

    <table class="details-table total-section">
        <tr>
            <td>Total Paid:</td>
            <td class="text-right">₹{{ number_format($receipt->payment?->amount ?? 0, 2) }}</td>
        </tr>
        <tr style="font-weight: normal; font-size: 11px;">
            <td>Total Remaining Balance:</td>
            <td class="text-right">₹{{ number_format($receipt->payment?->feeAccount?->remaining_balance ?? 0, 2) }}</td>
        </tr>
    </table>

    <div class="divider"></div>

    <div class="text-center" style="margin-top: 15px; font-size: 11px;">
        <p>Thank you!</p>
        <p>Issued by: {{ $receipt->payment?->collector?->full_name ?? $receipt->payment?->collector?->username ?? '-' }}</p>
        <p><em>This is a computer-generated receipt.</em></p>
    </div>

</body>
</html>