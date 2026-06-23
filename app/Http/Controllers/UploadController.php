<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class UploadController extends Controller
{
    public function upload(Request $request)
    {
        $this->authenticateAnyGuard();

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

    private function authenticateAnyGuard(): void
    {
        foreach (['api', 'doctor', 'patient'] as $guard) {
            try {
                $user = JWTAuth::setToken(JWTAuth::getToken())->authenticate();

                if ($user) {
                    auth($guard)->setUser($user);
                    return;
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        abort(401, 'Unauthorized.');
    }
}