<?php

namespace App\Http\Controllers\Pos\Widget;

use Illuminate\Http\Request;
use App\Models\Pos\Widget\Widget;
use App\Http\Controllers\Controller;

class WidgetController extends Controller
{
    public function index(): \Illuminate\Http\JsonResponse
    {
        $type = request('type');
        $tenantId = request('tenant_id');

        if (!$type) {
            return errorResponse("You must provide a type");
        }

        $query = Widget::query()->where('type', $type);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        } else {
            // Optional: If no tenant_id, you might want global widgets or those with tenant_id = null
            // For now, let's assume global means null tenant_id if we want that separation
            // But existing data has NULL tenant_id probably.
            // If we don't apply filter, it returns everything of that type.
            // Let's filter by NULL tenant_id if not provided, to keep backward compatibility or separation?
            // Actually, if tenant_id is NOT provided, it should probably return widgets where tenant_id is NULL (Landlord default).
            $query->whereNull('tenant_id');
        }

        $widgets = $query->get();

        return successResponse("Widget featch successfully", $widgets);
    }

    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        // Check if exists to prevent duplicates
        $exists = Widget::where('type', $request->type)
            ->where('tenant_id', $request->tenant_id)
            ->first();

        if ($exists) {
            $exists->update(['value' => $request->value]);
            return successResponse("Policy updated successfully", $exists);
        }

        $widget = Widget::create([
            'type' => $request->type,
            'value' => $request->value,
            'tenant_id' => $request->tenant_id,
            'name' => ucwords(str_replace('-', ' ', $request->type))
        ]);

        return successResponse("Policy created successfully", $widget);
    }

    public function update(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        $widget = Widget::find($id);

        if (!$widget) {
            return errorResponse("Widget not found");
        }

        $widget->update([
            'value' => $request->value
        ]);

        return successResponse("Policy updated successfully", $widget);
    }
}
