<?php

namespace App\Policies;

use App\Models\User;
use App\Models\SkpTahunan;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Log;

/**
 * SKP Tahunan Policy
 *
 * PRODUCTION-SAFE: Full debugging + strict authorization
 */
class SkpTahunanPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view the SKP
     */
    public function view(User $user, SkpTahunan $skp): bool
    {
        return $skp->user_id == $user->id;
    }

    /**
     * Determine if user can update the SKP header
     */
    public function update(User $user, SkpTahunan $skp): bool
    {
        // Must own the SKP
        if ($skp->user_id != $user->id) {
            return false;
        }

        // Can only update if status is DRAFT, DITOLAK
        return in_array($skp->status, ['DRAFT', 'DITOLAK']);
    }

    /**
     * Determine if user can submit the SKP for approval
     */
    public function submit(User $user, SkpTahunan $skp): bool
    {
        // Must own the SKP
        if ($skp->user_id != $user->id) {
            return false;
        }

        return $skp->canBeSubmitted();
    }

    /**
     * Determine if ASN can request revision
     * Only if SKP is approved (DISETUJUI)
     *
     * CRITICAL: This is why button appears/disappears
     */
    public function requestRevision(User $user, SkpTahunan $skp): bool
    {
        // DEBUG LOG (remove after production works)
        Log::info('Policy requestRevision Check', [
            'user_id' => $user->id,
            'user_role' => $user->role,
            'skp_id' => $skp->id,
            'skp_user_id' => $skp->user_id,
            'skp_status' => $skp->status,
            'ownership_match' => $skp->user_id == $user->id,
            'can_request' => $skp->canRequestRevision(),
        ]);

        // Must own the SKP
        if ($skp->user_id != $user->id) {
            return false;
        }

        // Must be ASN role
        if ($user->role !== 'ASN') {
            return false;
        }

        // Can only request revision if status is DISETUJUI
        return $skp->status === 'DISETUJUI';
    }

    /**
     * Determine if ATASAN can approve revision request
     */
    public function approveRevision(User $user, SkpTahunan $skp): bool
    {
        // DEBUG LOG
        Log::info('Policy approveRevision Check', [
            'user_id' => $user->id,
            'user_role' => $user->role,
            'skp_id' => $skp->id,
            'skp_status' => $skp->status,
        ]);

        // Must be ATASAN role
        if ($user->role !== 'ATASAN') {
            return false;
        }

        // SKP must be in REVISI_DIAJUKAN status
        return $skp->status === 'REVISI_DIAJUKAN';
    }

    /**
     * Determine if ATASAN can reject revision request
     */
    public function rejectRevision(User $user, SkpTahunan $skp): bool
    {
        // Must be ATASAN role
        if ($user->role !== 'ATASAN') {
            return false;
        }

        // SKP must be in REVISI_DIAJUKAN status
        return $skp->status === 'REVISI_DIAJUKAN';
    }
}
