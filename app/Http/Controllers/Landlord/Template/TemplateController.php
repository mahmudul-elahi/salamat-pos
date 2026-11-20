<?php

namespace App\Http\Controllers\Landlord\Template;

use App\Http\Controllers\Controller;
use App\Http\Requests\Template\TemplateRequest;
use App\Models\Template\Template;
use App\Models\Template\TemplateType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;

class TemplateController extends Controller
{
    public function index(): Collection
    {
        return TemplateType::query()
            ->get()
            ->groupBy('group_name');
    }

    public function showTemplate($typeId)
    {
        return Template::query()
            ->with('templateType:id,name')
            ->where('template_type_id', $typeId)
            ->first();
    }

    public function update(TemplateRequest $request, Template $template): JsonResponse
    {

        $template->update([
            'subject' => $request->subject,
            'custom_content' => $request->description,
        ]);

        return successResponse('Template updated successfully');
    }
}
