<?php

return [
    'module_key' => 'myhealthmembers',
    'module_name' => 'My Health',
    'icon' => 'fa fa-heartbeat',
    'route' => 'myhealth.dashboard',
    'permission' => 'myhealth.view',
    'items' => [
        [
            'label' => 'Dashboard',
            'route' => 'myhealth.dashboard',
            'permission' => 'myhealth.view',
            'icon' => 'fa fa-dashboard',
        ],

        [
            'label' => 'Hospital / HIS',
            'route' => 'myhealth.hospital.dashboard',
            'permission' => 'myhealth.hospital.view',
            'icon' => 'fa fa-hospital-o',
        ],
        [
            'label' => 'Reception',
            'route' => 'myhealth.hospital.reception.index',
            'permission' => 'myhealth.hospital.reception.view',
            'icon' => 'fa fa-desktop',
        ],
        [
            'label' => 'Appointments',
            'route' => 'myhealth.hospital.appointments.index',
            'permission' => 'myhealth.hospital.appointments.view',
            'icon' => 'fa fa-calendar-check-o',
        ],
        [
            'label' => 'Waiting Queue',
            'route' => 'myhealth.hospital.queue.index',
            'permission' => 'myhealth.hospital.queue.view',
            'icon' => 'fa fa-list-ol',
        ],
        [
            'label' => 'Doctor Session Schedules',
            'route' => 'myhealth.hospital.schedules.index',
            'permission' => 'myhealth.hospital.schedules.view',
            'icon' => 'fa fa-clock-o',
        ],

        [
            'label' => 'Laboratory',
            'route' => 'myhealth.laboratory.dashboard',
            'permission' => 'myhealth.laboratory.view',
            'icon' => 'fa fa-flask',
        ],
        [
            'label' => 'Lab Test Catalogue',
            'route' => 'myhealth.laboratory.catalogue.index',
            'permission' => 'myhealth.laboratory.catalogue.view',
            'icon' => 'fa fa-list',
        ],
        [
            'label' => 'Sample Collection',
            'route' => 'myhealth.laboratory.samples.index',
            'permission' => 'myhealth.laboratory.samples.view',
            'icon' => 'fa fa-barcode',
        ],
        [
            'label' => 'Lab Processing',
            'route' => 'myhealth.laboratory.processing.index',
            'permission' => 'myhealth.laboratory.processing.view',
            'icon' => 'fa fa-check-square-o',
        ],

        [
            'label' => 'Radiology',
            'route' => 'myhealth.radiology.dashboard',
            'permission' => 'myhealth.radiology.view',
            'icon' => 'fa fa-x-ray',
        ],
        [
            'label' => 'Radiology Requests',
            'route' => 'myhealth.radiology.requests.index',
            'permission' => 'myhealth.radiology.requests.view',
            'icon' => 'fa fa-calendar-plus-o',
        ],
        [
            'label' => 'Radiology Reports',
            'route' => 'myhealth.radiology.reports.index',
            'permission' => 'myhealth.radiology.reports.view',
            'icon' => 'fa fa-file-image-o',
        ],

        [
            'label' => 'Operation Theatre',
            'route' => 'myhealth.operation_theatre.dashboard',
            'permission' => 'myhealth.operation_theatre.view',
            'icon' => 'fa fa-procedures',
        ],
        [
            'label' => 'Surgery Schedule',
            'route' => 'myhealth.operation_theatre.schedules.index',
            'permission' => 'myhealth.operation_theatre.schedules.view',
            'icon' => 'fa fa-calendar-plus-o',
        ],
        [
            'label' => 'Theatre Rooms',
            'route' => 'myhealth.operation_theatre.theatres.index',
            'permission' => 'myhealth.operation_theatre.theatres.view',
            'icon' => 'fa fa-hospital-o',
        ],
        [
            'label' => 'Pre-Op Checklists',
            'route' => 'myhealth.operation_theatre.checklists.index',
            'permission' => 'myhealth.operation_theatre.checklists.view',
            'icon' => 'fa fa-check-square-o',
        ],
        [
            'label' => 'Operative Records',
            'route' => 'myhealth.operation_theatre.records.index',
            'permission' => 'myhealth.operation_theatre.records.view',
            'icon' => 'fa fa-file-text-o',
        ],
        [
            'label' => 'Post-Op Notes',
            'route' => 'myhealth.operation_theatre.post_op.index',
            'permission' => 'myhealth.operation_theatre.post_op.view',
            'icon' => 'fa fa-notes-medical',
        ],
        [
            'label' => 'OT Reports',
            'route' => 'myhealth.operation_theatre.reports.index',
            'permission' => 'myhealth.operation_theatre.reports.view',
            'icon' => 'fa fa-bar-chart',
        ],


        [
            'label' => 'Vaccination',
            'route' => 'myhealth.vaccination.dashboard',
            'permission' => 'myhealth.vaccination.view',
            'icon' => 'fa fa-shield',
        ],
        [
            'label' => 'Vaccine Master',
            'route' => 'myhealth.vaccination.vaccines.index',
            'permission' => 'myhealth.vaccination.vaccines.view',
            'icon' => 'fa fa-medkit',
        ],
        [
            'label' => 'Vaccination Register',
            'route' => 'myhealth.vaccination.records.index',
            'permission' => 'myhealth.vaccination.records.view',
            'icon' => 'fa fa-list-alt',
        ],
        [
            'label' => 'Immunization Schedules',
            'route' => 'myhealth.vaccination.schedules.index',
            'permission' => 'myhealth.vaccination.schedules.view',
            'icon' => 'fa fa-calendar',
        ],
        [
            'label' => 'Vaccination Reports',
            'route' => 'myhealth.vaccination.reports.index',
            'permission' => 'myhealth.vaccination.reports.view',
            'icon' => 'fa fa-bar-chart',
        ],





        [
            'label' => 'AI Clinical Assistant',
            'route' => 'myhealth.ai_clinical.dashboard',
            'permission' => 'myhealth.ai_clinical.view',
            'icon' => 'fa fa-magic',
        ],
        [
            'label' => 'Clinical Alerts',
            'route' => 'myhealth.ai_clinical.alerts.index',
            'permission' => 'myhealth.ai_clinical.alerts.view',
            'icon' => 'fa fa-exclamation-triangle',
        ],
        [
            'label' => 'Clinical Timeline',
            'route' => 'myhealth.ai_clinical.timeline.index',
            'permission' => 'myhealth.ai_clinical.timeline.view',
            'icon' => 'fa fa-history',
        ],
        [
            'label' => 'AI Clinical Reports',
            'route' => 'myhealth.ai_clinical.reports.index',
            'permission' => 'myhealth.ai_clinical.reports.view',
            'icon' => 'fa fa-bar-chart',
        ],



        [
            'label' => 'Disaster Recovery',
            'route' => 'myhealth.disaster_recovery.dashboard',
            'permission' => 'myhealth.disaster_recovery.view',
            'icon' => 'fa fa-life-ring',
        ],
        [
            'label' => 'Backup Centre',
            'route' => 'myhealth.disaster_recovery.backups.index',
            'permission' => 'myhealth.disaster_recovery.backups.view',
            'icon' => 'fa fa-database',
        ],
        [
            'label' => 'Restore Centre',
            'route' => 'myhealth.disaster_recovery.restores.index',
            'permission' => 'myhealth.disaster_recovery.restores.view',
            'icon' => 'fa fa-refresh',
        ],
        [
            'label' => 'System Health',
            'route' => 'myhealth.disaster_recovery.health.index',
            'permission' => 'myhealth.disaster_recovery.health.view',
            'icon' => 'fa fa-heartbeat',
        ],
        [
            'label' => 'DR Reports',
            'route' => 'myhealth.disaster_recovery.reports.index',
            'permission' => 'myhealth.disaster_recovery.reports.view',
            'icon' => 'fa fa-bar-chart',
        ],

        [
            'label' => 'Analytics',
            'route' => 'myhealth.analytics.dashboard',
            'permission' => 'myhealth.analytics.view',
            'icon' => 'fa fa-line-chart',
        ],
        [
            'label' => 'Executive Summary',
            'route' => 'myhealth.analytics.executive',
            'permission' => 'myhealth.analytics.view',
            'icon' => 'fa fa-dashboard',
        ],
        [
            'label' => 'Clinical Analytics',
            'route' => 'myhealth.analytics.clinical',
            'permission' => 'myhealth.analytics.clinical.view',
            'icon' => 'fa fa-heartbeat',
        ],
        [
            'label' => 'Operational Analytics',
            'route' => 'myhealth.analytics.operational',
            'permission' => 'myhealth.analytics.operational.view',
            'icon' => 'fa fa-cogs',
        ],
        [
            'label' => 'Financial Analytics',
            'route' => 'myhealth.analytics.financial',
            'permission' => 'myhealth.analytics.financial.view',
            'icon' => 'fa fa-money',
        ],

        [
            'label' => 'Nursing',
            'route' => 'myhealth.nursing.dashboard',
            'permission' => 'myhealth.nursing.view',
            'icon' => 'fa fa-heartbeat',
        ],
        [
            'label' => 'Nursing Station',
            'route' => 'myhealth.nursing.station.index',
            'permission' => 'myhealth.nursing.station.view',
            'icon' => 'fa fa-hospital-o',
        ],
        [
            'label' => 'Vital Signs',
            'route' => 'myhealth.nursing.vitals.index',
            'permission' => 'myhealth.nursing.vitals.view',
            'icon' => 'fa fa-stethoscope',
        ],
        [
            'label' => 'Nursing Notes',
            'route' => 'myhealth.nursing.notes.index',
            'permission' => 'myhealth.nursing.notes.view',
            'icon' => 'fa fa-file-text-o',
        ],
        [
            'label' => 'Medication Administration',
            'route' => 'myhealth.nursing.medications.index',
            'permission' => 'myhealth.nursing.medications.view',
            'icon' => 'fa fa-medkit',
        ],
        [
            'label' => 'Care Plans',
            'route' => 'myhealth.nursing.care_plans.index',
            'permission' => 'myhealth.nursing.care_plans.view',
            'icon' => 'fa fa-list-alt',
        ],
        [
            'label' => 'Shift Handovers',
            'route' => 'myhealth.nursing.handovers.index',
            'permission' => 'myhealth.nursing.handovers.view',
            'icon' => 'fa fa-exchange',
        ],
        [
            'label' => 'Members',
            'route' => 'myhealth.members.index',
            'permission' => 'myhealth.view',
            'icon' => 'fa fa-users',
        ],
        [
            'label' => 'Register Member',
            'route' => 'myhealth.members.create',
            'permission' => 'myhealth.create',
            'icon' => 'fa fa-user-plus',
        ],
        [
            'label' => 'Doctors',
            'route' => 'myhealth.doctors.index',
            'permission' => 'myhealth.doctors.view',
            'icon' => 'fa fa-user-md',
        ],
        [
            'label' => 'Pharmacy',
            'route' => 'myhealth.pharmacy.dashboard',
            'permission' => 'myhealth.pharmacy.view',
            'icon' => 'fa fa-medkit',
        ],
        [
            'label' => 'Medicines',
            'route' => 'myhealth.pharmacy.medicines.index',
            'permission' => 'myhealth.pharmacy.medicines.view',
            'icon' => 'fa fa-pills',
        ],
        [
            'label' => 'Pharmacy Stock',
            'route' => 'myhealth.pharmacy.stock.index',
            'permission' => 'myhealth.pharmacy.stock.view',
            'icon' => 'fa fa-cubes',
        ],
        [
            'label' => 'Dispensing',
            'route' => 'myhealth.pharmacy.dispensing.index',
            'permission' => 'myhealth.pharmacy.dispensing.view',
            'icon' => 'fa fa-prescription-bottle-alt',
        ],

        [
            'label' => 'Insurance',
            'route' => 'myhealth.insurance.dashboard',
            'permission' => 'myhealth.insurance.view',
            'icon' => 'fa fa-shield',
        ],
        [
            'label' => 'Insurance Companies',
            'route' => 'myhealth.insurance.companies.index',
            'permission' => 'myhealth.insurance.companies.view',
            'icon' => 'fa fa-building',
        ],
        [
            'label' => 'Member Policies',
            'route' => 'myhealth.insurance.policies.index',
            'permission' => 'myhealth.insurance.policies.view',
            'icon' => 'fa fa-id-card',
        ],
        [
            'label' => 'Insurance Claims',
            'route' => 'myhealth.insurance.claims.index',
            'permission' => 'myhealth.insurance.claims.view',
            'icon' => 'fa fa-file-text-o',
        ],

        [
            'label' => 'Telemedicine',
            'route' => 'myhealth.telemedicine.dashboard',
            'permission' => 'myhealth.telemedicine.view',
            'icon' => 'fa fa-video-camera',
        ],
        [
            'label' => 'Doctor Schedules',
            'route' => 'myhealth.telemedicine.schedules.index',
            'permission' => 'myhealth.telemedicine.schedules.view',
            'icon' => 'fa fa-calendar',
        ],
        [
            'label' => 'Telemedicine Appointments',
            'route' => 'myhealth.telemedicine.appointments.index',
            'permission' => 'myhealth.telemedicine.appointments.view',
            'icon' => 'fa fa-calendar-check-o',
        ],

        [
            'label' => 'Billing & Claims',
            'route' => 'myhealth.billing.dashboard',
            'permission' => 'myhealth.billing.view',
            'icon' => 'fa fa-money',
        ],
        [
            'label' => 'Billing Services',
            'route' => 'myhealth.billing.services.index',
            'permission' => 'myhealth.billing.services.view',
            'icon' => 'fa fa-list-alt',
        ],
        [
            'label' => 'Invoices',
            'route' => 'myhealth.billing.invoices.index',
            'permission' => 'myhealth.billing.invoices.view',
            'icon' => 'fa fa-file-text-o',
        ],
        [
            'label' => 'Claim Settlements',
            'route' => 'myhealth.billing.claims.index',
            'permission' => 'myhealth.billing.claims.view',
            'icon' => 'fa fa-check-square-o',
        ],

        [
            'label' => 'MyHealth Reports',
            'route' => 'myhealth.reports.index',
            'permission' => 'myhealth.reports.view',
            'icon' => 'fa fa-bar-chart',
        ],
        [
            'label' => 'Patient History Report',
            'route' => 'myhealth.reports.patient_history',
            'permission' => 'myhealth.reports.view',
            'icon' => 'fa fa-history',
        ],
        [
            'label' => 'Prescription Report',
            'route' => 'myhealth.reports.prescriptions',
            'permission' => 'myhealth.reports.view',
            'icon' => 'fa fa-file-text-o',
        ],
        [
            'label' => 'Lab Report',
            'route' => 'myhealth.reports.labs',
            'permission' => 'myhealth.reports.view',
            'icon' => 'fa fa-flask',
        ],
        [
            'label' => 'Medicine Dispense Report',
            'route' => 'myhealth.reports.dispenses',
            'permission' => 'myhealth.reports.view',
            'icon' => 'fa fa-medkit',
        ],
        [
            'label' => 'Insurance Claim Report',
            'route' => 'myhealth.reports.claims',
            'permission' => 'myhealth.reports.view',
            'icon' => 'fa fa-shield',
        ],
        [
            'label' => 'Telemedicine Report',
            'route' => 'myhealth.reports.telemedicine',
            'permission' => 'myhealth.reports.view',
            'icon' => 'fa fa-video-camera',
        ],
        [
            'label' => 'Revenue Report',
            'route' => 'myhealth.reports.revenue',
            'permission' => 'myhealth.reports.view',
            'icon' => 'fa fa-money',
        ],
        [
            'label' => 'Doctor Performance Report',
            'route' => 'myhealth.reports.doctor_performance',
            'permission' => 'myhealth.reports.view',
            'icon' => 'fa fa-user-md',
        ],
        [
            'label' => 'MyHealth Settings',
            'route' => 'myhealth.settings.index',
            'permission' => 'myhealth.settings.view',
            'icon' => 'fa fa-cogs',
        ],

        [
            'label' => 'Business Permissions',
            'route' => 'myhealth.superadmin.permissions.index',
            'permission' => 'myhealth.superadmin.permissions',
            'icon' => 'fa fa-lock',
        ],

        [
            'label' => 'Disaster Recovery',
            'route' => 'myhealth.disaster_recovery.dashboard',
            'permission' => 'myhealth.disaster_recovery.view',
            'icon' => 'fa fa-life-ring',
        ],
        [
            'label' => 'Production Certification',
            'route' => 'myhealth.certification.dashboard',
            'permission' => 'myhealth.certification.view',
            'icon' => 'fa fa-certificate',
        ],
    ],
];
