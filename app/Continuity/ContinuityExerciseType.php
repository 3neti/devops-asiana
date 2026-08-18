<?php

namespace App\Continuity;

enum ContinuityExerciseType: string
{
    case Tabletop = 'tabletop';
    case BackupRestore = 'backup_restore';
    case Failover = 'failover';
    case FullScale = 'full_scale';

    public function label(): string
    {
        return match ($this) {
            self::Tabletop => 'Tabletop',
            self::BackupRestore => 'Backup Restore',
            self::Failover => 'Failover',
            self::FullScale => 'Full-scale',
        };
    }

    public function requiresRestoreEvidence(): bool
    {
        return $this !== self::Tabletop;
    }
}
