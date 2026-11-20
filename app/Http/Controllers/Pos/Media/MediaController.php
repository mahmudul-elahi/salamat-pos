<?php

namespace App\Http\Controllers\Pos\Media;

use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Models\Ecommerce\MediaFolder\MediaFolder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaController extends Controller
{
    // public function allMedias()
    // {
    //     return Media::query()
    //         ->orderByDesc('id')
    //         ->paginate(request('per_page', 10))
    //         ->through(function ($media) {
    //             return [
    //                 'id' => $media->id,
    //                 'name' => $media->name,
    //                 'file_name' => $media->file_name,
    //                 'collection' => $media->collection_name,
    //                 'mime_type' => $media->mime_type,
    //                 'size' => $media->size,
    //                 'url' => $media->getFullUrl(),
    //                 'alt' => $media->getCustomProperty('alt_text', ''),
    //                 'created_at' => $media->created_at->format('Y-m-d H:i:s'),
    //             ];
    //         });
    // }
    public function allMedias(Request $request)
    {
        $folderId = $request->query('folder_id');
        $seacrh = $request->query('search');

        $query = Media::query()
            ->when($seacrh, function ($q) use ($seacrh) {
                $q->where('name', 'like', "%{$seacrh}%");
            })
            ->whereNotIn('collection_name', ['review_images', 'customer_profile_picture'])
            ->orderByDesc('id');

        if ($folderId) {
            $query->where('model_id', $folderId);
        }

        return $query->paginate(request('per_page', 24))
            ->through(fn ($media) => [
                'id' => $media->id,
                'name' => $media->name,
                'file_name' => $media->file_name,
                'collection' => $media->collection_name,
                'mime_type' => $media->mime_type,
                'size' => $media->size,
                'url' => $media->getFullUrl(),
                'alt' => $media->getCustomProperty('alt_text', ''),
                'created_at' => $media->created_at->format('Y-m-d H:i:s'),
            ]);
    }

    public function allFolders(Request $request)
    {
        $parentId = $request->query('parent_id');

        return MediaFolder::query()
            ->when(is_null($parentId), fn ($q) => $q->whereNull('parent_id'))
            ->when(! is_null($parentId), fn ($q) => $q->where('parent_id', $parentId))
            ->get();
    }

    // public function updateMedia(Request $request, $folderId = null)
    // {
    //     $request->validate([
    //         'files.*' => 'required|file|max:10240', // max 10MB per file
    //     ]);

    //     $folder = $folderId ? MediaFolder::query()->find($folderId) : MediaFolder::query()->updateOrCreate([
    //         'id' => 1,
    //     ], [
    //         'name' => 'root',
    //         'parent_id' => null
    //     ]);

    //     if ($request->hasFile('files')) {
    //         foreach ($request->file('files') as $file) {
    //             $folder->addMedia($file)->toMediaCollection('global_media');
    //         }
    //     }

    //     return successResponse('Image Upload has been successfully');
    // }
    public function updateMedia(Request $request, $folderId = null)
    {
        $request->validate([
            'files.*' => 'required|file|max:10240',
        ]);

        // Root folder fallback
        $folder = $folderId
            ? MediaFolder::findOrFail($folderId)
            : MediaFolder::firstOrCreate(
                ['id' => 1],
                ['name' => 'root', 'parent_id' => null]
            );

        foreach ($request->file('files', []) as $file) {
            $folder
                ->addMedia($file)
                ->toMediaCollection('global_media');
        }

        return successResponse('Image Upload has been successfully');
    }

    public function uploadMediaChunk(Request $request, $folderId = null)
    {
        $request->validate([
            'chunk' => 'required|file',
            'chunkIndex' => 'required|integer|min:0',
            'totalChunks' => 'required|integer|min:1',
            'fileId' => 'required|string',
            'fileName' => 'required|string',
        ]);

        $chunkIndex = (int) $request->input('chunkIndex');
        $totalChunks = (int) $request->input('totalChunks');
        $fileId = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $request->input('fileId'));
        $fileName = $request->input('fileName');

        // Temp directory for this specific file's chunks
        $tmpDir = storage_path("app/chunks/{$fileId}");
        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        // Save this chunk
        $request->file('chunk')->move($tmpDir, "chunk_{$chunkIndex}");

        // Check if all chunks have arrived
        $uploadedChunks = glob("{$tmpDir}/chunk_*");
        if (count($uploadedChunks) < $totalChunks) {
            // Not done yet
            return response()->json(['message' => 'Chunk received', 'done' => false]);
        }

        // All chunks received — merge them
        $mergedPath = "{$tmpDir}/{$fileName}";
        $out = fopen($mergedPath, 'wb');

        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = "{$tmpDir}/chunk_{$i}";
            $in = fopen($chunkPath, 'rb');
            stream_copy_to_stream($in, $out);
            fclose($in);
            unlink($chunkPath); // cleanup chunk
        }

        fclose($out);

        // Folder resolution (same as before)
        $folder = $folderId
            ? MediaFolder::findOrFail($folderId)
            : MediaFolder::firstOrCreate(
                ['id' => 1],
                ['name' => 'root', 'parent_id' => null]
            );

        // Add merged file to media library
        $folder
            ->addMedia($mergedPath)
            ->usingFileName($fileName)
            ->toMediaCollection('global_media');

        // Cleanup temp dir
        @rmdir($tmpDir);

        return successResponse('Image Upload has been successfully');
    }

    public function updateAltText(Request $request, Media $media)
    {
        $request->validate([
            'alt_text' => 'nullable|string|max:255',
        ]);

        $media->setCustomProperty('alt_text', $request->input('alt_text'));
        $media->save();

        return response()->json(['success' => true, 'alt_text' => $media->getCustomProperty('alt_text')]);
    }

    /**
     * @throws GeneralException
     */
    public function delete(Media $media)
    {
        try {
            // Get the disk and file path
            $disk = $media->disk;
            // Delete the file from disk manually
            if (Storage::disk($disk)->exists($media->id.'/'.$media->file_name)) {
                Storage::disk($disk)->delete($media->id.'/'.$media->file_name);
            }

            // Delete the DB record
            $media->delete();

            return successResponse('Media deleted has been successfully');
        } catch (\Exception $e) {

            if (app()->environment('production')) {
                throw new GeneralException('Media deleted has been failed', 500, $e);
            }
            throw new GeneralException($e->getMessage(), 500, $e);
        }
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);

        try {
            $mediaItems = Media::whereIn('id', $ids)->get();

            foreach ($mediaItems as $media) {
                $disk = $media->disk;
                if (Storage::disk($disk)->exists($media->id.'/'.$media->file_name)) {
                    Storage::disk($disk)->delete($media->id.'/'.$media->file_name);
                }
                $media->delete();
            }

            return response()->json(['message' => 'Media items deleted successfully.'], 200);
        } catch (\Exception $e) {
            if (app()->environment('production')) {
                return response()->json(['message' => 'Failed to delete media items.'], 500);
            }

            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function folderCreate(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:media_folders,id',
        ]);

        try {
            $folder = MediaFolder::query()->create($request->only('name', 'parent_id'));

            // Create folder in S3 (dummy file)
            $path = $this->getFolderPath($folder);
            Storage::disk(getenv('FILESYSTEM_DISK'))->put($path.'/.gitkeep', '');

            return response()->json(['success' => true, 'folder' => $folder]);
        } catch (\Exception $exception) {
            throw new GeneralException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    private function getFolderPath(MediaFolder $folder)
    {
        $path = $folder->name;
        $parent = $folder->parent_id ? MediaFolder::find($folder->parent_id) : null;

        while ($parent) {
            $path = $parent->name.'/'.$path;
            $parent = $parent->parent_id ? MediaFolder::find($parent->parent_id) : null;
        }

        return $path;
    }
}
