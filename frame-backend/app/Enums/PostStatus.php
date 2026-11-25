<?php

namespace App\Enums;

enum PostStatus: string
{
    case Published = 'published';
    case Archived = 'archived';
    case Removed = 'removed';
}
