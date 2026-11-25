<?php

namespace App\Enums;

enum VerificationStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case NeedsChanges = 'needs_changes';
    case Withdrawn = 'withdrawn';
}
