<?php

namespace App\Enums;

enum DeliverableStatus: string
{
    case Pending = 'pending';
    case Submitted = 'submitted';
    case InReview = 'in_review';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
}
