<?php

namespace App\Http\Controllers\Pos\Chat;

use App\Http\Controllers\Controller;
use App\Models\Pos\Chat\ChatLabel;
use Illuminate\Http\Request;

class ChatLabelController extends Controller
{
    public function index()
    {
        return ChatLabel::query()->orderBy('name')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:chat_labels,name'],
            'color' => ['nullable', 'string', 'max:20'],
        ]);

        $label = ChatLabel::query()->create($data);

        return successResponse('Label created successfully', $label);
    }
}
