<?php

namespace App\Http\Controllers;

use App\Models\Survei;
use App\Models\SurveiJawaban;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SurveiController extends Controller
{
    public function show()
    {
        $user = Auth::user();

        if ($user->role === 'ADMIN') {
            abort(403);
        }

        $survei = Survei::aktif()->with('pertanyaan')->latest()->first();

        if (!$survei) {
            return view('survei.show', ['survei' => null, 'sudahIsi' => false]);
        }

        $sudahIsi = SurveiJawaban::select('id')
            ->where('survei_id', $survei->id)
            ->where('user_id', $user->id)
            ->limit(1)
            ->exists();

        if ($sudahIsi) {
            return redirect()->route('dashboard')
                ->with('info', 'Anda sudah pernah mengisi survei ini.');
        }

        return view('survei.show', compact('survei', 'sudahIsi'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        if ($user->role === 'ADMIN') {
            abort(403);
        }

        $survei = Survei::aktif()->latest()->first();

        if (!$survei) {
            abort(404);
        }

        $alreadyExists = SurveiJawaban::select('id')
            ->where('survei_id', $survei->id)
            ->where('user_id', $user->id)
            ->limit(1)
            ->exists();

        if ($alreadyExists) {
            return redirect()->route('dashboard')
                ->with('info', 'Anda sudah pernah mengisi survei ini.');
        }

        $validated = $request->validate([
            'q1'    => 'required|integer|min:1|max:5',
            'q2'    => 'required|integer|min:1|max:5',
            'q3'    => 'required|integer|min:1|max:5',
            'q4'    => 'required|integer|min:1|max:5',
            'q5'    => 'required|integer|min:1|max:5',
            'q6'    => 'required|integer|min:1|max:5',
            'q7'    => 'required|integer|min:1|max:5',
            'q8'    => 'required|integer|min:1|max:5',
            'q9'    => 'required|integer|min:1|max:5',
            'saran' => 'nullable|string|max:2000',
        ], [
            'q1.required' => 'Pertanyaan 1 wajib diisi.',
            'q2.required' => 'Pertanyaan 2 wajib diisi.',
            'q3.required' => 'Pertanyaan 3 wajib diisi.',
            'q4.required' => 'Pertanyaan 4 wajib diisi.',
            'q5.required' => 'Pertanyaan 5 wajib diisi.',
            'q6.required' => 'Pertanyaan 6 wajib diisi.',
            'q7.required' => 'Pertanyaan 7 wajib diisi.',
            'q8.required' => 'Pertanyaan 8 wajib diisi.',
            'q9.required' => 'Pertanyaan 9 wajib diisi.',
        ]);

        DB::transaction(function () use ($survei, $user, $validated) {
            SurveiJawaban::create([
                'survei_id'     => $survei->id,
                'user_id'       => $user->id,
                'unit_kerja_id' => $user->unit_kerja_id ?? null,
                'q1'            => $validated['q1'],
                'q2'            => $validated['q2'],
                'q3'            => $validated['q3'],
                'q4'            => $validated['q4'],
                'q5'            => $validated['q5'],
                'q6'            => $validated['q6'],
                'q7'            => $validated['q7'],
                'q8'            => $validated['q8'],
                'q9'            => $validated['q9'],
                'saran'         => $validated['saran'] ?? null,
                'submitted_at'  => Carbon::now(),
            ]);
        });

        return redirect()->route('dashboard')
            ->with('success', 'Terima kasih, survei berhasil dikirim.');
    }
}
