<?php

namespace App\Enums;

enum CommentStatus: string
{
    case Published = 'published';
    case Hidden = 'hidden';
    case Removed = 'removed';
}
