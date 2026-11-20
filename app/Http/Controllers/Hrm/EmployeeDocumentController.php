<?php

namespace App\Http\Controllers\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Hrm\EmployeeDocument;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EmployeeDocumentController extends Controller
{
    public function index(User $employee)
    {
        return $employee->documents()
            ->with('addedBy:id,name')
            ->latest('id')
            ->get()
            ->map(fn(EmployeeDocument $document) => [
                'id' => $document->id,
                'name' => $document->name,
                'file_url' => $document->file_url,
                'added_by' => $document->addedBy?->name,
                'created_at' => $document->created_at->format('d M, Y'),
            ]);
    }

    public function store(Request $request, User $employee)
    {
        $request->validate([
            'name'    => ['required', 'string', 'max:255'],
            'files'   => ['required', 'array', 'min:1'],
            'files.*' => ['required', 'file', 'max:20480'],
        ]);

        foreach ($request->file('files') as $file) {
            $path = $file->store('employee-documents');

            $employee->documents()->create([
                'name'       => $request->name,
                'file_path'  => $path,
                'added_by'   => auth()->id(),
            ]);
        }

        return successResponse('Documents uploaded successfully');
    }

    public function destroy(EmployeeDocument $document)
    {
        Storage::delete($document->file_path);
        $document->delete();

        return successResponse('Document deleted successfully');
    }
}
