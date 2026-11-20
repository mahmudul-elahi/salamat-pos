<?php

namespace App\Http\Resources\Profile;

use App\Models\User;
use App\Repositories\User\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class ProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'profile_picture' => $this->getProfilePictureAttribute(),
            'timezone' => $this->timezone ?? 'Asia/Dhaka',
            'is_employee' => (bool) $this->is_employee,
            'tenant' => $this->formatTenant(),
            'permissions' => $this->getPermission(),
        ];
    }

    private function formatTenant(): ?array
    {
        $tenant = tenant();
        if (!$tenant) {
            return null;
        }

        return [
            'id'   => $tenant->id,
            'name' => $tenant->name,
        ];
    }

    private function getPermission(): array
    {
        $permissions = resolve(UserRepository::class)->getPermissionsForFrontEnd();
        /** @var User $user */
        $user = Auth::user();
        return array_merge(
            $permissions,
            [
                'is_app_admin'  => $user->isAppAdmin(),
                'is_employee'   => (bool) $user->is_employee,
                'tenant_ids'    => $user->is_employee
                    ? $user->tenants->pluck('id')->toArray()
                    : [],
            ]
        );
    }
}
