<?php

namespace App\Policies;

use App\Models\User;
use App\Models\SkpTahunanDetail;
use Illuminate\Auth\Access\HandlesAuthorization;

class SkpTahunanDetailPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view the detail
     */
    public function view(User $user, SkpTahunanDetail $detail): bool
    {
        return $detail->skpTahunan->user_id === $user->id;
    }

    /**
     * Determine if user can edit the detail
     */
    public function update(User $user, SkpTahunanDetail $detail): bool
    {
        // DEBUG: Log authorization check
        \Log::info('SkpTahunanDetailPolicy::update', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'detail_id' => $detail->id,
            'skp_tahunan_id' => $detail->skp_tahunan_id,
            'skp_owner_id' => $detail->skpTahunan->user_id,
            'skp_status' => $detail->skpTahunan->status,
            'ownership_check' => $detail->skpTahunan->user_id === $user->id,
            'status_check' => in_array($detail->skpTahunan->status, ['DRAFT', 'DITOLAK']),
        ]);

        // Must own the SKP
        if ($detail->skpTahunan->user_id !== $user->id) {
            \Log::warning('SKP ownership check FAILED', [
                'user_id' => $user->id,
                'skp_owner_id' => $detail->skpTahunan->user_id,
            ]);
            return false;
        }

        // Can only edit if status is DRAFT or DITOLAK
        $canEdit = in_array($detail->skpTahunan->status, ['DRAFT', 'DITOLAK']);

        if (!$canEdit) {
            \Log::warning('SKP status check FAILED', [
                'status' => $detail->skpTahunan->status,
                'allowed' => ['DRAFT', 'DITOLAK'],
            ]);
        }

        return $canEdit;
    }

    /**
     * Determine if user can delete the detail
     */
    public function delete(User $user, SkpTahunanDetail $detail): bool
    {
        // Must own the SKP
        if ($detail->skpTahunan->user_id !== $user->id) {
            return false;
        }

        // Can only delete if status is DRAFT or DITOLAK
        return in_array($detail->skpTahunan->status, ['DRAFT', 'DITOLAK']);
    }
}
