<?php

namespace App\Imports;

use App\Helpers\Traits\ImportFailedTrait;
use Illuminate\Support\Arr;
use Maatwebsite\Excel\HeadingRowImport;
use PhpOffice\PhpSpreadsheet\Exception;

trait ImportHeadingValidation
{
    use ImportFailedTrait;


    private function getHeadings($file): array
    {
        return (new HeadingRowImport())->toArray($file);
    }

    private function getMissingFields($import, $headings): array
    {
        return array_diff($import->requiredHeading, Arr::flatten($headings));
    }

    private function handleMissingFields($missingField): \Illuminate\Http\JsonResponse
    {
        $arrayValues = array_values($missingField);
        return response()->json([
            'status' => false,
            'errors' => [
                'missing_field' => 'Missing field',
                'missing_fields' => $arrayValues
            ],

        ], 422);
    }

    /**
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    private function handleImportFailures($file, $failures): array
    {
        return $this->importFailed($file, $failures);
    }

    private function partiallyImportedResponse($stat): array
    {
        return [
            'status' => 200,
            'message' => 'Partially imported',
            'stat' => $stat
        ];
    }
}
