<?php

namespace App\Enums;

enum TenantStatus: string
{
    case Active = 'active';
    case Pending = 'pending';
    case Suspended = 'suspended';
    case Trialing = 'trialing';
    case Cancelled = 'cancelled';
}
