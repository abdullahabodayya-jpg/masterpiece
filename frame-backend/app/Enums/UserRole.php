<?php

namespace App\Enums;

enum UserRole: string
{
    case Creator = 'creator';
    case Editor = 'editor';
    case Admin = 'admin';
    case Support = 'support';
}
