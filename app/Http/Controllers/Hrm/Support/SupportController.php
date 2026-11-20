<?php

namespace App\Http\Controllers\Hrm\Support;

use App\Filters\Landlord\Common\NameFilter;
use App\Http\Controllers\Controller;
use App\Models\Hrm\LeaveType;
use App\Models\User;

class SupportController extends Controller
{
    /**
     * Permission-free leave type list for dropdowns (e.g. the leave request
     * form) — any authenticated employee can request their own leave
     * without needing the leave-types management permission. Paginated to
     * match the response shape the advance-search-select input expects.
     */
    public function leaveTypes()
    {
        return LeaveType::query()
            ->where('enable', true)
            ->orderBy('name')
            ->select(['id', 'name'])
            ->paginate(request('per_page', 10));
    }

    /**
     * Active-employee list for dropdowns (e.g. the asset assignment form) —
     * deliberately excludes inactive employees, unlike the generic
     * /selected/employees lookup, so an asset can't be assigned to someone
     * who's no longer active.
     */
    public function employees()
    {
        return User::query()
            ->filters(new NameFilter)
            ->where('is_employee', true)
            ->where('status', 'active')
            ->orderBy('name')
            ->select(['id', 'name'])
            ->paginate(request('per_page', 10));
    }
}
