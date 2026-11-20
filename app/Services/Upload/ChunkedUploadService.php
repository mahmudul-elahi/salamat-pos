<?php

namespace App\Services\Upload;

use App\Exceptions\GeneralException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Receives a file a slice at a time and reassembles it once the last slice
 * lands, so a big upload never has to fit inside one request — PHP's
 * upload_max_filesize / post_max_size and the web server's body limit only ever
 * see one chunk.
 *
 * Mirrors the wire format the media library already uses (chunk, chunkIndex,
 * totalChunks, fileId, fileName) so the frontend helper is shared too.
 */
class ChunkedUploadService
{
    /**
     * Stores this chunk and, when it completes the set, returns the absolute
     * path of the reassembled file. Returns null while chunks are still due.
     *
     * @param  array<int, string>  $allowedExtensions  checked against the final
     *         file name — a per-chunk mime check is meaningless, since a slice
     *         of a PDF is not a PDF.
     * @throws GeneralException
     */
    public function receive(Request $request, array $allowedExtensions, int $maxKilobytes): ?string
    {
        $request->validate([
            'chunk'       => ['required', 'file'],
            'chunkIndex'  => ['required', 'integer', 'min:0'],
            'totalChunks' => ['required', 'integer', 'min:1'],
            'fileId'      => ['required', 'string', 'max:191'],
            'fileName'    => ['required', 'string', 'max:191'],
        ]);

        // basename() strips any directory the client tried to smuggle in, and
        // the fileId is reduced to characters that are safe in a path.
        $fileName    = basename($request->input('fileName'));
        $fileId      = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $request->input('fileId'));
        $chunkIndex  = (int) $request->input('chunkIndex');
        $totalChunks = (int) $request->input('totalChunks');

        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($extension, $allowedExtensions, true)) {
            throw new GeneralException('This file type is not allowed. Allowed: ' . implode(', ', $allowedExtensions) . '.');
        }

        $directory = storage_path("app/chunks/{$fileId}");

        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new GeneralException('Could not start the upload. Please try again.');
        }

        $request->file('chunk')->move($directory, "chunk_{$chunkIndex}");

        if (count(glob("{$directory}/chunk_*")) < $totalChunks) {
            return null;
        }

        $mergedPath = $directory . '/' . Str::random(40) . '.' . $extension;
        $out        = fopen($mergedPath, 'wb');

        for ($index = 0; $index < $totalChunks; $index++) {
            $chunkPath = "{$directory}/chunk_{$index}";

            // A gap means a chunk never arrived; better to fail loudly than to
            // store a silently truncated file.
            if (!is_file($chunkPath)) {
                fclose($out);
                $this->discard($directory);

                throw new GeneralException('The upload was incomplete. Please try again.');
            }

            $in = fopen($chunkPath, 'rb');
            stream_copy_to_stream($in, $out);
            fclose($in);
            unlink($chunkPath);
        }

        fclose($out);

        if (filesize($mergedPath) > $maxKilobytes * 1024) {
            $this->discard($directory);

            throw new GeneralException('The file is too large. Maximum ' . round($maxKilobytes / 1024) . ' MB.');
        }

        return $mergedPath;
    }

    /**
     * Wraps the assembled file so it can be handed to Storage like any upload.
     * `test: true` skips PHP's is_uploaded_file() check, which this file — moved
     * and merged by us — would never pass.
     */
    public function toUploadedFile(string $path): UploadedFile
    {
        return new UploadedFile($path, basename($path), null, null, true);
    }

    public function discard(string $directory): void
    {
        foreach (glob(rtrim($directory, '/') . '/*') ?: [] as $file) {
            @unlink($file);
        }

        @rmdir($directory);
    }
}
