<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DashboardController extends Controller
{
    /**
     * Display dashboard based on user role
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $role = strtoupper($user->role);

        // Get API token from session
        $token = session('api_token');

        try {
            switch ($role) {
                case 'ASN':
                    return $this->showAsnDashboard($token);

                case 'ATASAN':
                    return $this->showAtasanDashboard($token);

                case 'ADMIN':
                    return $this->showAdminDashboard($token);

                default:
                    return view('dashboard.default', [
                        'stats' => $this->getDefaultStats()
                    ]);
            }
        } catch (\Exception $e) {
            // If API fails, show dashboard with empty data
            return $this->showDashboardWithError($role, $e->getMessage());
        }
    }

    /**
     * Show ASN Dashboard
     */
    private function showAsnDashboard($token)
    {
        // Call API to get ASN dashboard data
        $response = Http::withToken($token)->get(config('app.api_url') . '/asn/dashboard');

        $data = $response->successful() ? $response->json() : [];

        return view('asn.dashboard', [
            'stats' => [
                'total_skp' => $data['total_skp'] ?? 0,
                'kinerja_bulan_ini' => $data['kinerja_bulan_ini'] ?? 0,
                'rencana_aktif' => $data['rencana_aktif'] ?? 0,
                'progres' => $data['progres'] ?? 0,
            ],
            'recent_activities' => $data['recent_activities'] ?? [],
        ]);
    }

    /**
     * Show Atasan Dashboard
     */
    private function showAtasanDashboard($token)
    {
        // Call API to get Atasan dashboard data
        $response = Http::withToken($token)->get(config('app.api_url') . '/atasan/dashboard');

        $data = $response->successful() ? $response->json() : [];

        return view('atasan.dashboard', [
            'stats' => [
                'total_bawahan' => $data['total_bawahan'] ?? 0,
                'pending_approval' => $data['pending_approval'] ?? 0,
                'approved' => $data['approved'] ?? 0,
                'avg_kinerja' => $data['avg_kinerja'] ?? 0,
            ],
            'pending_approvals' => $data['pending_approvals'] ?? [],
            'team_performance' => $data['team_performance'] ?? [],
        ]);
    }

    /**
     * Show Admin Dashboard
     */
    private function showAdminDashboard($token)
    {
        // For now, redirect to users management
        return redirect()->route('admin.users.index');
    }

    /**
     * Get default stats when no data available
     */
    private function getDefaultStats()
    {
        return [
            'total_skp' => 0,
            'kinerja_bulan_ini' => 0,
            'rencana_aktif' => 0,
            'progres' => 0,
        ];
    }

    /**
     * Show dashboard with error message
     */
    private function showDashboardWithError($role, $errorMessage)
    {
        $viewMap = [
            'ASN' => 'asn.dashboard',
            'ATASAN' => 'atasan.dashboard',
            'ADMIN' => 'admin.dashboard',
        ];

        $view = $viewMap[$role] ?? 'dashboard.default';

        return view($view, [
            'stats' => $this->getDefaultStats(),
            'recent_activities' => [],
            'pending_approvals' => [],
            'team_performance' => [],
            'error' => 'Gagal memuat data dashboard. Silakan refresh halaman.',
        ]);
    }
}
