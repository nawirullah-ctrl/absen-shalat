@extends('layouts.admin')

@section('content')
    <div class="mb-6">
        <h3 class="text-xl font-semibold">Rekap Absensi Shalat</h3>
        <p class="text-sm text-gray-500">Lihat rekap absensi shalat berdasarkan rentang tanggal.</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <form action="{{ route('absensi-shalat.rekap') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="tanggal_mulai" class="block text-sm font-medium mb-2">Tanggal Mulai</label>
                <input type="date" name="tanggal_mulai" id="tanggal_mulai" value="{{ $tanggalMulai }}"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5">
            </div>

            <div>
                <label for="tanggal_selesai" class="block text-sm font-medium mb-2">Tanggal Selesai</label>
                <input type="date" name="tanggal_selesai" id="tanggal_selesai" value="{{ $tanggalSelesai }}"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5">
            </div>

            <div>
                <label for="kelas_id" class="block text-sm font-medium mb-2">Kelas</label>
                <select name="kelas_id" id="kelas_id"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5">
                    <option value="">Semua Kelas</option>
                    @foreach($kelas as $item)
                        <option value="{{ $item->id }}" {{ (string) $kelasId === (string) $item->id ? 'selected' : '' }}>
                            {{ $item->nama }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end">
                <button type="submit" class="bg-green-700 text-white px-5 py-2.5 rounded-lg hover:bg-green-800">
                    Tampilkan
                </button>
            </div>
        </form>

        <div class="mb-6">
            <a href="{{ route('absensi-shalat.export', [
                'tanggal_mulai' => $tanggalMulai,
                'tanggal_selesai' => $tanggalSelesai,
                'kelas_id' => $kelasId
            ]) }}"
            class="inline-block bg-emerald-600 text-white px-4 py-2.5 rounded-lg hover:bg-emerald-700">
                Export Excel
            </a>

            <a href="{{ route('absensi-shalat.pdf', [
                'tanggal_mulai' => $tanggalMulai,
                'tanggal_selesai' => $tanggalSelesai,
                'kelas_id' => $kelasId
            ]) }}"
            target="_blank"
            class="inline-block bg-red-600 text-white px-4 py-2.5 rounded-lg hover:bg-red-700">
                Export PDF
            </a>
        </div>

        
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-4 py-3 text-left">No</th>
                        <th class="px-4 py-3 text-left">Tanggal</th>
                        <th class="px-4 py-3 text-left">Waktu</th>
                        <th class="px-4 py-3 text-left">Santri</th>
                        <th class="px-4 py-3 text-left">Kelas</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">Keterangan</th>
                        <th class="px-4 py-3 text-left">Input Oleh</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rekap as $item)
                        <tr class="border-t">
                            <td class="px-4 py-3">{{ $rekap->firstItem() + $loop->index }}</td>
                            <td class="px-4 py-3">{{ $item->tanggal->format('Y-m-d') }}</td>
                            <td class="px-4 py-3">{{ ucfirst($item->waktu_shalat) }}</td>
                            <td class="px-4 py-3 font-medium">{{ $item->santri->nama ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $item->santri->kelas->nama ?? '-' }}</td>
                            <td class="px-4 py-3">
                                @if($item->status === 'hadir')
                                <span class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-800">
                                    Hadir
                                </span>
                            @elseif($item->status === 'masbuk')
                                <span class="inline-flex items-center rounded-full bg-yellow-100 px-3 py-1 text-xs font-medium text-yellow-800">
                                    Masbuk
                                </span>
                            @elseif($item->status === 'izin')
                                <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-800">
                                    Izin
                                </span>
                            @elseif($item->status === 'sakit')
                                <span class="inline-flex items-center rounded-full bg-orange-100 px-3 py-1 text-xs font-medium text-orange-800">
                                    Sakit
                                </span>
                            @elseif($item->status === 'alpha')
                                <span class="inline-flex items-center rounded-full bg-red-100 px-3 py-1 text-xs font-medium text-red-800">
                                    Alpha
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-800">
                                    {{ ucfirst($item->status) }}
                                </span>
                            @endif
                            </td>
                            <td class="px-4 py-3">{{ $item->keterangan ?: '-' }}</td>
                            <td class="px-4 py-3">{{ $item->user->name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr class="border-t">
                            <td colspan="8" class="px-4 py-6 text-center text-gray-500">
                                Belum ada data rekap absensi shalat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-4 border-t">
            {{ $rekap->links() }}
        </div>
    </div>
@endsection