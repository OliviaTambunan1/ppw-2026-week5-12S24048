<x-layout title="Daftar Buku">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="text-del mb-0">Daftar Buku</h3>
        @auth
            <a href="{{ route('buku.create') }}" class="btn btn-del">+ Tambah Buku</a>
        @endauth
    </div>

    {{-- Kartu statistik (DB::table) --}}
    <div class="row g-3 mb-4">
        @foreach ($stats as $label => $nilai)
            <div class="col-6 col-md-3">
                <div class="card shadow-sm h-100"><div class="card-body">
                    <div class="text-muted small">{{ $label }}</div>
                    <div class="fs-3 fw-bold text-del">{{ $nilai }}</div>
                </div></div>
            </div>
        @endforeach
    </div>

    {{-- Filter dinamis --}}
    <form method="GET" action="{{ route('buku.index') }}" class="row g-2 mb-2">
        <div class="col-md-6">
            <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                   placeholder="Cari judul, penulis, atau ISBN...">
        </div>
        <div class="col-md-4">
            <select name="kategori_id" class="form-select">
                <option value="">Semua Kategori</option>
                @foreach ($kategoris as $k)
                    <option value="{{ $k->id }}" @selected(request('kategori_id') == $k->id)>{{ $k->nama_kategori }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 d-grid"><button class="btn btn-del">Cari</button></div>
    </form>

    @if (session('last_search'))
        <p class="small text-muted mb-3">Pencarian terakhir:
            <a href="{{ route('buku.index', ['search' => session('last_search')]) }}">{{ session('last_search') }}</a>
        </p>
    @endif

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ISBN</th><th>Judul</th><th>Penulis</th><th>Kategori</th>
                        <th>Stok</th><th>Dipinjam</th><th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bukus as $buku)
                        <tr>
                            <td>{{ $buku->isbn }}</td>
                            <td>{{ $buku->judul }}</td>
                            <td>{{ $buku->penulis }}</td>
                            <td><span class="badge bg-secondary">{{ $buku->kategori->nama_kategori }}</span></td>
                            <td>{{ $buku->stok }}</td>
                            <td>{{ $buku->peminjamans_count }}x</td>
                            <td class="text-end">
                                <a href="{{ route('buku.show', $buku) }}" class="btn btn-sm btn-outline-secondary">Detail</a>
                                @auth
                                    <a href="{{ route('buku.edit', $buku) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form action="{{ route('buku.destroy', $buku) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Hapus buku ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                    </form>
                                @endauth
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">Tidak ada buku yang cocok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $bukus->links() }}</div>
</x-layout>