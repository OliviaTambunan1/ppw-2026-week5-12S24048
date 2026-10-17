<x-layout title="Daftar Peminjaman">
    <h3 class="text-del mb-3">Daftar Peminjaman</h3>
    <div class="card shadow-sm"><div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Kode</th><th>Buku</th><th>Peminjam</th><th>Tgl Pinjam</th>
                    <th>Jatuh Tempo</th><th>Tgl Kembali</th><th>Status</th><th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($peminjamans as $p)
                    <tr>
                        <td>{{ $p->kode_pinjam }}</td>
                        <td>{{ $p->buku->judul }}</td>
                        <td>{{ $p->nama_peminjam }}<br><small class="text-muted">{{ $p->nim_peminjam }}</small></td>
                        <td>{{ $p->tanggal_pinjam->format('d M Y') }}</td>
                        <td>{{ $p->tanggal_jatuh_tempo->format('d M Y') }}</td>
                        <td>{{ $p->tanggal_kembali?->format('d M Y') ?? '-' }}</td>
                        <td>
                            <span class="badge {{ $p->status === 'dipinjam' ? 'bg-warning text-dark' : 'bg-success' }}">
                                {{ ucfirst($p->status) }}
                            </span>
                        </td>
                        <td class="text-end">
                            @if ($p->status === 'dipinjam')
                                <form action="{{ route('peminjaman.kembali', $p->id) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Proses pengembalian buku ini?')">
                                    @csrf
                                    @method('PATCH')
                                    <button class="btn btn-sm btn-outline-success">Kembalikan</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">Belum ada data peminjaman.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div></div>
    <div class="mt-3">{{ $peminjamans->links() }}</div>
</x-layout>