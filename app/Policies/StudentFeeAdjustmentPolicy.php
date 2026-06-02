<?php

namespace App\Policies;

use App\Models\StudentFeeAdjustment;
use App\Models\User;

class StudentFeeAdjustmentPolicy
{
    /**
     * Only leadership roles may approve or reject concessions.
     */
    public function approve(User $user, StudentFeeAdjustment $adjustment): bool
    {
        $role = strtoupper((string) optional($user->role)->role_name);

        return (bool) $user->is_active
            && in_array($role, ['ADMINISTRATOR', 'ADMIN', 'PRINCIPAL', 'CORRESPONDENT'], true)
            && $adjustment->status === 'PENDING';
    }
}
