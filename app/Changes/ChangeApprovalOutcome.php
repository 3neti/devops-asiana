<?php

namespace App\Changes;

enum ChangeApprovalOutcome: string
{
    case Approved = 'approved';
    case Rejected = 'rejected';
}
