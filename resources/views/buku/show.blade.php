<x-layout :title="$buku->judul">
    <div class="card shadow-sm">
        <div class="card-body">
            <h3 class="text-del">{{ $buku->judul }}</h3>
            <p class="text-muted">oleh {{ $buku->penulis }} · {{ $buku->penerbit }} ({{ $buku->tahun_terbit }})</p>
            <dl class="row">
                <dt class="col-sm-3">ISBN</dt>
                <dd class="col-sm-9">{{ $buku->isbn }}</dd>
                <dt class="col-sm-3">Kategori</dt>
                <dd class="col-sm-9">{{ $buku->kategori->nama_kategori }}</dd>
                <dt class="col-sm-3">Stok</dt>
                <dd class="col-sm-9">{{ $buku->stok }}</dd>
                <dt class="col-sm-3">Sinopsis</dt>
                <dd class="col-sm-9">{{ $buku->sinopsis ?: '-' }}</dd>
            </dl>

            @auth
            <hr>
            <h5 class="text-del">Pinjamkan Buku</h5>
            <form action="{{ route('buku.pinjam', $buku->id) }}" method="POST" class="row g-3 mb-3">
                @csrf
                <div class="col-md-4">
                    <label class="form-label">NIM Peminjam</label>
                    <input type="text" name="nim_peminjam" value="{{ old('nim_peminjam') }}"
                        class="form-control @error('nim_peminjam') is-invalid @enderror">
                    @error('nim_peminjam') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-5">
                    <label class="form-label">Nama Peminjam</label>
                    <input type="text" name="nama_peminjam" value="{{ old('nama_peminjam') }}"
                        class="form-control @error('nama_peminjam') is-invalid @enderror">
                    @error('nama_peminjam') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-del w-100">Pinjam</button>
                </div>
            </form>
            @else
            <hr>
            <p class="text-muted"><a href="{{ route('login') }}">Login sebagai pustakawan</a> untuk memproses peminjaman.</p>
            @endauth

            <a href="{{ route('buku.index') }}" class="btn btn-outline-secondary">Kembali</a>
        </div>
    </div>
</x-layout>