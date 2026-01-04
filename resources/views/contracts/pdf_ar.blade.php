<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>عقد إيجار - Contract {{ $contract->id }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Arial', 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
            padding: 20px;
            direction: rtl;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #333;
            padding-bottom: 20px;
        }
        .header h1 {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .header h2 {
            font-size: 18px;
            color: #666;
        }
        .section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f5f5f5;
            border-right: 4px solid #333;
        }
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            font-weight: bold;
            padding: 8px;
            width: 30%;
            border-bottom: 1px solid #ddd;
        }
        .info-value {
            display: table-cell;
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        .terms {
            margin-top: 20px;
            padding: 15px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
        }
        .terms h3 {
            font-size: 14px;
            margin-bottom: 10px;
        }
        .terms p {
            margin-bottom: 10px;
            text-align: justify;
        }
        .signatures {
            margin-top: 40px;
            display: table;
            width: 100%;
        }
        .signature-box {
            display: table-cell;
            width: 50%;
            padding: 20px;
            text-align: center;
            border-top: 2px solid #333;
            margin-top: 60px;
        }
        .signature-box p {
            margin-top: 10px;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        @page {
            margin: 1cm;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>عقد إيجار</h1>
        <h2>Rental Contract</h2>
        <p>رقم العقد / Contract No: {{ $contract->id }}</p>
        <p>تاريخ العقد / Contract Date: {{ date('Y-m-d', strtotime($contract->created_at)) }}</p>
    </div>

    <!-- Apartment Information -->
    <div class="section">
        <div class="section-title">معلومات العقار / Apartment Information</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">اسم العقار / Title:</div>
                <div class="info-value">{{ $contract->post->Title ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">العنوان / Address:</div>
                <div class="info-value">{{ $contract->post->Address ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">الإيجار الشهري / Monthly Rent:</div>
                <div class="info-value">${{ number_format($contract->monthly_rent ?? 0, 2) }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">تاريخ البدء / Start Date:</div>
                <div class="info-value">{{ $contract->start_date ? date('Y-m-d', strtotime($contract->start_date)) : 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">تاريخ الانتهاء / End Date:</div>
                <div class="info-value">{{ $contract->end_date ? date('Y-m-d', strtotime($contract->end_date)) : 'N/A' }}</div>
            </div>
        </div>
    </div>

    <!-- Owner Information -->
    <div class="section">
        <div class="section-title">معلومات المالك / Owner Information</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">الاسم / Name:</div>
                <div class="info-value">{{ $contract->post->user->name ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">البريد الإلكتروني / Email:</div>
                <div class="info-value">{{ $contract->post->user->email ?? 'N/A' }}</div>
            </div>
            @if($ownerIdentity)
            <div class="info-row">
                <div class="info-label">الاسم الكامل / Full Name:</div>
                <div class="info-value">{{ $ownerIdentity->full_name ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">رقم الهوية / ID Number:</div>
                <div class="info-value">{{ $ownerIdentity->document_number ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">تاريخ الميلاد / Date of Birth:</div>
                <div class="info-value">{{ $ownerIdentity->date_of_birth ? date('Y-m-d', strtotime($ownerIdentity->date_of_birth)) : 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">الجنسية / Nationality:</div>
                <div class="info-value">{{ $ownerIdentity->nationality ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">العنوان / Address:</div>
                <div class="info-value">{{ $ownerIdentity->address ?? 'N/A' }}</div>
            </div>
            @endif
        </div>
    </div>

    <!-- Renter Information -->
    <div class="section">
        <div class="section-title">معلومات المستأجر / Renter Information</div>
        <div class="info-grid">
            @php
                $renter = $contract->rentalRequest->user ?? $contract->user ?? null;
            @endphp
            @if($renter)
            <div class="info-row">
                <div class="info-label">الاسم / Name:</div>
                <div class="info-value">{{ $renter->name ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">البريد الإلكتروني / Email:</div>
                <div class="info-value">{{ $renter->email ?? 'N/A' }}</div>
            </div>
            @endif
            @if($renterIdentity)
            <div class="info-row">
                <div class="info-label">الاسم الكامل / Full Name:</div>
                <div class="info-value">{{ $renterIdentity->full_name ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">رقم الهوية / ID Number:</div>
                <div class="info-value">{{ $renterIdentity->document_number ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">تاريخ الميلاد / Date of Birth:</div>
                <div class="info-value">{{ $renterIdentity->date_of_birth ? date('Y-m-d', strtotime($renterIdentity->date_of_birth)) : 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">الجنسية / Nationality:</div>
                <div class="info-value">{{ $renterIdentity->nationality ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">العنوان / Address:</div>
                <div class="info-value">{{ $renterIdentity->address ?? 'N/A' }}</div>
            </div>
            @endif
        </div>
    </div>

    <!-- Payment Information -->
    @if($contract->payment)
    <div class="section">
        <div class="section-title">معلومات الدفع / Payment Information</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">المبلغ / Amount:</div>
                <div class="info-value">${{ number_format($contract->payment->amount ?? 0, 2) }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">الحالة / Status:</div>
                <div class="info-value">{{ $contract->payment->status ?? 'N/A' }}</div>
            </div>
            @if($contract->payment->paid_at)
            <div class="info-row">
                <div class="info-label">تاريخ الدفع / Payment Date:</div>
                <div class="info-value">{{ date('Y-m-d', strtotime($contract->payment->paid_at)) }}</div>
            </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Contract Terms -->
    @if($contract->terms)
    <div class="section">
        <div class="section-title">شروط العقد / Contract Terms</div>
        <div class="terms">
            {!! nl2br(e($contract->terms)) !!}
        </div>
    </div>
    @endif

    <!-- Signatures -->
    <div class="signatures">
        <div class="signature-box">
            <p>توقيع المالك / Owner Signature</p>
            @if($contract->owner_signature)
                <p style="margin-top: 20px;">{{ $contract->owner_signature }}</p>
                @if($contract->owner_signed_at)
                    <p style="font-size: 10px; margin-top: 5px;">Date: {{ date('Y-m-d', strtotime($contract->owner_signed_at)) }}</p>
                @endif
            @else
                <p style="margin-top: 20px; color: #999;">غير موقّع / Not Signed</p>
            @endif
        </div>
        <div class="signature-box">
            <p>توقيع المستأجر / Renter Signature</p>
            @if($contract->renter_signature)
                <p style="margin-top: 20px;">{{ $contract->renter_signature }}</p>
                @if($contract->renter_signed_at)
                    <p style="font-size: 10px; margin-top: 5px;">Date: {{ date('Y-m-d', strtotime($contract->renter_signed_at)) }}</p>
                @endif
            @else
                <p style="margin-top: 20px; color: #999;">غير موقّع / Not Signed</p>
            @endif
        </div>
    </div>

    <div class="footer">
        <p>تم إنشاء هذا العقد إلكترونياً / This contract was generated electronically</p>
        <p>حالة العقد / Contract Status: {{ $contract->status }}</p>
        @if($contract->cancelled_by_admin)
            <p style="color: red; font-weight: bold;">تم إلغاء هذا العقد من قبل الإدارة / This contract was cancelled by administration</p>
        @endif
    </div>
</body>
</html>

