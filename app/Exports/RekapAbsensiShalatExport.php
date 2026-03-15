<?php

namespace App\Exports;

use App\Models\AbsensiShalat;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class RekapAbsensiShalatExport implements FromCollection, WithHeadings
{
    protected $tanggalMulai;
    protected $tanggalSelesai;
    protected $kelasId;

    public function __construct($tanggalMulai, $tanggalSelesai, $kelasId = null)
    {
        $this->tanggalMulai = $tanggalMulai;
        $this->tanggalSelesai = $tanggalSelesai;
        $this->kelasId = $kelasId;
    }

    public function collection()
    {
        $query = AbsensiShalat::with(['santri.kelas', 'user'])
            ->whereBetween('tanggal', [$this->tanggalMulai, $this->tanggalSelesai])
            ->orderBy('tanggal')
            ->orderBy('waktu_shalat');

        if ($this->kelasId) {
            $query->whereHas('santri', function ($q) {
                $q->where('kelas_id', $this->kelasId);
            });
        }

        return $query->get()->map(function ($item) {
            return [
                'tanggal' => $item->tanggal->format('Y-m-d'),
                'waktu_shalat' => ucfirst($item->waktu_shalat),
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
            'Waktu Shalat',
            'Nama Santri',
            'Kelas',
            'Status',
            'Keterangan',
            'Input Oleh',
        ];
    }
}