<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PeminjamanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $links = [
            'self' => [
                'href'   => url("/api/v1/peminjamans/{$this->id}"),
                'method' => 'GET',
            ],
        ];

        // HATEOAS: aksi "kembalikan" hanya ada selama buku belum dikembalikan
        if ($this->status === 'dipinjam') {
            $links['kembalikan_buku'] = [
                'href'   => url("/api/v1/peminjamans/{$this->id}/kembalikan"),
                'method' => 'PATCH',
            ];
        }

        return [
            'id'                  => $this->id,
            'kode_pinjam'         => $this->kode_pinjam,
            'nim_peminjam'        => $this->nim_peminjam,
            'nama_peminjam'       => $this->nama_peminjam,
            'tanggal_pinjam'      => $this->tanggal_pinjam?->toDateString(),
            'tanggal_jatuh_tempo' => $this->tanggal_jatuh_tempo?->toDateString(),
            'tanggal_kembali'     => $this->tanggal_kembali?->toDateString(),
            'status'              => $this->status,
            'buku'                => $this->whenLoaded('buku', fn () => [
                'id'    => $this->buku->id,
                'isbn'  => $this->buku->isbn,
                'judul' => $this->buku->judul,
            ]),
            '_links'              => $links,
        ];
    }
}