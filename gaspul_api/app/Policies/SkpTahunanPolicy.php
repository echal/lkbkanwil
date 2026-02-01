<?php

namespace App\Policies;

use App\Models\User;
use App\Models\SkpTahunan;
use Illuminate\Auth\Access\HandlesAuthorization;

class SkpTahunanPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view the SKP
     */
    public function view(User $user, SkpTahunan $skp): bool
    {
        return $skp->user_id === $user->id;
    }

    /**
     * Determine if user can update the SKP header
     */
    public function update(User $user, SkpTahunan $skp): bool
    {
        // Must own the SKP
        if ($skp->user_id !== $user->id) {
            return false;
        }

        // Can only update if status is DRAFT, DITOLAK, or REVISI_DITOLAK
        return in_array($skp->status, ['DRAFT', 'DITOLAK', 'REVISI_DITOLAK']);
    }

    /**
     * Determine if user can submit the SKP for approval
     */
    public function submit(User $user, SkpTahunan $skp): bool
    {
        // Must own the SKP
        if ($skp->user_id !== $user->id) {
            return false;
        }

        return $skp->canBeSubmitted();
    }

    /**
     * Determine if user can request revision
     * Only if SKP is approved
     */
    public function requestRevision(User $user, SkpTahunan $skp): bool
    {
        // Must own the SKP
        if ($skp->user_id !== $user->id) {
            return false;
        }

        // Can only request revision if status is DISETUJUI
        return $skp->canRequestRevision();
    }

    /**
     * Determine if user (atasan) can approve revision request
     */
    public function approveRevision(User $user, SkpTahunan $skp): bool
    {
        // Must be PIMPINAN role
        if ($user->role !== 'PIMPINAN') {
            return false;
        }

        // SKP must be in REVISI_DIAJUKAN status
        return $skp->status === 'REVISI_DIAJUKAN';
    }

    /**
     * Determine if user (atasan) can reject revision request
     */
    public function rejectRevision(User $user, SkpTahunan $skp): bool
    {
        // Must be PIMPINAN role
        if ($user->role !== 'PIMPINAN') {
            return false;
        }

        // SKP must be in REVISI_DIAJUKAN status
        return $skp->status === 'REVISI_DIAJUKAN';
    }
}
