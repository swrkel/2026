@extends('customers::layouts.action', ['title' => 'View Customer'])

@section('customer_action_body')
@php
    $groupOptions = $groupOptions ?? [];
    $businessLocations = $businessLocations ?? [];
    $customers = $customers ?? [];
    $userGroups = $userGroups ?? [];
    $typeOptions = $typeOptions ?? ['customer' => 'Customer', 'both' => 'Both Supplier & Customer'];
    $statusOptions = $statusOptions ?? ['1' => 'Active', '0' => 'Inactive'];
    $payTermTypeOptions = $payTermTypeOptions ?? ['days' => 'Days', 'months' => 'Months'];

    $displayValue = static function ($value, $fallback = '-') {
        if ($value === null || $value === '' || $value === []) {
            return $fallback;
        }

        return $value;
    };

    $yesNo = static function ($value) {
        return (int) $value === 1 ? 'Yes' : 'No';
    };

    $money = static function ($value) {
        return number_format((float) str_replace(',', '', (string) ($value ?? 0)), 2);
    };

    $formatDate = static function ($value, $withTime = false) {
        if (empty($value)) {
            return '-';
        }

        try {
            return \Carbon\Carbon::parse($value)->format($withTime ? 'd/m/Y h:i A' : 'd/m/Y');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    };

    $notificationContactsRaw = data_get($customer, 'notification_contacts');
    if (is_string($notificationContactsRaw)) {
        $decodedNotificationContacts = json_decode($notificationContactsRaw, true);
        $notificationContacts = is_array($decodedNotificationContacts)
            ? implode(', ', array_filter($decodedNotificationContacts))
            : trim($notificationContactsRaw);
    } elseif (is_array($notificationContactsRaw)) {
        $notificationContacts = implode(', ', array_filter($notificationContactsRaw));
    } else {
        $notificationContacts = '';
    }

    $subCustomerIdsRaw = data_get($customer, 'sub_customers');
    if (is_string($subCustomerIdsRaw)) {
        $decodedSubCustomers = json_decode($subCustomerIdsRaw, true);
        $subCustomerIds = is_array($decodedSubCustomers) ? $decodedSubCustomers : [];
    } elseif (is_array($subCustomerIdsRaw)) {
        $subCustomerIds = $subCustomerIdsRaw;
    } else {
        $subCustomerIds = [];
    }

    $subCustomerNames = collect($subCustomerIds)
        ->map(function ($id) use ($customers) {
            return $customers[$id] ?? null;
        })
        ->filter()
        ->values()
        ->implode(', ');

    $creditNotificationLabels = [
        '' => 'None',
        'settlement' => 'Settlement',
        'customer_bill' => 'Customer Bill',
        'pumper_dashboard' => 'Pumper Dashboard',
    ];

    $addressLine1 = data_get($customer, 'address_line_1', data_get($customer, 'address'));
    $addressLine2 = data_get($customer, 'address_line_2', data_get($customer, 'address_2'));
    $addressLine3 = data_get($customer, 'address_line_3', data_get($customer, 'address_3'));

    $sections = [
        [
            'title' => 'Basic Details',
            'icon' => 'fa-user',
            'items' => [
                ['Contact Type', $typeOptions[data_get($customer, 'type')] ?? ucfirst((string) data_get($customer, 'type', 'customer'))],
                ['Customer Name', data_get($customer, 'name')],
                ['Business Name', data_get($customer, 'supplier_business_name')],
                ['Customer Code', data_get($customer, 'contact_id')],
                ['Mobile', data_get($customer, 'mobile')],
                ['WhatsApp Number', data_get($customer, 'whatsapp_number')],
                ['Alternate Number', data_get($customer, 'alternate_number')],
                ['Landline', data_get($customer, 'landline')],
                ['Email', data_get($customer, 'email')],
                ['Customer Group', $groupOptions[data_get($customer, 'customer_group_id')] ?? '-'],
                ['Business Location', $businessLocations[data_get($customer, 'business_location_id')] ?? 'All / Head Office'],
                ['Assigned To', $userGroups[data_get($customer, 'assigned_to')] ?? '-'],
                ['Vehicle No', data_get($customer, 'vehicle_no')],
                ['Customer Passcode', data_get($customer, 'customer_passcode')],
                ['Status', $statusOptions[(string) data_get($customer, 'active', 1)] ?? ((int) data_get($customer, 'active', 1) === 1 ? 'Active' : 'Inactive')],
            ],
        ],
        [
            'title' => 'Address',
            'icon' => 'fa-map-marker',
            'items' => [
                ['Address Line 1', $addressLine1],
                ['Address Line 2', $addressLine2],
                ['Address Line 3', $addressLine3],
                ['Landmark', data_get($customer, 'landmark')],
                ['City', data_get($customer, 'city')],
                ['State', data_get($customer, 'state')],
                ['Country', data_get($customer, 'country')],
                ['Zip Code', data_get($customer, 'zip_code')],
            ],
        ],
        [
            'title' => 'Credit & Opening Balance',
            'icon' => 'fa-money',
            'items' => [
                ['Tax No', data_get($customer, 'tax_number')],
                ['VAT No', data_get($customer, 'vat_number')],
                ['Credit Limit', $money(data_get($customer, 'credit_limit'))],
                ['Pay Term Number', data_get($customer, 'pay_term_number')],
                ['Pay Term Type', $payTermTypeOptions[data_get($customer, 'pay_term_type')] ?? ucfirst((string) data_get($customer, 'pay_term_type'))],
                ['Opening Balance', $money(data_get($customer, 'opening_balance'))],
                ['Transaction Date', $formatDate(data_get($customer, 'transaction_date'))],
                ['Credit Notification', $creditNotificationLabels[(string) data_get($customer, 'credit_notification', '')] ?? ucfirst(str_replace('_', ' ', (string) data_get($customer, 'credit_notification')))],
                ['Manual Bill to Settlement', $yesNo(data_get($customer, 'manual_bill_settlement'))],
                ['Sub Customer', $yesNo(data_get($customer, 'sub_customer'))],
                ['Selected Sub Customers', $subCustomerNames],
            ],
        ],
        [
            'title' => 'Notifications',
            'icon' => 'fa-bell',
            'items' => [
                ['Should Notify', $yesNo(data_get($customer, 'should_notify'))],
                ['Additional Notification Numbers', $notificationContacts],
            ],
        ],
        [
            'title' => 'Record Information',
            'icon' => 'fa-info-circle',
            'items' => [
                ['Created At', $formatDate(data_get($customer, 'created_at'), true)],
                ['Updated At', $formatDate(data_get($customer, 'updated_at'), true)],
            ],
        ],
    ];
@endphp

<div class="customers-full-view-wrapper">
    <style>
        .customers-full-view-wrapper {
            color: #1f2937;
        }
        .customers-full-view-wrapper .customer-view-hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 18px;
            padding: 18px 20px;
            border: 1px solid #dbe6f4;
            border-left: 5px solid #2f67f6;
            border-radius: 16px;
            background: linear-gradient(135deg, #f7fbff 0%, #ffffff 100%);
            box-shadow: 0 8px 22px rgba(15, 23, 42, .06);
        }
        .customers-full-view-wrapper .customer-view-title {
            margin: 0 0 5px;
            color: #0f172a;
            font-size: 22px;
            font-weight: 800;
        }
        .customers-full-view-wrapper .customer-view-subtitle {
            color: #64748b;
            font-size: 13px;
        }
        .customers-full-view-wrapper .customer-view-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .customers-full-view-wrapper .customer-status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
        }
        .customers-full-view-wrapper .customer-status-active {
            color: #166534;
            background: #dcfce7;
        }
        .customers-full-view-wrapper .customer-status-inactive {
            color: #991b1b;
            background: #fee2e2;
        }
        .customers-full-view-wrapper .customer-edit-button {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 13px;
            border-radius: 10px;
            color: #fff !important;
            background: #2f67f6;
            font-weight: 700;
            text-decoration: none !important;
        }
        .customers-full-view-wrapper .customer-section-card {
            margin-bottom: 16px;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            background: #fff;
            overflow: hidden;
            box-shadow: 0 5px 16px rgba(15, 23, 42, .04);
        }
        .customers-full-view-wrapper .customer-section-header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 17px;
            border-bottom: 1px solid #edf2f7;
            background: #f8fbff;
        }
        .customers-full-view-wrapper .customer-section-icon {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #2f67f6;
            background: #eaf2ff;
        }
        .customers-full-view-wrapper .customer-section-title {
            margin: 0;
            color: #0f172a;
            font-size: 15px;
            font-weight: 800;
        }
        .customers-full-view-wrapper .customer-details-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .customers-full-view-wrapper .customer-detail-item {
            display: grid;
            grid-template-columns: minmax(145px, 38%) 1fr;
            min-height: 48px;
            border-bottom: 1px solid #edf2f7;
        }
        .customers-full-view-wrapper .customer-detail-item:nth-child(odd) {
            border-right: 1px solid #edf2f7;
        }
        .customers-full-view-wrapper .customer-detail-label,
        .customers-full-view-wrapper .customer-detail-value {
            padding: 12px 14px;
            line-height: 1.45;
            word-break: break-word;
        }
        .customers-full-view-wrapper .customer-detail-label {
            color: #475569;
            background: #fbfdff;
            font-size: 12px;
            font-weight: 800;
        }
        .customers-full-view-wrapper .customer-detail-value {
            color: #111827;
            font-size: 13px;
            font-weight: 600;
        }
        .customers-full-view-wrapper .customer-detail-empty {
            color: #94a3b8;
            font-weight: 500;
        }
        @media (max-width: 900px) {
            .customers-full-view-wrapper .customer-view-hero {
                align-items: flex-start;
                flex-direction: column;
            }
            .customers-full-view-wrapper .customer-details-grid {
                grid-template-columns: 1fr;
            }
            .customers-full-view-wrapper .customer-detail-item:nth-child(odd) {
                border-right: 0;
            }
        }
        @media (max-width: 520px) {
            .customers-full-view-wrapper .customer-detail-item {
                grid-template-columns: 1fr;
            }
            .customers-full-view-wrapper .customer-detail-label {
                padding-bottom: 5px;
            }
            .customers-full-view-wrapper .customer-detail-value {
                padding-top: 5px;
            }
        }
    </style>

    <div class="customer-view-hero">
        <div>
            <h2 class="customer-view-title">{{ $displayValue(data_get($customer, 'name')) }}</h2>
            <div class="customer-view-subtitle">
                Customer Code: {{ $displayValue(data_get($customer, 'contact_id')) }}
                @if(!empty(data_get($customer, 'mobile')))
                    &nbsp;|&nbsp; Mobile: {{ data_get($customer, 'mobile') }}
                @endif
            </div>
        </div>
        <div class="customer-view-actions">
            <span class="customer-status-pill {{ (int) data_get($customer, 'active', 1) === 1 ? 'customer-status-active' : 'customer-status-inactive' }}">
                <i class="fa {{ (int) data_get($customer, 'active', 1) === 1 ? 'fa-check-circle' : 'fa-ban' }}"></i>
                {{ (int) data_get($customer, 'active', 1) === 1 ? 'Active' : 'Inactive' }}
            </span>
            @if(!empty($canEdit))
                <a href="{{ route('customers.edit', $customer->id) }}" class="customer-edit-button">
                    <i class="fa fa-edit"></i> Edit Customer
                </a>
            @endif
        </div>
    </div>

    @foreach($sections as $section)
        <div class="customer-section-card">
            <div class="customer-section-header">
                <span class="customer-section-icon"><i class="fa {{ $section['icon'] }}"></i></span>
                <h3 class="customer-section-title">{{ $section['title'] }}</h3>
            </div>
            <div class="customer-details-grid">
                @foreach($section['items'] as $item)
                    @php $itemValue = $displayValue($item[1]); @endphp
                    <div class="customer-detail-item">
                        <div class="customer-detail-label">{{ $item[0] }}</div>
                        <div class="customer-detail-value {{ $itemValue === '-' ? 'customer-detail-empty' : '' }}">{{ $itemValue }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
@endsection
