<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function __construct(protected DashboardService $service) {}

    public function index()
    {
        $data = $this->service->getAllData();
        return view('admin.dashboard', $data);
    }

    public function refresh()
    {
        $this->service->clearCache();
        return redirect()->route('admin.dashboard.index')->with('success', 'Data dashboard berhasil diperbarui');
    }

    /**
     * JSON endpoint: daily reporting data (async / AJAX)
     * GET /admin/dashboard/daily-report?tanggal=2026-02-20
     */
    public function dailyReport(Request $request)
    {
        $tanggal = $request->query('tanggal');
        $data = $this->service->getDailyReportingData($tanggal);
        return response()->json($data);
    }
}
