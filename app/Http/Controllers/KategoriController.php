<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KategoriController extends Controller
{
    private function rules(?Kategori $kategori = null): array
    {
        return [
            'kode_kategori' => ['required', 'string', 'max:10',
                                Rule::unique('kategoris', 'kode_kategori')->ignore($kategori?->id)],
            'nama_kategori' => ['required', 'string', 'min:3', 'max:100'],
        ];
    }

    public function index()
    {
        $kategoris = Kategori::withCount('bukus')->latest()->paginate(10);
        return view('kategori.index', compact('kategoris'));
    }

    public function create()
    {
        return view('kategori.create');
    }

    public function store(Request $request)
    {
        Kategori::create($request->validate($this->rules()));

        return redirect()->route('kategori.index')
            ->with('success', 'Kategori berhasil ditambahkan!');
    }

    public function show(Kategori $kategori)
    {
        $bukus = $kategori->bukus()->latest()->paginate(10);
        return view('kategori.show', compact('kategori', 'bukus'));
    }

    public function edit(Kategori $kategori)
    {
        return view('kategori.edit', compact('kategori'));
    }

    public function update(Request $request, Kategori $kategori)
    {
        $kategori->update($request->validate($this->rules($kategori)));

        return redirect()->route('kategori.index')
            ->with('success', 'Kategori berhasil diperbarui!');
    }

    public function destroy(Kategori $kategori)
    {
        $kategori->delete();

        return redirect()->route('kategori.index')
            ->with('success', 'Kategori dan seluruh bukunya berhasil dihapus!');
    }
}