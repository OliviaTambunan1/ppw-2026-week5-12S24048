<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BukuResource;
use App\Models\Buku;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BukuApiController extends Controller
{
    use ApiResponse;

    /**
     * Aturan validasi. Mode $partial (PATCH) menambahkan 'sometimes' ke tiap field,
     * sehingga hanya field yang dikirim yang divalidasi.
     */
    private function rules(?Buku $buku = null, bool $partial = false): array
    {
        $rules = [
            'isbn'         => ['required', 'digits_between:10,13', Rule::unique('bukus', 'isbn')->ignore($buku?->id)],
            'judul'        => ['required', 'string', 'min:5', 'max:200'],
            'penulis'      => ['required', 'string', 'max:150'],
            'penerbit'     => ['required', 'string', 'max:100'],
            'tahun_terbit' => ['required', 'integer', 'min:1900', 'max:' . date('Y')],
            'kategori_id'  => ['required', 'exists:kategoris,id'],
            'stok'         => ['required', 'integer', 'min:0'],
            'sinopsis'     => ['nullable', 'string'],
        ];

        if ($partial) {
            foreach ($rules as $field => $rule) {
                array_unshift($rules[$field], 'sometimes');
            }
        }

        return $rules;
    }

    private function bukuNotFound(string $id): JsonResponse
    {
        return $this->problemResponse(
            title: 'Resource Not Found',
            detail: "Buku dengan ID '{$id}' tidak ditemukan di basis data.",
            status: 404,
            errorSlug: 'buku-not-found'
        );
    }

    /**
     * GET /api/v1/bukus (publik): search, filter, sort, paginasi. Bebas N+1.
     */
    public function index(Request $request): JsonResponse
    {
        $allowedSorts = ['judul', 'penulis', 'tahun_terbit', 'stok', 'created_at'];
        $sortBy  = in_array($request->query('sort'), $allowedSorts) ? $request->query('sort') : 'judul';
        $sortDir = strtolower($request->query('dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $perPage = min(max((int) $request->query('per_page', 5), 1), 50);

        $paginator = Buku::query()
            ->with('kategori')
            ->withCount('peminjamans')
            ->when($request->query('search'), function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('judul', 'like', "%{$search}%")
                        ->orWhere('penulis', 'like', "%{$search}%")
                        ->orWhere('isbn', 'like', "%{$search}%");
                });
            })
            ->when($request->query('kategori_id'), fn ($q, $k) => $q->where('kategori_id', (int) $k))
            ->when($request->boolean('tersedia'), fn ($q) => $q->where('stok', '>', 0))
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage)
            ->withQueryString();

        return $this->successResponse(
            data: BukuResource::collection($paginator->items()),
            message: 'Daftar koleksi buku berhasil diambil.',
            meta: [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total_items'  => $paginator->total(),
                'total_pages'  => $paginator->lastPage(),
                'links'        => [
                    'first' => $paginator->url(1),
                    'prev'  => $paginator->previousPageUrl(),
                    'next'  => $paginator->nextPageUrl(),
                    'last'  => $paginator->url($paginator->lastPage()),
                ],
            ]
        );
    }

    /**
     * GET /api/v1/bukus/{id} (publik): detail + kategori + riwayat peminjaman
     */
    public function show(string $id): JsonResponse
    {
        $buku = Buku::with(['kategori', 'peminjamans'])
            ->withCount('peminjamans')
            ->find($id);

        if (!$buku) {
            return $this->bukuNotFound($id);
        }

        return $this->successResponse(
            data: new BukuResource($buku),
            message: 'Detail buku berhasil ditemukan.'
        );
    }

    /**
     * POST /api/v1/bukus (Bearer): 201 Created + header Location
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rules());

        $buku = Buku::create($validated);
        $buku->load('kategori')->loadCount('peminjamans');

        return $this->createdResponse(
            data: new BukuResource($buku),
            locationUrl: url("/api/v1/bukus/{$buku->id}"),
            message: 'Buku baru berhasil dibuat.'
        );
    }

    /**
     * PUT (penggantian penuh) dan PATCH (ubah parsial) /api/v1/bukus/{id} (Bearer)
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $buku = Buku::find($id);

        if (!$buku) {
            return $this->bukuNotFound($id);
        }

        $isPut = $request->isMethod('put');
        $validated = $request->validate($this->rules($buku, partial: !$isPut));

        if ($isPut) {
            // PUT = representasi diganti utuh: field opsional yang tidak dikirim direset ke null
            $validated['sinopsis'] = $request->input('sinopsis');
        } elseif (empty($validated)) {
            return $this->problemResponse(
                title: 'Bad Request',
                detail: 'Permintaan PATCH wajib menyertakan minimal satu atribut yang ingin diperbarui.',
                status: 400,
                errorSlug: 'empty-patch-payload'
            );
        }

        $buku->update($validated);
        $buku->load('kategori')->loadCount('peminjamans');

        return $this->successResponse(
            data: new BukuResource($buku),
            message: $isPut
                ? 'Seluruh representasi buku berhasil diganti (PUT).'
                : 'Atribut buku berhasil diperbarui sebagian (PATCH).'
        );
    }

    /**
     * DELETE /api/v1/bukus/{id} (Bearer): 409 bila masih dipinjam, 204 bila sukses
     */
    public function destroy(string $id): JsonResponse
    {
        $buku = Buku::find($id);

        if (!$buku) {
            return $this->bukuNotFound($id);
        }

        $aktif = $buku->peminjamans()->where('status', 'dipinjam')->count();

        if ($aktif > 0) {
            return $this->problemResponse(
                title: 'Conflict - Book Has Active Loans',
                detail: "Buku '{$buku->judul}' tidak dapat dihapus karena masih memiliki {$aktif} peminjaman aktif.",
                status: 409,
                errorSlug: 'buku-has-active-loans'
            );
        }

        $buku->delete();

        return $this->noContentResponse();
    }
}