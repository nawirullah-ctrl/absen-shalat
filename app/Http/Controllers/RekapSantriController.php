<?php

namespace App\Http\Controllers;

use App\Models\AbsensiKegiatanTambahan;
use App\Models\AbsensiShalat;
use App\Models\ProfilPesantren;
use App\Models\Santri;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class RekapSantriController extends Controller
{
    public function index(Request $request)
    {
        $santriId = $request->santri_id;
        $tanggalMulai = $request->tanggal_mulai ?? now()->timezone(config('app.timezone'))->startOfMonth()->toDateString();
        $tanggalSelesai = $request->tanggal_selesai ?? now()->timezone(config('app.timezone'))->toDateString();
        $cariSantri = $request->cari_santri;

        $santris = Santri::with('kelas')
            ->where('status', 'aktif')
            ->when($cariSantri, function ($query) use ($cariSantri) {
                $query->where('nama', 'like', '%' . $cariSantri . '%');
            })
            ->orderBy('nama')
            ->get();

        $santri = null;
        $rekapShalat = collect();
        $rekapKegiatan = collect();

        $ringkasanShalat = [
            'hadir' => 0,
            'masbuk' => 0,
            'izin' => 0,
            'sakit' => 0,
            'alpha' => 0,
        ];

        $ringkasanKegiatan = [
            'hadir' => 0,
            'izin' => 0,
            'sakit' => 0,
            'alpha' => 0,
        ];

        if ($santriId) {
            $santri = Santri::with('kelas')->find($santriId);

            if ($santri) {
                $rekapShalat = AbsensiShalat::with('user')
                    ->where('santri_id', $santriId)
                    ->whereBetween('tanggal', [$tanggalMulai, $tanggalSelesai])
                    ->orderBy('tanggal')
                    ->orderBy('waktu_shalat')
                    ->get();

                $rekapKegiatan = AbsensiKegiatanTambahan::with(['kegiatanTambahan', 'user'])
                    ->where('santri_id', $santriId)
                    ->whereBetween('tanggal', [$tanggalMulai, $tanggalSelesai])
                    ->orderBy('tanggal')
                    ->get();

                foreach (['hadir', 'masbuk', 'izin', 'sakit', 'alpha'] as $status) {
                    $ringkasanShalat[$status] = $rekapShalat->where('status', $status)->count();
                }

                foreach (['hadir', 'izin', 'sakit', 'alpha'] as $status) {
                    $ringkasanKegiatan[$status] = $rekapKegiatan->where('status', $status)->count();
                }
            }
        }

        return view('rekap-santri.index', compact(
            'santris',
            'santri',
            'santriId',
            'tanggalMulai',
            'tanggalSelesai',
            'rekapShalat',
            'rekapKegiatan',
            'ringkasanShalat',
            'ringkasanKegiatan',
            'cariSantri'
        ));
    }

    public function pdf(Request $request)
    {
        $santriId = $request->santri_id;
        $tanggalMulai = $request->tanggal_mulai ?? now()->timezone(config('app.timezone'))->startOfMonth()->toDateString();
        $tanggalSelesai = $request->tanggal_selesai ?? now()->timezone(config('app.timezone'))->toDateString();

        $profilPesantren = ProfilPesantren::first();
        $santri = null;
        $rekapShalat = collect();
        $rekapKegiatan = collect();

        $ringkasanShalat = [
            'hadir' => 0,
            'masbuk' => 0,
            'izin' => 0,
            'sakit' => 0,
            'alpha' => 0,
        ];

        $ringkasanKegiatan = [
            'hadir' => 0,
            'izin' => 0,
            'sakit' => 0,
            'alpha' => 0,
        ];

        if ($santriId) {
            $santri = Santri::with('kelas')->find($santriId);

            if ($santri) {
                $rekapShalat = AbsensiShalat::with('user')
                    ->where('santri_id', $santriId)
                    ->whereBetween('tanggal', [$tanggalMulai, $tanggalSelesai])
                    ->orderBy('tanggal')
                    ->orderBy('waktu_shalat')
                    ->get();

                $rekapKegiatan = AbsensiKegiatanTambahan::with(['kegiatanTambahan', 'user'])
                    ->where('santri_id', $santriId)
                    ->whereBetween('tanggal', [$tanggalMulai, $tanggalSelesai])
                    ->orderBy('tanggal')
                    ->get();

                foreach (['hadir', 'masbuk', 'izin', 'sakit', 'alpha'] as $status) {
                    $ringkasanShalat[$status] = $rekapShalat->where('status', $status)->count();
                }

                foreach (['hadir', 'izin', 'sakit', 'alpha'] as $status) {
                    $ringkasanKegiatan[$status] = $rekapKegiatan->where('status', $status)->count();
                }
            }
        }

        $pdf = Pdf::loadView('pdf.rekap-santri', [
            'profilPesantren' => $profilPesantren,
            'santri' => $santri,
            'tanggalMulai' => $tanggalMulai,
            'tanggalSelesai' => $tanggalSelesai,
            'rekapShalat' => $rekapShalat,
            'rekapKegiatan' => $rekapKegiatan,
            'ringkasanShalat' => $ringkasanShalat,
            'ringkasanKegiatan' => $ringkasanKegiatan,
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('rekap-per-santri.pdf');
    }

    public function bulkDeleteShalat(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:absensi_shalats,id',
            'santri_id' => 'required|exists:santris,id',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date',
        ], [
            'ids.required' => 'Pilih minimal satu data absensi shalat yang ingin dihapus.',
        ]);

        $jumlahDihapus = AbsensiShalat::where('santri_id', $request->santri_id)
            ->whereIn('id', $request->ids)
            ->delete();

        if ($jumlahDihapus < 1) {
            return redirect()
                ->route('rekap-santri.index', [
                    'santri_id' => $request->santri_id,
                    'tanggal_mulai' => $request->tanggal_mulai,
                    'tanggal_selesai' => $request->tanggal_selesai,
                ])
                ->with('error', 'Data absensi shalat yang dipilih tidak valid atau tidak bisa dihapus.');
        }

        return redirect()
            ->route('rekap-santri.index', [
                'santri_id' => $request->santri_id,
                'tanggal_mulai' => $request->tanggal_mulai,
                'tanggal_selesai' => $request->tanggal_selesai,
            ])
            ->with('success', $jumlahDihapus . ' data absensi shalat berhasil dihapus.');
    }
    public function bulkDeleteKegiatan(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:absensi_kegiatan_tambahans,id',
            'santri_id' => 'required|exists:santris,id',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date',
        ], [
            'ids.required' => 'Pilih minimal satu data absensi kegiatan yang ingin dihapus.',
        ]);

        $jumlahDihapus = AbsensiKegiatanTambahan::where('santri_id', $request->santri_id)
            ->whereIn('id', $request->ids)
            ->delete();

        if ($jumlahDihapus < 1) {
            return redirect()
                ->route('rekap-santri.index', [
                    'santri_id' => $request->santri_id,
                    'tanggal_mulai' => $request->tanggal_mulai,
                    'tanggal_selesai' => $request->tanggal_selesai,
                ])
                ->with('error', 'Data absensi kegiatan yang dipilih tidak valid atau tidak bisa dihapus.');
        }

        return redirect()
            ->route('rekap-santri.index', [
                'santri_id' => $request->santri_id,
                'tanggal_mulai' => $request->tanggal_mulai,
                'tanggal_selesai' => $request->tanggal_selesai,
            ])
            ->with('success', $jumlahDihapus . ' data absensi kegiatan berhasil dihapus.');
    }
}