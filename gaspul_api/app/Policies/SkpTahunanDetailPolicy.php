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
        // Safety check: ensure relation is loaded
        if (!$detail->skpTahunan) {
            return false;
        }

        return $detail->skpTahunan->user_id === $user->id;
    }

    /**
     * Determine if user can edit the detail
     */
    public function update(User $user, SkpTahunanDetail $detail): bool
    {
        // Safety check: ensure relation is loaded
        if (!$detail->skpTahunan) {
            return false;
        }

        // Must own the SKP
        if ($detail->skpTahunan->user_id !== $user->id) {
            return false;
        }

        // Can only edit if status is DRAFT or DITOLAK
        // REVISI_DITOLAK TIDAK boleh edit (SKP tetap DISETUJUI)
        return in_array($detail->skpTahunan->status, ['DRAFT', 'DITOLAK']);
    }

    /**
     * Determine if user can delete the detail
     */
    public function delete(User $user, SkpTahunanDetail $detail): bool
    {
        // Safety check: ensure relation is loaded
        if (!$detail->skpTahunan) {
            return false;
        }

        // Must own the SKP
        if ($detail->skpTahunan->user_id !== $user->id) {
            return false;
        }

        // Can only delete if status is DRAFT or DITOLAK
        // REVISI_DITOLAK TIDAK bisa dihapus (SKP tetap DISETUJUI)
        return in_array($detail->skpTahunan->status, ['DRAFT', 'DITOLAK']);
    }
}
