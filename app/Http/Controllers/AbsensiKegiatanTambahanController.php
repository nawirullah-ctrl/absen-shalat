<?php

namespace App\Http\Controllers;

use App\Exports\RekapAbsensiKegiatanExport;
use App\Models\AbsensiKegiatanTambahan;
use App\Models\KegiatanTambahan;
use App\Models\Kelas;
use App\Models\Santri;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\ProfilPesantren;

class AbsensiKegiatanTambahanController extends Controller
{
    public function index(Request $request)
    {
        $tanggal = $request->tanggal ?? now()->timezone(config('app.timezone'))->toDateString();
        $kelasId = $request->kelas_id;
        $keyword = $request->keyword;

        $kegiatanTambahans = KegiatanTambahan::where('is_active', true)
            ->orderBy('nama')
            ->get();

        $kegiatanTambahanId = $request->kegiatan_tambahan_id;

        if (!$kegiatanTambahanId && $kegiatanTambahans->count() > 0) {
            $kegiatanTambahanId = $kegiatanTambahans->first()->id;
        }

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

        $absensiTersimpan = collect();

        if ($kegiatanTambahanId) {
            $absensiTersimpan = AbsensiKegiatanTambahan::whereDate('tanggal', $tanggal)
                ->where('kegiatan_tambahan_id', $kegiatanTambahanId)
                ->get()
                ->keyBy('santri_id');
        }

        return view('absensi-kegiatan-tambahan.index', compact(
            'tanggal',
            'kelasId',
            'keyword',
            'kelas',
            'kegiatanTambahans',
            'kegiatanTambahanId',
            'santris',
            'absensiTersimpan'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal' => 'required|date',
            'kegiatan_tambahan_id' => 'required|exists:kegiatan_tambahans,id',
            'absensi' => 'required|array',
            'absensi.*.santri_id' => 'required|exists:santris,id',
            'absensi.*.status' => 'required|in:hadir,izin,sakit,alpha',
            'absensi.*.keterangan' => 'nullable|string',
        ]);

        foreach ($validated['absensi'] as $item) {
            AbsensiKegiatanTambahan::updateOrCreate(
                [
                    'santri_id' => $item['santri_id'],
                    'kegiatan_tambahan_id' => $validated['kegiatan_tambahan_id'],
                    'tanggal' => $validated['tanggal'],
                ],
                [
                    'status' => $item['status'],
                    'keterangan' => $item['keterangan'] ?? null,
                    'user_id' => auth()->id(),
                ]
            );
        }

        return redirect()
            ->route('absensi-kegiatan-tambahan.index', [
                'tanggal' => $validated['tanggal'],
                'kegiatan_tambahan_id' => $validated['kegiatan_tambahan_id'],
                'kelas_id' => $request->kelas_id,
                'keyword' => $request->keyword,
            ])
            ->with('success', 'Absensi kegiatan tambahan berhasil disimpan.');
    }

    public function rekap(Request $request)
    {
        $tanggalMulai = $request->tanggal_mulai ?? now()->timezone(config('app.timezone'))->toDateString();
        $tanggalSelesai = $request->tanggal_selesai ?? now()->timezone(config('app.timezone'))->toDateString();
        $kelasId = $request->kelas_id;
        $kegiatanTambahanId = $request->kegiatan_tambahan_id;

        $kelas = Kelas::orderBy('nama')->get();
        $kegiatanTambahans = KegiatanTambahan::orderBy('nama')->get();

        $query = AbsensiKegiatanTambahan::with(['santri.kelas', 'kegiatanTambahan', 'user'])
            ->whereBetween('tanggal', [$tanggalMulai, $tanggalSelesai])
            ->orderBy('tanggal');

        if ($kelasId) {
            $query->whereHas('santri', function ($q) use ($kelasId) {
                $q->where('kelas_id', $kelasId);
            });
        }

        if ($kegiatanTambahanId) {
            $query->where('kegiatan_tambahan_id', $kegiatanTambahanId);
        }

        $rekap = $query->paginate(15)->withQueryString();

        return view('absensi-kegiatan-tambahan.rekap', compact(
            'rekap',
            'tanggalMulai',
            'tanggalSelesai',
            'kelas',
            'kelasId',
            'kegiatanTambahans',
            'kegiatanTambahanId'
        ));
    }

    public function export(Request $request)
    {
        $tanggalMulai = $request->tanggal_mulai ?? now()->timezone(config('app.timezone'))->toDateString();
        $tanggalSelesai = $request->tanggal_selesai ?? now()->timezone(config('app.timezone'))->toDateString();
        $kelasId = $request->kelas_id;
        $kegiatanTambahanId = $request->kegiatan_tambahan_id;

        $namaFile = 'rekap-absensi-kegiatan-' . $tanggalMulai . '-sampai-' . $tanggalSelesai . '.xlsx';

        return Excel::download(
            new RekapAbsensiKegiatanExport($tanggalMulai, $tanggalSelesai, $kelasId, $kegiatanTambahanId),
            $namaFile
        );
    }

    public function pdf(Request $request)
    {
        $tanggalMulai = $request->tanggal_mulai ?? now()->timezone(config('app.timezone'))->toDateString();
        $tanggalSelesai = $request->tanggal_selesai ?? now()->timezone(config('app.timezone'))->toDateString();
        $kelasId = $request->kelas_id;
        $kegiatanTambahanId = $request->kegiatan_tambahan_id;

        $profilPesantren = ProfilPesantren::first();

        $query = AbsensiKegiatanTambahan::with(['santri.kelas', 'kegiatanTambahan', 'user'])
            ->whereBetween('tanggal', [$tanggalMulai, $tanggalSelesai])
            ->orderBy('tanggal');

        if ($kelasId) {
            $query->whereHas('santri', function ($q) use ($kelasId) {
                $q->where('kelas_id', $kelasId);
            });
        }

        if ($kegiatanTambahanId) {
            $query->where('kegiatan_tambahan_id', $kegiatanTambahanId);
        }

        $rekap = $query->get();

        $pdf = Pdf::loadView('pdf.absensi-kegiatan', [
            'rekap' => $rekap,
            'tanggalMulai' => $tanggalMulai,
            'tanggalSelesai' => $tanggalSelesai,
            'profilPesantren' => $profilPesantren,
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('rekap-absensi-kegiatan-' . $tanggalMulai . '-sampai-' . $tanggalSelesai . '.pdf');
    }
}