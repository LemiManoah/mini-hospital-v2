<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ApproveLabResultEntry;
use App\Actions\CollectLabSpecimen;
use App\Actions\CorrectLabResultEntry;
use App\Actions\ReceiveLabOrderItem;
use App\Actions\ReviewLabResultEntry;
use App\Actions\StoreLabResultEntry;
use App\Http\Requests\ApproveLabResultEntryRequest;
use App\Http\Requests\CollectLabSpecimenRequest;
use App\Http\Requests\CorrectLabResultEntryRequest;
use App\Http\Requests\ReviewLabResultEntryRequest;
use App\Http\Requests\StoreLabResultEntryRequest;
use App\Models\LabOrderItem;
use App\Support\ActiveBranchWorkspace;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\ValidationException;

final readonly class LabResultWorkflowController implements HasMiddleware
{
    public function __construct(
        private ActiveBranchWorkspace $activeBranchWorkspace,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:lab_orders.update', only: ['collectSample', 'receive', 'store', 'correct', 'review', 'approve']),
        ];
    }

    public function collectSample(
        CollectLabSpecimenRequest $request,
        LabOrderItem $labOrderItem,
        CollectLabSpecimen $action,
    ): RedirectResponse {
        return $this->handleAction(
            $request,
            $labOrderItem,
            fn (string $staffId): LabOrderItem => $action->handle($labOrderItem, $request->validated(), $staffId),
            'Sample picked successfully.',
        );
    }

    public function receive(
        Request $request,
        LabOrderItem $labOrderItem,
        ReceiveLabOrderItem $action,
    ): RedirectResponse {
        return $this->handleAction(
            $request,
            $labOrderItem,
            fn (string $staffId): LabOrderItem => $action->handle($labOrderItem, $staffId),
            'Lab order item received successfully.',
        );
    }

    public function store(
        StoreLabResultEntryRequest $request,
        LabOrderItem $labOrderItem,
        StoreLabResultEntry $action,
    ): RedirectResponse {
        return $this->handleAction(
            $request,
            $labOrderItem,
            fn (string $staffId): LabOrderItem => $action->handle($labOrderItem, $request->storeDto(), $staffId),
            'Lab results saved successfully.',
        );
    }

    public function correct(
        CorrectLabResultEntryRequest $request,
        LabOrderItem $labOrderItem,
        CorrectLabResultEntry $action,
    ): RedirectResponse {
        return $this->handleAction(
            $request,
            $labOrderItem,
            fn (string $staffId): LabOrderItem => $action->handle($labOrderItem, $request->validated(), $staffId),
            'Lab result correction saved. Review and release it again before clinicians can see it.',
        );
    }

    public function review(
        ReviewLabResultEntryRequest $request,
        LabOrderItem $labOrderItem,
        ReviewLabResultEntry $action,
    ): RedirectResponse {
        return $this->handleAction(
            $request,
            $labOrderItem,
            fn (string $staffId): LabOrderItem => $action->handle(
                $labOrderItem,
                $staffId,
                $this->nullableText($request->input('review_notes')),
            ),
            static fn (LabOrderItem $updatedItem): string => $updatedItem->approved_at !== null
                ? 'Lab results reviewed and released successfully.'
                : 'Lab results reviewed successfully.',
        );
    }

    public function approve(
        ApproveLabResultEntryRequest $request,
        LabOrderItem $labOrderItem,
        ApproveLabResultEntry $action,
    ): RedirectResponse {
        return $this->handleAction(
            $request,
            $labOrderItem,
            fn (string $staffId): LabOrderItem => $action->handle(
                $labOrderItem,
                $staffId,
                $this->nullableText($request->input('review_notes')),
                $this->nullableText($request->input('approval_notes')),
            ),
            'Lab results reviewed, approved, and released successfully.',
        );
    }

    /**
     * @param  Closure(string):LabOrderItem  $callback
     * @param  string|Closure(LabOrderItem):string  $successMessage
     */
    private function handleAction(
        Request $request,
        LabOrderItem $labOrderItem,
        Closure $callback,
        string|Closure $successMessage,
    ): RedirectResponse {
        $labOrder = $labOrderItem->order()->firstOrFail();
        $this->activeBranchWorkspace->authorizeModel($labOrder);

        $staffId = $request->user()?->staff_id;

        if (! is_string($staffId) || $staffId === '') {
            return $this->redirectToTarget($request, $labOrderItem)
                ->with('error', 'This action needs a linked staff profile for audit tracking.');
        }

        try {
            $result = $callback($staffId);
        } catch (ValidationException $validationException) {
            return $this->redirectToTarget($request, $labOrderItem)
                ->with('error', $validationException->validator->errors()->first() ?: 'The lab workflow action could not be completed.');
        }

        return $this->redirectToTarget($request, $labOrderItem)
            ->with('success', is_string($successMessage) ? $successMessage : $successMessage($result));
    }

    private function redirectToTarget(Request $request, LabOrderItem $labOrderItem): RedirectResponse
    {
        $redirectTo = $request->input('redirect_to');

        if (is_string($redirectTo) && $redirectTo !== '' && str_starts_with($redirectTo, '/')) {
            return redirect()->to($redirectTo);
        }

        return to_route('laboratory.order-items.show', $labOrderItem);
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
