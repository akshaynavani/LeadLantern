<?php

namespace App\Enums;

/**
 * Categories the triage service may return. Must stay in sync with
 * triage-service/src/schema.ts.
 */
enum TaskCategory: string
{
    case SalesLead = 'sales_lead';
    case Billing = 'billing';
    case TechnicalSupport = 'technical_support';
    case AccountManagement = 'account_management';
    case ChurnRisk = 'churn_risk';
    case Partnership = 'partnership';
    case Spam = 'spam';
    case Other = 'other';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
