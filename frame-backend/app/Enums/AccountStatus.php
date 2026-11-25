<?php

namespace App\Enums;

enum AccountStatus: string
{
    case PendingVerification = 'pending_verification';
    case Active = 'active';
    case Restricted = 'restricted';
    case Suspended = 'suspended';
    case Closed = 'closed';
}
