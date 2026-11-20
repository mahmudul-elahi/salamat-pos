<?php

namespace App\Http\Controllers\Ecommerce\Career;

use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ecommerce\Career\CareerRequest;
use App\Models\Ecommerce\Career\Career;
use App\Models\Ecommerce\Career\CareerApplie;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CareerController extends Controller
{
    public function index(Request $request)
    {
        return Career::query()
            ->when($request->search, fn ($q) => $q->where('heading', 'like', '%'.$request->search.'%'))
            ->orderByDesc('id')
            ->paginate($request->input('per_page', 10));
    }

    public function store(CareerRequest $request): JsonResponse
    {
        Career::query()->create($request->validated());

        return successResponse('Career created successfully');
    }

    public function show(Career $career): Career
    {
        return $career;
    }

    public function update(CareerRequest $request, Career $career): JsonResponse
    {
        $career->update($request->validated());

        return successResponse('Career updated successfully');
    }

    public function destroy(Career $career): JsonResponse
    {
        $career->delete();

        return successResponse('Career deleted successfully');
    }

    public function updateStatus(Request $request, Career $career)
    {
        $request->validate([
            'status' => ['required', 'in:1,0'],
        ]);

        if ($request->status == $career->status) {
            throw new GeneralException('Career status is already in use');
        }

        $career->update([
            'status' => $request->status,
        ]);

        return successResponse('Career status updated successfully');
    }

    public function applicants(Request $request)
    {
        return CareerApplie::query()
            ->with('career:id,heading')
            // The column collation is case-insensitive, so 'male' matches the
            // stored 'Male' either way.
            ->when($request->gender, fn ($q, $gender) => $q->where('gender', $gender))
            ->when($request->career_id, fn ($q, $careerId) => $q->where('career_id', $careerId))
            ->orderByDesc('id')
            ->paginate($request->input('per_page', 10));
    }

    public function showApplicant(CareerApplie $applicant)
    {
        return $applicant->load('career:id,heading');
    }
}
