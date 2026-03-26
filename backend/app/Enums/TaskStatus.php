<?php

namespace App\Enums;

enum TaskStatus: string
{
    case New = 'new';
    case Routed = 'routed';
    case NeedsReview = 'needs_review';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
}
