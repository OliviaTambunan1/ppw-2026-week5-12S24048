<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PeminjamanResource;
use App\Models\Buku;
use App\Models\Peminjaman;
use App\Traits\ApiResponse;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PeminjamanApiController extends Controller
{
    use ApiResponse;

    private function pinjamanNotFound(string $id): JsonResponse
    {
        return $this->problemResponse(
            title: 'Resource Not Found',
            detail: "Data peminjaman dengan ID '{$id}' tidak ditemukan.",
            status: 404,
            errorSlug: 'peminjaman-not-found'
        );
    }

    /**
     * GET /api/v1/peminjamans (Bearer): filter ?status= dan ?nim=
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->query('per_page', 5), 1), 50);
        $status = $request->query('status');

        $paginator = Peminjaman::query()
            ->with('buku')
            ->when(in_array($status, ['dipinjam', 'dikembalikan'], true), fn ($q) => $q->where('status', $status))
            ->when($request->query('nim'), fn ($q, $nim) => $q->where('nim_peminjam', strtoupper(trim($nim))))
            ->latest()
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return $this->successResponse(
            data: PeminjamanResource::collection($paginator->items()),
            message: 'Daftar transaksi peminjaman berhasil diambil.',
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
     * GET /api/v1/peminjamans/{id} (Bearer): tujuan header Location setelah POST
     */
    public function show(string $id): JsonResponse
    {
        $pinjam = Peminjaman::with('buku')->find($id);

        if (!$pinjam) {
            return $this->pinjamanNotFound($id);
        }

        return $this->successResponse(
            data: new PeminjamanResource($pinjam),
            message: 'Detail peminjaman berhasil ditemukan.'
        );
    }

    /**
     * POST /api/v1/peminjamans (Bearer): transaksi ACID + lockForUpdate
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'buku_id'       => 'required|integer|exists:bukus,id',
            'nim_peminjam'  => 'required|string|min:6|max:15',
            'nama_peminjam' => 'required|string|min:3|max:150',
            'durasi_hari'   => 'nullable|integer|min:1|max:30',
        ]);

        try {
            return DB::transaction(function () use ($validated) {
                // Kunci baris buku: transaksi lain yang menyentuh buku ini harus mengantre
                $buku = Buku::where('id', $validated['buku_id'])->lockForUpdate()->first();

                $nim = strtoupper(trim($validated['nim_peminjam']));

                // 409: NIM yang sama masih meminjam buku yang sama
                $masihDipinjam = Peminjaman::where('buku_id', $buku->id)
                    ->where('nim_peminjam', $nim)
                    ->where('status', 'dipinjam')
                    ->exists();

                if ($masihDipinjam) {
                    return $this->problemResponse(
                        title: 'Duplicate Loan Conflict',
                        detail: 'Peminjam ini masih meminjam buku yang sama dan belum mengembalikannya.',
                        status: 409,
                        errorSlug: 'duplicate-loan'
                    );
                }

                // 422: stok habis
                if ($buku->stok < 1) {
                    return $this->problemResponse(
                        title: 'Book Out of Stock',
                        detail: "Stok buku '{$buku->judul}' sedang habis.",
                        status: 422,
                        errorSlug: 'stock-exhausted'
                    );
                }

                $durasi = (int) ($validated['durasi_hari'] ?? 7);

                $buku->decrement('stok');

                $pinjam = $buku->peminjamans()->create([
                    'kode_pinjam'         => 'PJM-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5)),
                    'nim_peminjam'        => $nim,
                    'nama_peminjam'       => $validated['nama_peminjam'],
                    'tanggal_pinjam'      => now()->toDateString(),
                    'tanggal_jatuh_tempo' => now()->addDays($durasi)->toDateString(),
                    'status'              => 'dipinjam',
                ]);

                $pinjam->load('buku');

                return $this->createdResponse(
                    data: new PeminjamanResource($pinjam),
                    locationUrl: url("/api/v1/peminjamans/{$pinjam->id}"),
                    message: 'Peminjaman berhasil dicatat secara atomik.'
                );
            }, 3);
        } catch (QueryException $e) {
            Log::error('[DB EXCEPTION]: ' . $e->getMessage());

            return $this->problemResponse(
                title: 'Internal Server Error',
                detail: 'Terjadi kesalahan database. Peminjaman dibatalkan.',
                status: 500,
                errorSlug: 'database-error'
            );
        }
    }

    /**
     * PATCH /api/v1/peminjamans/{id}/kembalikan (Bearer): transaksi ACID + lockForUpdate
     */
    public function kembalikan(string $id): JsonResponse
    {
        try {
            return DB::transaction(function () use ($id) {
                $pinjam = Peminjaman::where('id', $id)->lockForUpdate()->first();

                if (!$pinjam) {
                    return $this->pinjamanNotFound($id);
                }

                if ($pinjam->status === 'dikembalikan') {
                    return $this->problemResponse(
                        title: 'Already Returned Conflict',
                        detail: 'Buku pada peminjaman ini sudah dikembalikan sebelumnya.',
                        status: 409,
                        errorSlug: 'already-returned'
                    );
                }

                $buku = Buku::where('id', $pinjam->buku_id)->lockForUpdate()->first();

                $pinjam->update([
                    'status'          => 'dikembalikan',
                    'tanggal_kembali' => now()->toDateString(),
                ]);

                $buku->increment('stok');

                $pinjam->load('buku');

                return $this->successResponse(
                    data: new PeminjamanResource($pinjam),
                    message: 'Buku berhasil dikembalikan, stok diperbarui.'
                );
            }, 3);
        } catch (QueryException $e) {
            Log::error('[DB EXCEPTION]: ' . $e->getMessage());

            return $this->problemResponse(
                title: 'Internal Server Error',
                detail: 'Terjadi kesalahan database. Pengembalian dibatalkan.',
                status: 500,
                errorSlug: 'database-error'
            );
        }
    }
}