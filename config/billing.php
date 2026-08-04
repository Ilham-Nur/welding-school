<?php

return [
    'administration_fee' => (int) env('BILLING_ADMINISTRATION_FEE', 150000),
    'invoice_due_hours' => (int) env('BILLING_INVOICE_DUE_HOURS', 24),
];
