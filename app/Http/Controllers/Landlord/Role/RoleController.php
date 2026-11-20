<?php

namespace App\Http\Controllers\Landlord\Role;

use App\Filters\Common\Role\RoleFilter;
use App\Filters\Common\User\UserFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Administrator\Role\RoleRequest;
use App\Models\Permission\Permission;
use App\Models\Role\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

class RoleController extends Controller
{
    public function __construct(protected RoleFilter $filter) {}

    public function index(): object
    {
        return Role::query()
            ->filters($this->filter)
            ->withCount('users as total_users', 'permissions as total_permissions')
            ->orderByDesc('id')
            ->paginate(request('per_page', 10));
    }

    public function store(RoleRequest $request): JsonResponse
    {
        $role = Role::query()->create($request->only('name'));

        if ($request->get('permissions')) {
            $role->permissions()->sync($request->get('permissions'));
        }

        return successResponse('Role created successfully');
    }

    public function show(Role $role): Role
    {
        return $role->load('permissions', 'users');
    }

    public function update(RoleRequest $request, Role $role)
    {
        $role->update($request->validated());

        //        if (!($role->is_admin || $role->is_default)) {
        $permission = $request->get('permissions');
        if (empty($permission)) {
            $role->permissions()->detach();
        }
        $role->permissions()->sync($permission);
        //        }

        return successResponse('Role updated successfully');
    }

    public function destroy(Role $role): JsonResponse
    {
        if ($role->is_admin || $role->is_default) {
            return errorResponse('Action not allowed');
        }
        $role->delete();

        return successResponse('Role deleted successfully');
    }

    /**
     * Groups are returned in the same order they appear in the sidebar menu,
     * so admins can find a module's permissions by matching what they see
     * in the navigation. Keep this in sync with database/seeders/Permission/PermissionSeeder.php.
     */
    private const array GROUP_ORDER = [
        'dashboards',
        'products', 'pre_owneds_products', 'categories', 'brands', 'pages', 'attributes',
        'specification_groups', 'product_specifications', 'products_care',
        'purchases', 'purchase_returns',
        'sales', 'sale_returns',
        'couriers',
        'stock_transfers', 'cash_transfers',
        'customers', 'online_contacts', 'suppliers',
        'payments', 'payment_records',
        'expenses', 'expense_categories',
        'incomes', 'income_categories',
        'investments', 'investment_categories',
        'dealers', 'retailers', 'b2b', 'franchises', 'partners',
        'seo', 'orders',
        'reels_videos', 'elites', 'popup_dialogs', 'offers', 'medias', 'coupons', 'news',
        'blogs', 'sliders', 'gift_products', 'warranties', 'product_verifies', 'our_teams',
        'careers',
        'faq_categories', 'faqs',
        'outlets',
        'hrm_employees', 'hrm_designations', 'hrm_employee_statuses', 'hrm_departments',
        'hrm_attendance', 'hrm_attendances',
        'hrm_leaves',
        'hrm_payrun', 'payroll',
        'hrm_holidays', 'hrm_events', 'hrm_announcements',
        'ledgers',
        'reports', 'report_downloads',
        'closing_statements',
        'users', 'roles',
        'settings', 'payment_methods', 'shops', 'ecommerces',
    ];

    public function permission(): Collection
    {
        $grouped = Permission::query()
            ->select('id', 'name', 'label', 'group_name')
            ->get()
            ->groupBy('group_name');

        $ordered = collect(self::GROUP_ORDER)
            ->filter(fn ($groupName) => $grouped->has($groupName))
            ->mapWithKeys(fn ($groupName) => [$groupName => $grouped->get($groupName)]);

        // Any group not yet added to GROUP_ORDER still shows up, appended at the end.
        return $ordered->union($grouped);
    }

    public function roleWithoutUser(): RoleWithoutUserResourceCollection
    {
        $existingUserIds = request()->has('existing')
            ? explode(',', request()->input('existing'))
            : [];

        $users = User::query()
            ->filters(new UserFilter)
            ->where('id', '<>', auth()->id())
            ->when(function (Builder $query) {
                $query->whereHas('userProfile', function (Builder $q) {
                    $q->whereNotIn('status', ['invited', 'inactive']);
                });
            })
            ->where('is_admin', 0)
            ->whereNotIn('id', $existingUserIds)
            ->paginate(request('per_page', 10));

        return new RoleWithoutUserResourceCollection($users);
    }
}
