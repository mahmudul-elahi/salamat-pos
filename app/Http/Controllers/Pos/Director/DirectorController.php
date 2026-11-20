<?php

namespace App\Http\Controllers\Pos\Director;

use App\Exceptions\GeneralException;
use App\Filters\Pos\Report\DepositReportFilter;
use App\Filters\Pos\Report\IncomeFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\Director\DirectorRequest;
use App\Jobs\Report\GenerateDirectorProfilePdfJob;
use App\Models\Pos\Income\Income;
use App\Models\Pos\Investment\Investment;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class DirectorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $directors = User::query()
            ->where('role', 'director')
            ->get();

        $directorIds = $directors->pluck('id');

        $deposits = Investment::query()
            ->whereIn('user_id', $directorIds)
            ->groupBy('user_id')
            ->selectRaw('user_id, SUM(amount) as total')
            ->pluck('total', 'user_id');

        $withdraws = Income::query()
            ->whereIn('user_id', $directorIds)
            ->groupBy('user_id')
            ->selectRaw('user_id, SUM(amount) as total')
            ->pluck('total', 'user_id');

        return $directors->map(function ($director) use ($deposits, $withdraws) {
            $totalDeposit  = (float) ($deposits[$director->id]  ?? 0);
            $totalWithdraw = (float) ($withdraws[$director->id] ?? 0);
            return [
                ...$director->toArray(),
                'total_deposit'  => $totalDeposit,
                'total_withdraw' => $totalWithdraw,
                'total_balance'  => $totalDeposit - $totalWithdraw,
            ];
        });
    }

    /**
     * Store a newly created resource in storage.
     * @throws GeneralException|Throwable
     */
    public function store(DirectorRequest $request)
    {
        try {
            DB::beginTransaction();

            $user = User::query()->create(array_merge($request->validated(), [
                'role' => 'director'
            ]));

            $user->tenants()->sync($request->tenant_id);

            DB::commit();
            return successResponse('Director created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            if (app()->environment('production')) {
                throw new GeneralException('Director created failed', 500);
            }
            throw new GeneralException($e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(User $director)
    {
        $director->load('tenant');
        return [
            ...$director->toArray(),
            'tenant_id' => $director->tenant?->id
        ];
    }

    /**
     * Update the specified resource in storage.
     * @throws Throwable
     */
    public function update(DirectorRequest $request, User $director)
    {
        try {
            DB::beginTransaction();

            $director->update(array_merge($request->validated(), [
                'role' => 'director'
            ]));

            $director->tenants()->sync($request->tenant_id);

            DB::commit();
            return successResponse('Director created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            if (app()->environment('production')) {
                throw new GeneralException('Director created failed', 500);
            }
            throw new GeneralException($e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * @throws GeneralException|Throwable
     */
    public function destroy(User $director)
    {
        try {
            DB::beginTransaction();
            $director->tenants()->detach();
            $director->delete();
            DB::commit();
            return successResponse('Director deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            if (app()->environment('production')) {
                throw new GeneralException('Director deleted failed', 500);
            }
            throw new GeneralException($e->getMessage(), 500);
        }
    }

    public function statistics(User $director)
    {
        $totalDeposit = Investment::query()
            ->where('user_id', $director->id)
            ->sum('amount');

        $totalWithdraw = Income::query()
            ->where('user_id', $director->id)
            ->sum('amount');

        return [
            'total_deposit' => $totalDeposit,
            'total_withdraw' => $totalWithdraw,
            'total_balance' => $totalDeposit - $totalWithdraw,
        ];
    }


    public function deposit(User $director)
    {
        $deposits = Investment::query()
            ->filters(new DepositReportFilter())
            ->where('user_id', $director->id)
            ->with('tenant')
            ->latest()
            ->paginate(request('per_page', 10));

        return [
            ...$deposits->toArray(),
            'grand_total' => [
                'amount' => $deposits->sum('amount')
            ]
        ];
    }

    public function withdrawals(User $director)
    {
        $withdrawals = Income::query()
            ->filters(new IncomeFilter())
            ->where('user_id', $director->id)
            ->with('tenant')
            ->latest()
            ->paginate(request('per_page', 10));

        return [
            ...$withdrawals->toArray(),
            'grand_total' => [
                'amount' => $withdrawals->sum('amount')
            ]
        ];
    }
    public function shopBalances(User $director)
    {
        $shops = $director->tenants()->pluck('name', 'id');

        $shopIds = $shops->keys();

        $deposits = Investment::query()
            ->where('user_id', $director->id)
            ->whereIn('tenant_id', $shopIds)
            ->groupBy('tenant_id')
            ->selectRaw('tenant_id, SUM(amount) as total')
            ->pluck('total', 'tenant_id');

        $withdraws = Income::query()
            ->where('user_id', $director->id)
            ->whereIn('tenant_id', $shopIds)
            ->groupBy('tenant_id')
            ->selectRaw('tenant_id, SUM(amount) as total')
            ->pluck('total', 'tenant_id');

        return $shops->map(function ($name, $shopId) use ($deposits, $withdraws) {
            $deposit  = $deposits->get($shopId, 0);
            $withdraw = $withdraws->get($shopId, 0);

            return [
                'shop_id'        => $shopId,
                'shop_name'      => $name,
                'total_deposit'  => $deposit,
                'total_withdraw' => $withdraw,
                'balance'        => $deposit - $withdraw,
            ];
        })->values()->all();
    }

    public function generatePdf(User $director)
    {
        $token = (string) Str::uuid();

        Cache::put(GenerateDirectorProfilePdfJob::cacheKey($token), ['status' => 'queued'], now()->addHour());

        GenerateDirectorProfilePdfJob::dispatch($token, $director->id)->onQueue('report-generate');

        return response()->json(['token' => $token]);
    }

    public function generateShopPdf(User $director, int $tenant)
    {
        abort_unless($director->tenants()->where('tenants.id', $tenant)->exists(), 404);

        $token = (string) Str::uuid();

        Cache::put(GenerateDirectorProfilePdfJob::cacheKey($token), ['status' => 'queued'], now()->addHour());

        GenerateDirectorProfilePdfJob::dispatch($token, $director->id, $tenant)->onQueue('report-generate');

        return response()->json(['token' => $token]);
    }

    public function pdfStatus(string $token)
    {
        $status = Cache::get(GenerateDirectorProfilePdfJob::cacheKey($token));

        if (!$status) {
            return response()->json(['status' => 'not_found'], 404);
        }

        return response()->json($status);
    }

    public function pdfCleanup(string $token)
    {
        if (Cache::has(GenerateDirectorProfilePdfJob::cacheKey($token))) {
            Storage::disk('public')->delete("director-profile-reports/{$token}.pdf");
            Cache::forget(GenerateDirectorProfilePdfJob::cacheKey($token));
        }

        return response()->json(['deleted' => true]);
    }
}
