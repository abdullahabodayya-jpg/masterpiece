<?php

namespace App\Enums;

enum PostVisibility: string
{
    case Public = 'public';
    case ConnectionsOnly = 'connections_only';
    case Private = 'private';
}
