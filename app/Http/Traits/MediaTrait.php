<?php


namespace App\Http\Traits ;

use Illuminate\Support\Facades\Storage;

trait MediaTrait
{
    public function upload_files($file,$location){

        try {
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs(
                "uploads/$location",
                $fileName,
                'public'
            );
            return Storage::url($filePath);
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to upload file.');
        }
    }
}
