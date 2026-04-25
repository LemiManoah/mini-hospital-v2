<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateReferralFacility;
use App\Actions\DeleteReferralFacility;
use App\Actions\UpdateReferralFacility;
use App\Http\Requests\DeleteReferralFacilityRequest;
use App\Http\Requests\StoreReferralFacilityRequest;
use App\Http\Requests\UpdateReferralFacilityRequest;
use App\Models\ReferralFacility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ReferralFacilityController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:referral_facilities.view', only: ['index']),
            new Middleware('permission:referral_facilities.create', only: ['create', 'store']),
            new Middleware('permission:referral_facilities.update', only: ['edit', 'update']),
            new Middleware('permission:referral_facilities.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        $search = mb_trim((string) $request->query('search', ''));

        $referralFacilities = ReferralFacility::query()
            ->when(
                $search !== '',
                static fn (Builder $query) => $query->where('name', 'like', sprintf('%%%s%%', $search))
                    ->orWhere('facility_type', 'like', sprintf('%%%s%%', $search))
                    ->orWhere('phone', 'like', sprintf('%%%s%%', $search))
                    ->orWhere('email', 'like', sprintf('%%%s%%', $search))
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('referral-facilities/index', [
            'referralFacilities' => $referralFacilities,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('referral-facilities/create');
    }

    public function store(StoreReferralFacilityRequest $request, CreateReferralFacility $action): RedirectResponse
    {
        $action->handle($request->validated());

        return to_route('referral-facilities.index')->with('success', 'Referral facility created successfully.');
    }

    public function edit(ReferralFacility $referralFacility): Response
    {
        return Inertia::render('referral-facilities/edit', [
            'referralFacility' => $referralFacility,
        ]);
    }

    public function update(
        UpdateReferralFacilityRequest $request,
        ReferralFacility $referralFacility,
        UpdateReferralFacility $action,
    ): RedirectResponse {
        $action->handle($referralFacility, $request->validated());

        return to_route('referral-facilities.index')->with('success', 'Referral facility updated successfully.');
    }

    public function destroy(
        DeleteReferralFacilityRequest $request,
        ReferralFacility $referralFacility,
        DeleteReferralFacility $action,
    ): RedirectResponse {
        $action->handle($referralFacility);

        return to_route('referral-facilities.index')->with('success', 'Referral facility deleted successfully.');
    }
}
