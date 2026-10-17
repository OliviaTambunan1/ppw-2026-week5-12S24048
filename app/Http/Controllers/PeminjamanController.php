<?php

namespace App\Http\Controllers;

use App\Models\Buku;
use App\Models\Peminjaman;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PeminjamanController extends Controller
{
    public function index()
    {
        $peminjamans = Peminjaman::with('buku')->latest()->paginate(10);
        return view('peminjaman.index', compact('peminjamans'));
    }

    public function pinjam(Request $request, int $id)
    {
        $validated = $request->validate([
            'nim_peminjam'  => 'required|string|min:6|max:15',
            'nama_peminjam' => 'required|string|min:3|max:150',
        ]);

        try {
            DB::transaction(function () use ($validated, $id) {
                // Kunci baris buku: transaksi lain yang menyentuh buku ini harus mengantre
                $buku = Buku::where('id', $id)->lockForUpdate()->firstOrFail();

                if ($buku->stok < 1) {
                    throw new \RuntimeException("Stok buku \"{$buku->judul}\" habis!");
                }

                $nim = strtoupper(trim($validated['nim_peminjam']));

                $masihDipinjam = Peminjaman::where('buku_id', $buku->id)
                    ->where('nim_peminjam', $nim)
                    ->where('status', 'dipinjam')
                    ->exists();

                if ($masihDipinjam) {
                    throw new \RuntimeException("NIM {$nim} masih meminjam buku ini dan belum mengembalikannya.");
                }

                $buku->decrement('stok');

                $buku->peminjamans()->create([
                    'kode_pinjam'         => 'PJM-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5)),
                    'nim_peminjam'        => $nim,
                    'nama_peminjam'       => $validated['nama_peminjam'],
                    'tanggal_pinjam'      => now()->toDateString(),
                    'tanggal_jatuh_tempo' => now()->addDays(7)->toDateString(),
                    'status'              => 'dipinjam',
                ]);
            }, 3);

            return redirect()->route('peminjaman.index')
                ->with('success', 'Peminjaman berhasil dicatat secara atomik!');
        } catch (QueryException $e) {
            Log::error('[DB EXCEPTION]: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Terjadi kesalahan database. Peminjaman dibatalkan.');
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function kembali(int $id)
    {
        try {
            DB::transaction(function () use ($id) {
                $pinjam = Peminjaman::where('id', $id)->lockForUpdate()->firstOrFail();

                if ($pinjam->status === 'dikembalikan') {
                    throw new \RuntimeException('Buku ini sudah dikembalikan sebelumnya.');
                }

                $buku = Buku::where('id', $pinjam->buku_id)->lockForUpdate()->firstOrFail();

                $pinjam->update([
                    'status'          => 'dikembalikan',
                    'tanggal_kembali' => now()->toDateString(),
                ]);

                $buku->increment('stok');
            }, 3);

            return back()->with('success', 'Buku berhasil dikembalikan, stok diperbarui.');
        } catch (QueryException $e) {
            Log::error('[DB EXCEPTION]: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan database. Pengembalian dibatalkan.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}