<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PharmacyPosCartStatus;
use App\Models\InventoryLocation;
use App\Models\PharmacyPosCart;
use App\Support\BranchContext;
use App\Support\InventoryLocationAccess;
use App\Support\PharmacyPosCartNumberGenerator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class OpenPharmacyPosCartAction
{
    public function __construct(
        private PharmacyPosCartNumberGenerator $cartNumberGenerator,
        private InventoryLocationAccess $inventoryLocationAccess,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(array $attributes): PharmacyPosCart
    {
        return DB::transaction(function () use ($attributes): PharmacyPosCart {
            $user = Auth::user();
            $branchId = BranchContext::getActiveBranchId();
            $tenantId = is_object($user) ? $user->tenant_id : null;
            $locationId = is_string($attributes['inventory_location_id'] ?? null)
                ? $attributes['inventory_location_id']
                : null;

            if ($locationId !== null) {
                $canAccess = $this->inventoryLocationAccess->canAccessLocationForTypes(
                    $user,
                    $locationId,
                    ['pharmacy'],
                    $branchId,
                );

                if (! $canAccess) {
                    throw ValidationException::withMessages([
                        'inventory_location_id' => 'You do not have access to the selected pharmacy location.',
                    ]);
                }

                $location = InventoryLocation::query()->findOrFail($locationId);

                if (! $location->is_dispensing_point) {
                    throw ValidationException::withMessages([
                        'inventory_location_id' => 'Select a pharmacy dispensing point for this POS session.',
                    ]);
                }
            }

            return PharmacyPosCart::query()->create([
                'tenant_id' => $tenantId,
                'branch_id' => $branchId,
                'inventory_location_id' => $locationId,
                'user_id' => Auth::id(),
                'cart_number' => $this->cartNumberGenerator->generate($tenantId),
                'customer_name' => $this->nullableText($attributes['customer_name'] ?? null),
                'customer_phone' => $this->nullableText($attributes['customer_phone'] ?? null),
                'notes' => $this->nullableText($attributes['notes'] ?? null),
                'status' => PharmacyPosCartStatus::Active,
            ]);
        });
    }

    private function nullableText(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = mb_trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
