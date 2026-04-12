<?php

namespace App\Enums;

enum FeedbackStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
