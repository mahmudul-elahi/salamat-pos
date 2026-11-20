<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pos\EntryJob;

class EntryJobController extends Controller
{
    public function index(Request $request) 
    {
        $query = EntryJob::query();
        if ($request->search) {
            $query->where('heading', 'like', '%' . $request->search . '%');
        }
        $data = $query->orderBy('id', 'desc')->paginate($request->pageSize ?? 10);
        return response()->json($data);
    }

    public function store(Request $request) 
    {
        $request->validate([
            'heading' => 'required',
            'slug' => 'required|unique:entry_jobs,slug',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
        
        $data = $request->except('image');
        
        if ($request->hasFile('image')) {
            $data['image'] = $this->uploadImage($request->file('image'));
        }
        
        EntryJob::create($data);
        return response()->json(['message' => 'Job created successfully']);
    }

    public function show($id)
    {
        return EntryJob::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $job = EntryJob::findOrFail($id);

        $request->validate([
            'heading' => 'required',
            'slug' => 'required|unique:entry_jobs,slug,' . $id,
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $request->except(['image', '_method']);

        if ($request->hasFile('image')) {
            // Delete old image if needed
            // if ($job->image) Storage::delete($job->image);
            $data['image'] = $this->uploadImage($request->file('image'));
        }

        $job->update($data);

        return response()->json(['message' => 'Job updated successfully']);
    }

    public function destroy($id) 
    {
        $job = EntryJob::findOrFail($id);
        $job->delete();
        return response()->json(['message' => 'Job deleted successfully']);
    }

    private function uploadImage($file)
    {
        $path = $file->store('entry-jobs', 'public');
        return '/storage/' . $path;
    }
}
