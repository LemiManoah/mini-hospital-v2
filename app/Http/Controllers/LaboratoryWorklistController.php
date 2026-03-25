<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\LabRequest;
use App\Models\LabRequestItem;
use App\Support\ActiveBranchWorkspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

final readonly class LaboratoryWorklistController implements HasMiddleware
{
    public function __construct(
        private ActiveBranchWorkspace $activeBranchWorkspace,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:lab_requests.view', only: ['index', 'show']),
        ];
    }

    public function index(Request $request): Response
    {
        $search = mb_trim((string) $request->query('search', ''));
        $status = mb_trim((string) $request->query('status', ''));

        $requests = $this->activeBranchWorkspace->apply(LabRequest::query())
            ->when($status !== '', static fn (Builder $query) => $query->where('status', $status))
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
            ->with([
                'requestedBy:id,first_name,last_name',
                'visit:id,visit_number,patient_id',
                'visit.patient:id,patient_number,first_name,last_name',
                'items' => static fn ($query) => $query
                    ->with([
                        'test:id,test_code,test_name,lab_test_category_id,result_type_id',
                        'test.labCategory:id,name',
                        'test.specimenTypes:id,name',
                        'test.resultTypeDefinition:id,code,name',
                    ])
                    ->orderBy('created_at'),
            ])
            ->orderByRaw("case when priority = 'urgent' then 0 else 1 end")
            ->latest('request_date')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('laboratory/worklist', [
            'requests' => $requests,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'statuses' => [
                ['value' => 'requested', 'label' => 'Requested'],
                ['value' => 'in_progress', 'label' => 'In Progress'],
                ['value' => 'completed', 'label' => 'Completed'],
                ['value' => 'cancelled', 'label' => 'Cancelled'],
            ],
        ]);
    }

    public function show(LabRequestItem $labRequestItem): Response
    {
        $this->activeBranchWorkspace->authorizeModel($labRequestItem->request);

        $labRequestItem->load([
            'request:id,visit_id,facility_branch_id,requested_by,request_date,priority,status,clinical_notes',
            'request.requestedBy:id,first_name,last_name',
            'request.visit:id,visit_number,patient_id',
            'request.visit.patient:id,patient_number,first_name,last_name,gender,phone_number',
            'test:id,test_code,test_name,description,lab_test_category_id,result_type_id,base_price',
            'test.labCategory:id,name',
            'test.specimenTypes:id,name',
            'test.resultTypeDefinition:id,code,name',
            'test.resultOptions:id,lab_test_catalog_id,label,sort_order',
            'test.resultParameters:id,lab_test_catalog_id,label,unit,reference_range,value_type,sort_order',
            'resultEntry:id,lab_request_item_id,entered_by,entered_at,reviewed_by,reviewed_at,approved_by,approved_at,released_by,released_at,result_notes,review_notes,approval_notes',
            'resultEntry.enteredBy:id,first_name,last_name',
            'resultEntry.reviewedBy:id,first_name,last_name',
            'resultEntry.approvedBy:id,first_name,last_name',
            'resultEntry.values:id,lab_result_entry_id,lab_test_result_parameter_id,label,value_numeric,value_text,unit,reference_range,sort_order',
            'consumables' => static fn ($query) => $query
                ->with('recordedBy:id,first_name,last_name')
                ->latest('used_at'),
        ]);

        return Inertia::render('laboratory/request-item', [
            'labRequestItem' => $labRequestItem,
        ]);
    }
}
