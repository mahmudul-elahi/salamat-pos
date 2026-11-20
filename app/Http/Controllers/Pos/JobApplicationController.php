<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Pos\JobApplication;
use Illuminate\Http\Request;

class JobApplicationController extends Controller
{
    public function index(Request $request)
    {
        $query = JobApplication::query()->with('entryJob');

        if ($request->entry_job_id) {
            $query->where('entry_job_id', $request->entry_job_id);
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('email', 'like', '%'.$request->search.'%');
            });
        }

        $data = $query->orderBy('id', 'desc')->paginate($request->pageSize ?? 10);

        return response()->json($data);
    }

    public function show($id)
    {
        return JobApplication::with('entryJob')->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $application = JobApplication::findOrFail($id);
        $request->validate([
            'status' => 'required|in:pending,reviewed,hired,rejected',
        ]);
        $application->update(['status' => $request->status]);

        return response()->json(['message' => 'Status updated successfully']);
    }

    public function destroy($id)
    {
        $application = JobApplication::findOrFail($id);
        $application->delete();

        return response()->json(['message' => 'Application deleted successfully']);
    }
}
