<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\LabRequest;
use App\Models\LabRequestItem;
use App\Support\ActiveBranchWorkspace;
use App\Support\VisitWorkflowGuard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

final readonly class LaboratoryQueueController implements HasMiddleware
{
    public function __construct(
        private ActiveBranchWorkspace $activeBranchWorkspace,
        private VisitWorkflowGuard $visitWorkflowGuard,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:lab_requests.view', only: ['incoming', 'enterResults', 'reviewResults', 'viewResults']),
        ];
    }

    public function incoming(Request $request): Response
    {
        return $this->renderQueue($request, 'incoming');
    }

    public function enterResults(Request $request): Response
    {
        return $this->renderQueue($request, 'enter_results');
    }

    public function reviewResults(Request $request): Response
    {
        return $this->renderQueue($request, 'review_results');
    }

    public function viewResults(Request $request): Response
    {
        return $this->renderQueue($request, 'view_results');
    }

    private function renderQueue(Request $request, string $stage): Response
    {
        $search = mb_trim((string) $request->query('search', ''));

        $requests = $this->activeBranchWorkspace->apply(LabRequest::query())
            ->when($search !== '', static function (Builder $query) use ($search): void {
                $query->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery
                        ->whereHas('visit.patient', static function (Builder $patientQuery) use ($search): void {
                            $patientQuery
                                ->where('patient_number', 'like', sprintf('%%%s%%', $search))
                                ->orWhere('first_name', 'like', sprintf('%%%s%%', $search))
                                ->orWhere('last_name', 'like', sprintf('%%%s%%', $search));
                        })
                        ->orWhereHas('visit', static fn (Builder $visitQuery) => $visitQuery
                            ->where('visit_number', 'like', sprintf('%%%s%%', $search)))
                        ->orWhereHas('items.test', static function (Builder $testQuery) use ($search): void {
                            $testQuery
                                ->where('test_name', 'like', sprintf('%%%s%%', $search))
                                ->orWhere('test_code', 'like', sprintf('%%%s%%', $search));
                        });
                });
            })
            ->whereHas('items', function (Builder $query) use ($stage): void {
                $this->applyStageFilter($query, $stage);
            })
            ->with([
                'requestedBy:id,first_name,last_name',
                'visit:id,visit_number,patient_id',
                'visit.patient:id,patient_number,first_name,last_name,gender,age,age_units,date_of_birth,phone_number',
                'items' => function (HasMany $query) use ($stage): void {
                    $this->applyStageFilter($query, $stage)
                        ->with([
                            'test:id,test_code,test_name,description,lab_test_category_id,result_type_id,base_price',
                            'test.labCategory:id,name',
                            'test.specimenTypes:id,name',
                            'test.resultTypeDefinition:id,code,name',
                            'test.resultOptions:id,lab_test_catalog_id,label,sort_order',
                            'test.resultParameters:id,lab_test_catalog_id,label,unit,gender,age_min,age_max,reference_range,value_type,sort_order',
                            'specimen:id,lab_request_item_id,accession_number,specimen_type_id,specimen_type_name,status,collected_by,collected_at,outside_sample,outside_sample_origin,notes',
                            'specimen.collectedBy:id,first_name,last_name',
                            'resultEntry:id,lab_request_item_id,entered_by,entered_at,reviewed_by,reviewed_at,approved_by,approved_at,released_by,released_at,result_notes,review_notes,approval_notes',
                            'resultEntry.enteredBy:id,first_name,last_name',
                            'resultEntry.reviewedBy:id,first_name,last_name',
                            'resultEntry.approvedBy:id,first_name,last_name',
                            'resultEntry.values:id,lab_result_entry_id,lab_test_result_parameter_id,label,value_numeric,value_text,unit,gender,age_min,age_max,reference_range,sort_order',
                        ])->oldest();
                },
            ])
            ->orderByRaw("case when priority in ('critical', 'stat', 'urgent') then 0 else 1 end")
            ->latest('request_date')
            ->paginate(10)
            ->withQueryString();

        $tenantId = $requests->getCollection()->first()?->tenant_id;

        return Inertia::render('laboratory/queue', [
            'page' => $this->pageMeta($stage),
            'requests' => $requests,
            'filters' => [
                'search' => $search,
            ],
            'labReleasePolicy' => is_string($tenantId) && $tenantId !== ''
                ? $this->visitWorkflowGuard->labReleasePolicy($tenantId)
                : [
                    'require_review_before_release' => true,
                    'require_approval_before_release' => true,
                ],
        ]);
    }

    /**
     * @param  Builder<LabRequestItem>|HasMany<LabRequestItem, LabRequest>  $query
     * @return Builder<LabRequestItem>|HasMany<LabRequestItem, LabRequest>
     */
    private function applyStageFilter(Builder|HasMany $query, string $stage): Builder|HasMany
    {
        return match ($stage) {
            'incoming' => $query
                ->where('status', '!=', 'cancelled')
                ->whereNull('approved_at')
                ->whereDoesntHave('specimen'),
            'enter_results' => $query
                ->where('status', '!=', 'cancelled')
                ->whereNull('result_entered_at')
                ->where(static function (Builder $stageQuery): void {
                    $stageQuery
                        ->whereHas('specimen')
                        ->orWhere(static function (Builder $fallbackQuery): void {
                            $fallbackQuery
                                ->whereDoesntHave('specimen')
                                ->whereNotNull('received_at');
                        });
                }),
            'review_results' => $query
                ->where('status', '!=', 'cancelled')
                ->whereNotNull('result_entered_at')
                ->whereNull('approved_at'),
            'view_results' => $query
                ->whereNotNull('approved_at')
                ->where('status', 'completed'),
            default => $query->whereRaw('1 = 0'),
        };
    }

    /**
     * @return array{
     *     stage:string,
     *     title:string,
     *     description:string,
     *     action_label:string,
     *     route:string
     * }
     */
    private function pageMeta(string $stage): array
    {
        return match ($stage) {
            'incoming' => [
                'stage' => $stage,
                'title' => 'Incoming Lab Investigations Queue',
                'description' => 'Pick samples for ordered lab tests and move each collected item into the bench result queue.',
                'action_label' => 'Pick Sample',
                'route' => '/laboratory/incoming-investigations',
            ],
            'enter_results' => [
                'stage' => $stage,
                'title' => 'Enter Results',
                'description' => 'Work through patients whose samples have already been picked and record their laboratory results.',
                'action_label' => 'Enter Results',
                'route' => '/laboratory/enter-results',
            ],
            'review_results' => [
                'stage' => $stage,
                'title' => 'Review and Release Results',
                'description' => 'Check entered results, add notes, and release them from one focused queue.',
                'action_label' => 'Review and Release',
                'route' => '/laboratory/review-results',
            ],
            'view_results' => [
                'stage' => $stage,
                'title' => 'View Results',
                'description' => 'Open finalized laboratory results for each patient without exposing unreleased bench work.',
                'action_label' => 'View Result',
                'route' => '/laboratory/view-results',
            ],
            default => [
                'stage' => $stage,
                'title' => 'Laboratory Queue',
                'description' => 'Laboratory queue.',
                'action_label' => 'Open',
                'route' => '/laboratory/dashboard',
            ],
        };
    }
}
