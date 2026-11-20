<?php

namespace App\Http\Controllers\Ecommerce\OurTeam;

use App\Http\Controllers\Controller;
use App\Http\Requests\Landlord\Ecommerce\OurTeam\OurTeamRequest;
use App\Models\Ecommerce\OurTeam\OurTeam;

class OurTeamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return OurTeam::query()
            ->orderByDesc('id')
            ->paginate(request('per_page', 10));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(OurTeamRequest $request): \Illuminate\Http\JsonResponse
    {
        OurTeam::query()->create($request->validated());

        return successResponse('Our team member created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(OurTeam $ourTeam): OurTeam
    {
        return $ourTeam->load('tenant:id,name');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(OurTeamRequest $request, OurTeam $ourTeam): \Illuminate\Http\JsonResponse
    {
        $ourTeam->update($request->validated());

        return successResponse('Our team member updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(OurTeam $ourTeam): \Illuminate\Http\JsonResponse
    {
        $ourTeam->delete();

        return successResponse('Our team member deleted successfully');
    }
}
