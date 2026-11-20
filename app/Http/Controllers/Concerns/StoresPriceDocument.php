<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Pos\Contact\Contact;
use App\Services\Upload\ChunkedUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Shared by the supplier, customer and online-contact controllers — all three
 * keep the file on the same `contacts.price_document` column and differ only in
 * the folder it lands in and the permission guarding the route.
 */
trait StoresPriceDocument
{
    /** `txt` is allowed because spreadsheets often export CSVs as text/plain. */
    private array $priceDocumentExtensions = ['pdf', 'xls', 'xlsx', 'csv', 'txt', 'png', 'jpg', 'jpeg'];

    /** Chunking lifts the old 10 MB single-request cap. */
    private int $priceDocumentMaxKilobytes = 102400;

    /**
     * Takes one slice of the upload. Answers `done: false` while slices are
     * still due, and only writes to the contact once the whole file is here.
     */
    private function receivePriceDocumentChunk(
        Request $request,
        Contact $contact,
        string $folder,
        ChunkedUploadService $chunks
    ): \Illuminate\Http\JsonResponse {
        $mergedPath = $chunks->receive($request, $this->priceDocumentExtensions, $this->priceDocumentMaxKilobytes);

        if (!$mergedPath) {
            return response()->json(['message' => 'Chunk received', 'done' => false]);
        }

        $previous = $contact->price_document;

        try {
            // putFileAs, not putFile: putFile names the file from the *guessed*
            // extension, which a merged file has no reliable mime for — it was
            // storing "abc123" with no .pdf at all. The merged path already
            // carries a random name plus the real extension, so reuse it.
            //
            // No disk named: the file lands wherever FILESYSTEM_DISK points.
            $path = Storage::putFileAs($folder, $chunks->toUploadedFile($mergedPath), basename($mergedPath));
        } finally {
            // The temp directory must go whether the store succeeded or threw.
            $chunks->discard(dirname($mergedPath));
        }

        $contact->update(['price_document' => $path]);

        if ($previous && $previous !== $path) {
            Storage::delete($previous);
        }

        // `done` stays at the top level, matching the "Chunk received" replies
        // above. successResponse() would nest it under `result`, where the
        // caller isn't looking — which left the upload finishing server-side
        // while the modal never closed and the table never reloaded.
        return response()->json([
            'message' => 'Price document uploaded successfully',
            'done'    => true,
            'result'  => [
                'price_document'     => $path,
                'price_document_url' => $contact->fresh()->price_document_url,
            ],
        ]);
    }
}
