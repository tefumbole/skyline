<?php

namespace App\Http\Controllers;

use App\GalleryItem;
use App\Support\GalleryImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MediaBoardController extends Controller
{
    public function gallery()
    {
        return $this->show('gallery');
    }

    public function menu()
    {
        return $this->show('menu');
    }

    public function storeGallery(Request $request)
    {
        return $this->store($request, 'gallery');
    }

    public function storeMenu(Request $request)
    {
        return $this->store($request, 'menu');
    }

    public function destroy(Request $request, $id)
    {
        $this->authorizeAdmin();
        $board = $request->input('board') === 'menu' ? 'menu' : 'gallery';
        $item = GalleryItem::where('board', $board)->findOrFail($id);
        $item->delete();

        return redirect($this->path($board))->with('message', 'Image removed.');
    }

    protected function show($board)
    {
        $this->authorizeAdmin();

        return view('admin.image_board', [
            'board' => $board,
            'title' => $board === 'menu' ? 'Menu' : 'Gallery',
            'live' => $board === 'menu' ? url('/menu') : url('/gallery'),
            'items' => GalleryItem::where('board', $board)->where('type', 'image')->ordered()->get(),
        ]);
    }

    protected function store(Request $request, $board)
    {
        $this->authorizeAdmin();
        $request->validate([
            'images' => 'required|array|min:1|max:20',
            'images.*' => 'image|mimes:jpg,jpeg,png,gif,webp|max:8192',
        ]);

        $dir = public_path('images/'.$board);
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $sort = (int) GalleryItem::where('board', $board)->max('sort_order');
        foreach ($request->file('images', []) as $file) {
            if (! $file || ! $file->isValid()) {
                continue;
            }
            $stored = GalleryImage::storeUpload($file, $dir);
            if (! $stored) {
                $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
                $name = $board.'_'.time().'_'.bin2hex(random_bytes(4)).'.'.$ext;
                $file->move($dir, $name);
                $stored = ['file' => 'images/'.$board.'/'.$name, 'thumb' => null];
            }
            $sort++;
            GalleryItem::create([
                'type' => 'image',
                'board' => $board,
                'title' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'file_path' => $stored['file'],
                'thumb_path' => $stored['thumb'],
                'sort_order' => $sort,
                'is_published' => true,
            ]);
        }

        return redirect($this->path($board))->with('message', 'Images uploaded.');
    }

    protected function path($board)
    {
        return $board === 'menu' ? '/admin/menu' : '/admin/gallery';
    }

    protected function authorizeAdmin()
    {
        if (! Auth::check() || ! in_array((int) Auth::user()->role_id, [1, 2], true)) {
            abort(403, 'You are not allowed to upload these images.');
        }
    }
}
