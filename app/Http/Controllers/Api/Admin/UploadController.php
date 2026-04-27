<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|image|max:4096', // 4MB
        ]);

        $path = $request->file('file')->store('uploads', 'public');

        return response()->json([
            'url' => Storage::disk('public')->url($path),
            'path' => $path,
        ]);
    }

    public function destroy(Request $request)
    {
        $request->validate(['path' => 'required|string']);

        $path = $request->path;

        // Only allow deleting files inside uploads/
        if (str_starts_with($path, 'uploads/')) {
            Storage::disk('public')->delete($path);
        }

        return response()->json(null, 204);
    }
}
