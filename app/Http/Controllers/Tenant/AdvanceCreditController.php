<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Balance\AdvanceCredit;
use Illuminate\Http\Request;

class AdvanceCreditController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'contact_id' => 'required|exists:contacts,id',
            'date' => 'required|date',
            'note' => 'nullable|string|max:500',
            'amount' => 'required|numeric|min:0.01',
            'type' => 'required|in:receivable,payable',
        ]);

        AdvanceCredit::create($request->all());

        return successResponse('Advance credit recorded successfully.');
    }
}
