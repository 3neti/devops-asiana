<?php

namespace App\CorrectiveActions;

enum CorrectiveActionSourceType: string
{
    case Incident = 'incident';
    case Change = 'change';
    case BreakGlassAccess = 'break_glass_access';
    case ProductionAccess = 'production_access';
    case PolicyException = 'policy_exception';
    case OtherFinding = 'other_finding';

    public function label(): string
    {
        return match ($this) {
            self::Incident => 'Incident',
            self::Change => 'Change',
            self::BreakGlassAccess => 'Break-glass Review',
            self::ProductionAccess => 'Access Review',
            self::PolicyException => 'Policy Exception',
            self::OtherFinding => 'Other Finding',
        };
    }
}
