<?php

namespace App\Http\Controllers;

use App\Models\Gallery;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GalleryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('media.index', [
            'galleries' => Gallery::latest()->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('media.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (
            $request->gallery_type == 'image' ||
            $request->gallery_type == 'video'
        ) {
            $fileName = Str::slug($request->gallery_name, '_');
            $extension = $request->file('gallery_path')->extension();
            $fileContent = $request->file('gallery_path')->get();
            $chunkSize = 1000000;

            $totalChunks = ceil(strlen($fileContent) / $chunkSize);
            $destinationFolder = storage_path('app/public/gallery');

            // Memecah file menjadi bagian-bagian
            for ($i = 0; $i < $totalChunks; $i++) {
                // Mendapatkan potongan data
                $chunk = substr($fileContent, $i * $chunkSize, $chunkSize);

                // Menyimpan potongan data ke file tujuan
                $destinationFile = "{$destinationFolder}/part_{$i}.dat";
                File::put($destinationFile, $chunk);
            }

            // Ambil semua file dalam folder sumber
            $files = File::files($destinationFolder);

            // Urutkan file berdasarkan nama
            natsort($files);

            // Buka file tujuan untuk ditulis
            $combinedFile = storage_path(
                'app/public/gallery/' . $fileName . '.' . $extension
            );
            $destinationHandle = fopen($combinedFile, 'wb');

            // Gabungkan file-file menjadi satu file utuh
            foreach ($files as $file) {
                // Baca isi file dan tulis ke file tujuan
                $chunk = File::get($file);
                fwrite($destinationHandle, $chunk);
            }

            // Tutup handle file tujuan
            fclose($destinationHandle);

            // Hapus file-file sumber setelah digabungkan
            File::delete($files);

            Gallery::create([
                'gallery_name' => $request->gallery_name,
                'gallery_path' =>
                    'app/public/gallery/' . $fileName . '.' . $extension,
                'gallery_type' => $request->gallery_type,
            ]);
        } else {
            Gallery::create([
                'gallery_name' => $request->gallery_name,
                'gallery_path' => $request->link,
                'gallery_type' => $request->gallery_type,
            ]);
        }

        $request->session()->flash('success', 'Berhasil Menambah Gakeri');

        return response()->json([
            'message' => 'Berhasil menambahkan galeri!.',
            'redirect' => route('gallery.index'),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Gallery $gallery)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Gallery $gallery)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Gallery $gallery)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Gallery $gallery)
    {
        if ($gallery->gallery_path) {
            if (Storage::exists($gallery->gallery_path)) {
                Storage::delete($gallery->gallery_path);
            }
        }

        $gallery->delete();

        return redirect()
            ->route('gallery.index')
            ->with('success', 'Barhasil menghapus Galeri!');
    }
}
