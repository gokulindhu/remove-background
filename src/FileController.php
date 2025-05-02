<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Models\Candidate;
use Illuminate\Http\Request;

class FileController extends Controller
{

    public function index(Request $request)
    {
        $candidate_info = Candidate::find($request->input('id'));
        if ($candidate_info == null) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Invalid candidate information provided'], 400);
            }
            return back()->withInput()->withErrors($validation->messages());
        }

        return $this->remove_background($candidate_info);
    }

    public function remove_background($candidate_info)
    {

        try {
            $ext = $this->getUrlExtension($candidate_info->photo_copy_url);
            $name = date('Ymdhis') . '_profile_pic_' . $request->input('candidate_id') . '.' . $ext;
            $finalImagePath = storage_path('app/public/' . $name);
            // Run Python script
            $python = "XDG_CACHE_HOME=/tmp  /usr/bin/python3 ";
            $command = $python . storage_path('remove_bg.py') . " '" . $candidate_info->photo_copy_url . "' '" . $finalImagePath . "'";
            shell_exec($command . " 2>&1"); // Capture errors as well
            Storage::disk(config('filesystems.default'))->put($name, file_get_contents($finalImagePath), [
                'visibility' => 'public', // Optional: You can set the file to be publicly accessible
                'Content-Type' => 'application/pdf', // Explicitly set the content type to PDF
            ]);
            unlink($finalImagePath);
            $image_url = Storage::disk(config('filesystems.default'))->url($name);
            if ($request->expectsJson()) {
                return response()->json(['success' => 'Background removed successfully', 'image_url' => $image_url]);
            }
            return back()->withInput()->withErrors(['success' => 'Background removed successfully']);
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
            }
            return back()->withInput()->withErrors(['error' => 'An error occurred: ' . $e->getMessage()]);
        }
    }
}
