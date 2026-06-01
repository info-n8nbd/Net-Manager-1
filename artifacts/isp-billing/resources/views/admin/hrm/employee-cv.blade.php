<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $employee->name }} - CV</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; padding: 20px; }
        .header { text-align: center; border-bottom: 3px solid #1e3a5f; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { color: #1e3a5f; margin: 0 0 5px 0; }
        .section { margin-bottom: 20px; }
        .section-title { background: #1e3a5f; color: white; padding: 8px 15px; font-weight: bold; margin-bottom: 10px; }
        .info-row { display: flex; margin-bottom: 8px; }
        .info-label { width: 150px; font-weight: bold; color: #333; }
        .info-value { flex: 1; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f5f5f5; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $employee->name }}</h1>
        <p>{{ $employee->designation->name ?? 'Staff' }} | {{ $employee->department->name ?? 'General' }}</p>
    </div>

    <div class="section">
        <div class="section-title">Personal Information</div>
        <div class="info-row">
            <span class="info-label">Employee ID:</span>
            <span class="info-value">{{ $employee->employee_id ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Phone:</span>
            <span class="info-value">{{ $employee->phone }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Email:</span>
            <span class="info-value">{{ $employee->email ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Address:</span>
            <span class="info-value">{{ $employee->address ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">NID:</span>
            <span class="info-value">{{ $employee->nid ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Date of Birth:</span>
            <span class="info-value">{{ $employee->date_of_birth?->format('d-m-Y') ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Gender:</span>
            <span class="info-value">{{ ucfirst($employee->gender ?? 'N/A') }}</span>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Employment Information</div>
        <div class="info-row">
            <span class="info-label">Department:</span>
            <span class="info-value">{{ $employee->department->name ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Designation:</span>
            <span class="info-value">{{ $employee->designation->name ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Joining Date:</span>
            <span class="info-value">{{ $employee->joining_date?->format('d-m-Y') ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Basic Salary:</span>
            <span class="info-value">৳{{ number_format($employee->basic_salary, 0) }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Status:</span>
            <span class="info-value">{{ ucfirst($employee->status) }}</span>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Bank Information</div>
        <div class="info-row">
            <span class="info-label">Bank Name:</span>
            <span class="info-value">{{ $employee->bank_name ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Account Number:</span>
            <span class="info-value">{{ $employee->bank_account ?? 'N/A' }}</span>
        </div>
    </div>

    <div style="margin-top: 30px; text-align: center; color: #666; font-size: 10px;">
        <p>Generated on {{ now()->format('d-m-y H:i:s') }} | SOFTYNEX Billing</p>
    </div>
</body>
</html>
