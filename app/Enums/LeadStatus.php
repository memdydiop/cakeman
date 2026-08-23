<?php

namespace App\Enums;

enum LeadStatus: string
{
    case New = 'new';

    case Contacted = 'contacted';

    case Converted = 'converted';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Nouveau',
            self::Contacted => 'Contacté',
            self::Converted => 'Converti',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::New => 'blue',
            self::Contacted => 'amber',
            self::Converted => 'green',
        };
    }
}
