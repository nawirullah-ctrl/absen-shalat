<?php

namespace App\Exports;

use App\Models\AbsensiKegiatanTambahan;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class RekapAbsensiKegiatanExport implements FromCollection, WithHeadings
{
    protected $tanggalMulai;
    protected $tanggalSelesai;
    protected $kelasId;
    protected $kegiatanTambahanId;

    public function __construct($tanggalMulai, $tanggalSelesai, $kelasId = null, $kegiatanTambahanId = null)
    {
        $this->tanggalMulai = $tanggalMulai;
        $this->tanggalSelesai = $tanggalSelesai;
        $this->kelasId = $kelasId;
        $this->kegiatanTambahanId = $kegiatanTambahanId;
    }

    public function collection()
    {
        $query = AbsensiKegiatanTambahan::with(['santri.kelas', 'kegiatanTambahan', 'user'])
            ->whereBetween('tanggal', [$this->tanggalMulai, $this->tanggalSelesai])
            ->orderBy('tanggal');

        if ($this->kelasId) {
            $query->whereHas('santri', function ($q) {
                $q->where('kelas_id', $this->kelasId);
            });
        }

        if ($this->kegiatanTambahanId) {
            $query->where('kegiatan_tambahan_id', $this->kegiatanTambahanId);
        }

        return $query->get()->map(function ($item) {
            return [
                'tanggal' => $item->tanggal->format('Y-m-d'),
                'kegiatan' => $item->kegiatanTambahan->nama ?? '-',
                'nama_santri' => $item->santri->nama ?? '-',
                'kelas' => $item->santri->kelas->nama ?? '-',
                'status' => ucfirst($item->status),
                'keterangan' => $item->keterangan ?? '-',
                'input_oleh' => $item->user->name ?? '-',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Kegiatan',
            'Nama Santri',
            'Kelas',
            'Status',
            'Keterangan',
            'Input Oleh',
        ];
    }
}