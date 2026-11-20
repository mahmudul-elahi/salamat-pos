<?php

namespace App\Http\Controllers\Ecommerce\Emi;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ecommerce\Emi\EmiBankRequest;
use App\Models\Ecommerce\Emi\EmiBank;
use Illuminate\Http\Request;

class EmiBankController extends Controller
{
    public function index(Request $request)
    {
        return EmiBank::query()
            ->with('tenures')
            ->when($request->search, fn ($q) => $q->where('bank_name', 'like', '%'.$request->search.'%'))
            ->when(isset($request->gw_key), fn ($q) => $q->where('gw_key', $request->gw_key))
            ->orderBy('bank_name')
            ->paginate(request('per_page', 10));
    }

    public function store(EmiBankRequest $request)
    {
        $bank = EmiBank::create($request->only(['bank_name', 'gw_key', 'logo', 'is_active']));
        $bank->tenures()->createMany($request->input('tenures'));

        return successResponse('EMI bank created successfully');
    }

    public function show(EmiBank $emiBank)
    {
        return $emiBank->load('tenures');
    }

    public function update(EmiBankRequest $request, EmiBank $emiBank)
    {
        $emiBank->update($request->only(['bank_name', 'gw_key', 'logo', 'is_active']));

        // sync tenures: delete old, insert new
        $emiBank->tenures()->delete();
        $emiBank->tenures()->createMany($request->input('tenures'));

        return successResponse('EMI bank updated successfully');
    }

    public function destroy(EmiBank $emiBank)
    {
        $emiBank->tenures()->delete();
        $emiBank->delete();

        return successResponse('EMI bank deleted successfully');
    }
}
