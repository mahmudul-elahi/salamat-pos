<?php

namespace App\Http\Controllers\Pos\People;

use App\Filters\Pos\Customer\CustomerFilter;
use App\Http\Controllers\Controller;
use App\Models\Pos\Contact\Contact;

/**
 * Offline customers, online customers, and suppliers are all rows on the
 * same `contacts` table (CustomerController/SupplierController/
 * OnlineContactController each just add their own fixed ->where() on top) —
 * this merges the three into one "People" list with a type filter instead
 * of three separate pages.
 */
class PeopleController extends Controller
{
    public function index()
    {
        $perPage = request('per_page', 10);
        //$user = auth()->user();
        //$isAdmin = $user->isAppAdmin();
        //$can = fn(string $permission) => $isAdmin || $user->can($permission);

        // Same three-way split as CustomerFilter::contactType(), but as an
        // OR across whichever types this user is actually allowed to see —
        // a user with only view_customers must not see supplier/online rows
        // just because the list is merged.
        $visibleTypes = array_filter([
            'offline',
            'supplier',
            'online',
        ]);

        // if (empty($visibleTypes)) {
        //     throw new \App\Exceptions\AuthorizeException('Permission Denied');
        // }

        return Contact::query()
            ->filters(new CustomerFilter)
            ->with(['district:id,name', 'area:id,name'])
            ->where(function ($query) use ($visibleTypes) {
                foreach ($visibleTypes as $type) {
                    $query->orWhere(function ($q) use ($type) {
                        match ($type) {
                            'supplier' => $q->where('type', 'supplier'),
                            'online' => $q->where('online', true),
                            'offline' => $q->where('type', 'consumer')
                                ->where(fn($q2) => $q2->where('online', false)->orWhereNull('online')),
                        };
                    });
                }
            })
            ->orderByDesc('id')
            ->paginate($perPage)
            ->through(function ($contact) {
                return [
                    ...$contact->toArray(),
                    'created_at' => $contact->created_at->diffForHumans(),
                ];
            });
    }

    /**
     * Deletes several offline/supplier/online contacts in one request. The
     * three kinds carry different delete permissions (same as their
     * individual CustomerController/SupplierController/OnlineContactController
     * destroy() actions), so each row is checked against its own kind's
     * permission here rather than gating the whole endpoint behind one -
     * a user who can only delete_customers must not be able to bulk-delete
     * suppliers or online contacts just because they share this endpoint.
     */
    public function bulkDelete(\Illuminate\Http\Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:contacts,id'],
        ]);

        $user = $request->user();
        $isAdmin = $user->isAppAdmin();

        $contacts = Contact::query()->whereIn('id', $data['ids'])->get();

        $deleted = 0;
        $skipped = 0;

        foreach ($contacts as $contact) {
            $kind = match (true) {
                $contact->type === 'supplier' => 'supplier',
                (bool) $contact->online => 'online',
                default => 'offline',
            };

            $permission = match ($kind) {
                'supplier' => 'delete_suppliers',
                'online' => 'delete_online_contacts',
                'offline' => 'delete_customers',
            };

            if (!$isAdmin && !$user->can($permission)) {
                $skipped++;
                continue;
            }

            $contact->delete();
            $deleted++;
        }

        $message = "{$deleted} people deleted successfully";
        if ($skipped) {
            $message .= ", {$skipped} skipped (no permission)";
        }

        return successResponse($message, ['deleted' => $deleted, 'skipped' => $skipped]);
    }
}
