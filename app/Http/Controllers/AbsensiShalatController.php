<?php

namespace App\Http\Controllers;

use App\Exports\RekapAbsensiShalatExport;
use App\Models\AbsensiShalat;
use App\Models\Kelas;
use App\Models\Santri;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\ProfilPesantren;

class AbsensiShalatController extends Controller
{
    public function index(Request $request)
    {
        $tanggal = $request->tanggal ?? now()->timezone(config('app.timezone'))->toDateString();
        $waktuShalat = $request->waktu_shalat ?? 'subuh';
        $kelasId = $request->kelas_id;
        $keyword = $request->keyword;

        $daftarWaktuShalat = [
            'subuh',
            'dzuhur',
            'ashar',
            'maghrib',
            'isya',
        ];

        $kelas = Kelas::orderBy('nama')->get();

        $query = Santri::with('kelas')
            ->where('status', 'aktif');

        if ($kelasId) {
            $query->where('kelas_id', $kelasId);
        }

        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('nama', 'like', '%' . $keyword . '%')
                  ->orWhere('nis', 'like', '%' . $keyword . '%');
            });
        }

        $santris = $query->orderBy('nama')->get();

        $absensiTersimpan = AbsensiShalat::whereDate('tanggal', $tanggal)
            ->where('waktu_shalat', $waktuShalat)
            ->get()
            ->keyBy('santri_id');

        return view('absensi-shalat.index', compact(
            'tanggal',
            'waktuShalat',
            'kelasId',
            'keyword',
            'kelas',
            'daftarWaktuShalat',
            'santris',
            'absensiTersimpan'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal' => 'required|date',
            'waktu_shalat' => 'required|in:subuh,dzuhur,ashar,maghrib,isya',
            'absensi' => 'required|array',
            'absensi.*.santri_id' => 'required|exists:santris,id',
            'absensi.*.status' => 'required|in:hadir,masbuk,izin,sakit,alpha',
            'absensi.*.keterangan' => 'nullable|string',
        ]);

        foreach ($validated['absensi'] as $item) {
            AbsensiShalat::updateOrCreate(
                [
                    'santri_id' => $item['santri_id'],
                    'tanggal' => $validated['tanggal'],
                    'waktu_shalat' => $validated['waktu_shalat'],
                ],
                [
                    'status' => $item['status'],
                    'keterangan' => $item['keterangan'] ?? null,
                    'user_id' => auth()->id(),
                ]
            );
        }

        return redirect()
            ->route('absensi-shalat.index', [
                'tanggal' => $validated['tanggal'],
                'waktu_shalat' => $validated['waktu_shalat'],
                'kelas_id' => $request->kelas_id,
                'keyword' => $request->keyword,
            ])
            ->with('success', 'Absensi shalat berhasil disimpan.');
    }

    public function rekap(Request $request)
    {
        $tanggalMulai = $request->tanggal_mulai ?? now()->timezone(config('app.timezone'))->toDateString();
        $tanggalSelesai = $request->tanggal_selesai ?? now()->timezone(config('app.timezone'))->toDateString();
        $kelasId = $request->kelas_id;

        $kelas = Kelas::orderBy('nama')->get();

        $query = AbsensiShalat::with(['santri.kelas', 'user'])
            ->whereBetween('tanggal', [$tanggalMulai, $tanggalSelesai])
            ->orderBy('tanggal')
            ->orderBy('waktu_shalat');

        if ($kelasId) {
            $query->whereHas('santri', function ($q) use ($kelasId) {
                $q->where('kelas_id', $kelasId);
            });
        }

        $rekap = $query->paginate(15)->withQueryString();

        return view('absensi-shalat.rekap', compact(
            'rekap',
            'tanggalMulai',
            'tanggalSelesai',
            'kelas',
            'kelasId'
        ));
    }

    public function export(Request $request)
    {
        $tanggalMulai = $request->tanggal_mulai ?? now()->timezone(config('app.timezone'))->toDateString();
        $tanggalSelesai = $request->tanggal_selesai ?? now()->timezone(config('app.timezone'))->toDateString();
        $kelasId = $request->kelas_id;

        $namaFile = 'rekap-absensi-shalat-' . $tanggalMulai . '-sampai-' . $tanggalSelesai . '.xlsx';

        return Excel::download(
            new RekapAbsensiShalatExport($tanggalMulai, $tanggalSelesai, $kelasId),
            $namaFile
        );
    }

    public function pdf(Request $request)
    {
        $tanggalMulai = $request->tanggal_mulai ?? now()->timezone(config('app.timezone'))->toDateString();
        $tanggalSelesai = $request->tanggal_selesai ?? now()->timezone(config('app.timezone'))->toDateString();
        $kelasId = $request->kelas_id;

        $profilPesantren = ProfilPesantren::first();

        $query = AbsensiShalat::with(['santri.kelas', 'user'])
            ->whereBetween('tanggal', [$tanggalMulai, $tanggalSelesai])
            ->orderBy('tanggal')
            ->orderBy('waktu_shalat');

        if ($kelasId) {
            $query->whereHas('santri', function ($q) use ($kelasId) {
                $q->where('kelas_id', $kelasId);
            });
        }

        $rekap = $query->get();

        $pdf = Pdf::loadView('pdf.absensi-shalat', [
            'rekap' => $rekap,
            'tanggalMulai' => $tanggalMulai,
            'tanggalSelesai' => $tanggalSelesai,
            'profilPesantren' => $profilPesantren,
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('rekap-absensi-shalat-' . $tanggalMulai . '-sampai-' . $tanggalSelesai . '.pdf');
    }
}