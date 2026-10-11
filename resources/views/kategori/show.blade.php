<x-layout :title="$kategori->nama_kategori">
    <h3 class="text-del">{{ $kategori->nama_kategori }} <small class="text-muted">({{ $kategori->kode_kategori }})</small></h3>
    <h5 class="mt-4">Buku dalam kategori ini</h5>
    <ul class="list-group shadow-sm">
        @forelse ($bukus as $buku)
            <li class="list-group-item d-flex justify-content-between">
                <a href="{{ route('buku.show', $buku) }}">{{ $buku->judul }}</a>
                <span class="text-muted">stok: {{ $buku->stok }}</span>
            </li>
        @empty
            <li class="list-group-item text-muted">Belum ada buku.</li>
        @endforelse
    </ul>
    <div class="mt-3">{{ $bukus->links() }}</div>
    <a href="{{ route('kategori.index') }}" class="btn btn-outline-secondary mt-2">Kembali</a>
</x-layout>