<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:5120',
        ]);

        $file = $request->file('file');
        $filename = Str::random(20) . '_' . $file->getClientOriginalName();
        $path = $file->storeAs('temp', $filename, 'document');

        return response()->json([
            'success' => true,
            'path' => $path,
            'url' => route('file.read', ['path' => $path]),
            'filename' => $file->getClientOriginalName(),
            'field_value' => $path,
        ]);
    }
}
