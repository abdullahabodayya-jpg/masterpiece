<?php

namespace App\Enums;

enum WorkStatus: string
{
    case Draft = 'draft';
    case Open = 'open';
    case Paused = 'paused';
    case PendingAgreement = 'pending_agreement';
    case InProgress = 'in_progress';
    case InReview = 'in_review';
    case Completed = 'completed';
    case Disputed = 'disputed';
    case Cancelled = 'cancelled';
    case Closed = 'closed';
}
