<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ApproveLabResultEntry;
use App\Actions\CollectLabSpecimen;
use App\Actions\CorrectLabResultEntry;
use App\Actions\ReceiveLabRequestItem;
use App\Actions\ReviewLabResultEntry;
use App\Actions\StoreLabResultEntry;
use App\Http\Requests\ApproveLabResultEntryRequest;
use App\Http\Requests\CollectLabSpecimenRequest;
use App\Http\Requests\CorrectLabResultEntryRequest;
use App\Http\Requests\ReviewLabResultEntryRequest;
use App\Http\Requests\StoreLabResultEntryRequest;
use App\Models\LabRequestItem;
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
            new Middleware('permission:lab_requests.update', only: ['collectSample', 'receive', 'store', 'correct', 'review', 'approve']),
        ];
    }

    public function collectSample(
        CollectLabSpecimenRequest $request,
        LabRequestItem $labRequestItem,
        CollectLabSpecimen $action,
    ): RedirectResponse {
        return $this->handleAction(
            $request,
            $labRequestItem,
            fn (string $staffId): LabRequestItem => $action->handle($labRequestItem, $request->validated(), $staffId),
            'Sample picked successfully.',
        );
    }

    public function receive(
        Request $request,
        LabRequestItem $labRequestItem,
        ReceiveLabRequestItem $action,
    ): RedirectResponse {
        return $this->handleAction(
            $request,
            $labRequestItem,
            fn (string $staffId): LabRequestItem => $action->handle($labRequestItem, $staffId),
            'Lab request item received successfully.',
        );
    }

    public function store(
        StoreLabResultEntryRequest $request,
        LabRequestItem $labRequestItem,
        StoreLabResultEntry $action,
    ): RedirectResponse {
        return $this->handleAction(
            $request,
            $labRequestItem,
            fn (string $staffId): LabRequestItem => $action->handle($labRequestItem, $request->storeDto(), $staffId),
            'Lab results saved successfully.',
        );
    }

    public function correct(
        CorrectLabResultEntryRequest $request,
        LabRequestItem $labRequestItem,
        CorrectLabResultEntry $action,
    ): RedirectResponse {
        return $this->handleAction(
            $request,
            $labRequestItem,
            fn (string $staffId): LabRequestItem => $action->handle($labRequestItem, $request->validated(), $staffId),
            'Lab result correction saved. Review and release it again before clinicians can see it.',
        );
    }

    public function review(
        ReviewLabResultEntryRequest $request,
        LabRequestItem $labRequestItem,
        ReviewLabResultEntry $action,
    ): RedirectResponse {
        return $this->handleAction(
            $request,
            $labRequestItem,
            fn (string $staffId): LabRequestItem => $action->handle(
                $labRequestItem,
                $staffId,
                $this->nullableText($request->input('review_notes')),
            ),
            static fn (LabRequestItem $updatedItem): string => $updatedItem->approved_at !== null
                ? 'Lab results reviewed and released successfully.'
                : 'Lab results reviewed successfully.',
        );
    }

    public function approve(
        ApproveLabResultEntryRequest $request,
        LabRequestItem $labRequestItem,
        ApproveLabResultEntry $action,
    ): RedirectResponse {
        return $this->handleAction(
            $request,
            $labRequestItem,
            fn (string $staffId): LabRequestItem => $action->handle(
                $labRequestItem,
                $staffId,
                $this->nullableText($request->input('review_notes')),
                $this->nullableText($request->input('approval_notes')),
            ),
            'Lab results reviewed, approved, and released successfully.',
        );
    }

    /**
     * @param  Closure(string):LabRequestItem  $callback
     * @param  string|Closure(LabRequestItem):string  $successMessage
     */
    private function handleAction(
        Request $request,
        LabRequestItem $labRequestItem,
        Closure $callback,
        string|Closure $successMessage,
    ): RedirectResponse {
        $labRequest = $labRequestItem->request()->firstOrFail();
        $this->activeBranchWorkspace->authorizeModel($labRequest);

        $staffId = $request->user()?->staff_id;

        if (! is_string($staffId) || $staffId === '') {
            return $this->redirectToTarget($request, $labRequestItem)
                ->with('error', 'This action needs a linked staff profile for audit tracking.');
        }

        try {
            $result = $callback($staffId);
        } catch (ValidationException $validationException) {
            return $this->redirectToTarget($request, $labRequestItem)
                ->with('error', $validationException->validator->errors()->first() ?: 'The lab workflow action could not be completed.');
        }

        return $this->redirectToTarget($request, $labRequestItem)
            ->with('success', is_string($successMessage) ? $successMessage : $successMessage($result));
    }

    private function redirectToTarget(Request $request, LabRequestItem $labRequestItem): RedirectResponse
    {
        $redirectTo = $request->input('redirect_to');

        if (is_string($redirectTo) && $redirectTo !== '' && str_starts_with($redirectTo, '/')) {
            return redirect()->to($redirectTo);
        }

        return to_route('laboratory.request-items.show', $labRequestItem);
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
