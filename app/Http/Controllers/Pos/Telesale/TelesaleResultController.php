<?php

namespace App\Http\Controllers\Pos\Telesale;

use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Models\Pos\Telesale\TelesaleResult;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TelesaleResultController extends Controller
{
    public function index(Request $request)
    {
        $query = TelesaleResult::query()
            ->when($request->search, fn ($q, $search) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('sort_order')
            ->orderBy('id');

        // The dropdowns that consume this only want the enabled ones.
        if ($request->boolean('only_active')) {
            $query->where('status', true);
        }

        return $query->paginate($request->input('per_page', 10));
    }

    public function store(Request $request): JsonResponse
    {
        TelesaleResult::query()->create($this->validated($request));

        return successResponse('Result created successfully');
    }

    public function show(TelesaleResult $telesaleResult): TelesaleResult
    {
        return $telesaleResult;
    }

    public function update(Request $request, TelesaleResult $telesaleResult): JsonResponse
    {
        $telesaleResult->update($this->validated($request, $telesaleResult->id));

        return successResponse('Result updated successfully');
    }

    /**
     * @throws GeneralException
     */
    public function destroy(TelesaleResult $telesaleResult): JsonResponse
    {
        // Telesale rows null the result out on delete, which would silently
        // blank the column on old entries — refuse instead.
        if ($telesaleResult->telesales()->exists()) {
            throw new GeneralException('This result is already used in telesale entries. Disable it instead of deleting.');
        }

        $telesaleResult->delete();

        return successResponse('Result deleted successfully');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:191', Rule::unique('telesale_results', 'name')->ignore($ignoreId)],
            'color' => ['nullable', 'string', 'max:20'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'boolean'],
        ]);
    }
}
