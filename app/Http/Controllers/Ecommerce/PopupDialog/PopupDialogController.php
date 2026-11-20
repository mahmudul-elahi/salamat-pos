<?php

namespace App\Http\Controllers\Ecommerce\PopupDialog;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\PopupDialog\PopupDialog;
use Illuminate\Http\Request;

class PopupDialogController extends Controller
{
    public function index()
    {
        return PopupDialog::query()
            ->orderByDesc('id')
            ->paginate(request('per_page', 10));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'image' => ['required', 'string'],
            'mobile_image' => ['nullable', 'string'],
            'url' => ['nullable', 'string', 'max:2048'],
            'status' => ['required', 'boolean'],
        ]);

        if (! empty($validated['status'])) {
            PopupDialog::query()->update(['status' => false]);
        }

        PopupDialog::query()->create($validated);

        return successResponse('Popup dialog created successfully');
    }

    public function show(PopupDialog $popupDialog)
    {
        return $popupDialog;
    }

    public function update(Request $request, PopupDialog $popupDialog)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'image' => ['sometimes', 'nullable', 'string'],
            'mobile_image' => ['sometimes', 'nullable', 'string'],
            'url' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'status' => ['sometimes', 'boolean'],
        ]);

        if (! empty($validated['status'])) {
            PopupDialog::query()->where('id', '!=', $popupDialog->id)->update(['status' => false]);
        }

        $popupDialog->update($validated);

        return successResponse('Popup dialog updated successfully');
    }

    public function destroy(PopupDialog $popupDialog)
    {
        $popupDialog->delete();

        return successResponse('Popup dialog deleted successfully');
    }
}
