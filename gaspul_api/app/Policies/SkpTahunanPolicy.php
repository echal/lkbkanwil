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
     * Determine if user can request revision of their own SKP.
     *
     * Allowed:
     * - role ASN (semua ASN)
     * - role ATASAN yang bukan puncak hierarki (atasan_id != NULL)
     *
     * Blocked:
     * - role ATASAN puncak hierarki (atasan_id = NULL) — tidak ada yang bisa menyetujui
     * - user bukan pemilik SKP
     * - status bukan DISETUJUI
     */
    public function requestRevision(User $user, SkpTahunan $skp): bool
    {
        // Must own the SKP
        if ($skp->user_id != $user->id) {
            return false;
        }

        // ATASAN puncak hierarki (atasan_id = NULL) tidak bisa ajukan revisi
        // karena tidak ada atasan yang bisa menyetujuinya
        if ($user->role === 'ATASAN' && is_null($user->atasan_id)) {
            return false;
        }

        // Hanya ASN dan ATASAN non-puncak yang boleh
        if (!in_array($user->role, ['ASN', 'ATASAN'])) {
            return false;
        }

        // Can only request revision if status is DISETUJUI or REVISI_DITOLAK
        return in_array($skp->status, ['DISETUJUI', 'REVISI_DITOLAK']);
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
