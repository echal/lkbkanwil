<?php
/**
 * PRODUCTION DEBUG SCRIPT
 *
 * Jalankan via: php artisan tinker < debug_production_auth.php
 * Atau copy-paste ke tinker satu per satu
 */

echo "==========================================\n";
echo "PRODUCTION AUTHORIZATION DEBUG\n";
echo "==========================================\n\n";

// 1. Check current auth user
echo "1. CURRENT AUTH USER:\n";
if (auth()->check()) {
    $user = auth()->user();
    echo "   User ID: " . $user->id . "\n";
    echo "   Name: " . $user->name . "\n";
    echo "   Email: " . $user->email . "\n";
    echo "   Role: " . $user->role . "\n";
    echo "   NIP: " . ($user->nip ?? 'N/A') . "\n";
} else {
    echo "   ❌ NO USER AUTHENTICATED\n";
}
echo "\n";

// 2. Check SKP ownership
echo "2. SKP OWNERSHIP CHECK:\n";
$skpId = 3; // GANTI dengan ID SKP yang bermasalah
$skp = \App\Models\SkpTahunan::find($skpId);

if ($skp) {
    echo "   SKP ID: " . $skp->id . "\n";
    echo "   SKP User ID: " . $skp->user_id . "\n";
    echo "   SKP Status: " . $skp->status . "\n";
    echo "   SKP Tahun: " . $skp->tahun . "\n";

    if (auth()->check()) {
        $match = ($skp->user_id === auth()->id());
        echo "   Ownership Match: " . ($match ? '✅ YES' : '❌ NO') . "\n";
    }
} else {
    echo "   ❌ SKP NOT FOUND\n";
}
echo "\n";

// 3. Check Policy registration
echo "3. POLICY REGISTRATION:\n";
try {
    $policies = \Illuminate\Support\Facades\Gate::policies();
    echo "   Total Policies Registered: " . count($policies) . "\n";

    if (isset($policies[\App\Models\SkpTahunan::class])) {
        echo "   ✅ SkpTahunan Policy: " . $policies[\App\Models\SkpTahunan::class] . "\n";
    } else {
        echo "   ❌ SkpTahunan Policy: NOT REGISTERED\n";
    }

    if (isset($policies[\App\Models\SkpTahunanDetail::class])) {
        echo "   ✅ SkpTahunanDetail Policy: " . $policies[\App\Models\SkpTahunanDetail::class] . "\n";
    } else {
        echo "   ❌ SkpTahunanDetail Policy: NOT REGISTERED\n";
    }
} catch (\Exception $e) {
    echo "   ❌ ERROR: " . $e->getMessage() . "\n";
}
echo "\n";

// 4. Manual Policy Check
echo "4. MANUAL POLICY CHECK:\n";
if (auth()->check() && $skp) {
    $user = auth()->user();

    // Test requestRevision
    echo "   requestRevision: ";
    try {
        $canRequest = \Illuminate\Support\Facades\Gate::forUser($user)->allows('requestRevision', $skp);
        echo ($canRequest ? '✅ ALLOWED' : '❌ DENIED') . "\n";
    } catch (\Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n";
    }

    // Test update
    echo "   update: ";
    try {
        $canUpdate = \Illuminate\Support\Facades\Gate::forUser($user)->allows('update', $skp);
        echo ($canUpdate ? '✅ ALLOWED' : '❌ DENIED') . "\n";
    } catch (\Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ❌ SKIP (no auth or no SKP)\n";
}
echo "\n";

// 5. Check Detail Policy
echo "5. DETAIL POLICY CHECK:\n";
$detailId = 1; // GANTI dengan ID detail yang bermasalah
$detail = \App\Models\SkpTahunanDetail::find($detailId);

if ($detail && auth()->check()) {
    $user = auth()->user();

    echo "   Detail ID: " . $detail->id . "\n";
    echo "   Detail SKP ID: " . $detail->skp_tahunan_id . "\n";

    // Load relation
    $detail->load('skpTahunan');
    echo "   SKP User ID: " . ($detail->skpTahunan ? $detail->skpTahunan->user_id : 'NULL') . "\n";
    echo "   SKP Status: " . ($detail->skpTahunan ? $detail->skpTahunan->status : 'NULL') . "\n";

    // Test update
    echo "   Can Update Detail: ";
    try {
        $canUpdate = \Illuminate\Support\Facades\Gate::forUser($user)->allows('update', $detail);
        echo ($canUpdate ? '✅ ALLOWED' : '❌ DENIED') . "\n";
    } catch (\Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n";
    }

    // Test delete
    echo "   Can Delete Detail: ";
    try {
        $canDelete = \Illuminate\Support\Facades\Gate::forUser($user)->allows('delete', $detail);
        echo ($canDelete ? '✅ ALLOWED' : '❌ DENIED') . "\n";
    } catch (\Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ❌ SKIP (no detail or no auth)\n";
}
echo "\n";

echo "==========================================\n";
echo "DEBUG COMPLETE\n";
echo "==========================================\n";
