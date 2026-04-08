<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\LabRequestItem;
use App\Support\ActiveBranchWorkspace;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
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

    public function index(Request $request): RedirectResponse
    {
        $query = $request->query();

        return to_route('laboratory.incoming.index', $query);
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
            'specimen:id,lab_request_item_id,accession_number,specimen_type_id,specimen_type_name,status,collected_by,collected_at,rejected_by,rejected_at,rejection_reason,outside_sample,outside_sample_origin,notes',
            'specimen.collectedBy:id,first_name,last_name',
            'specimen.rejectedBy:id,first_name,last_name',
            'resultEntry:id,lab_request_item_id,entered_by,entered_at,reviewed_by,reviewed_at,approved_by,approved_at,released_by,released_at,result_notes,review_notes,approval_notes',
            'resultEntry.enteredBy:id,first_name,last_name',
            'resultEntry.reviewedBy:id,first_name,last_name',
            'resultEntry.approvedBy:id,first_name,last_name',
            'resultEntry.values:id,lab_result_entry_id,lab_test_result_parameter_id,label,value_numeric,value_text,unit,reference_range,sort_order',
            'consumables' => static fn (HasMany $query): HasMany => $query
                ->with('recordedBy:id,first_name,last_name')
                ->latest('used_at'),
        ]);

        return Inertia::render('laboratory/request-item', [
            'labRequestItem' => $labRequestItem,
        ]);
    }
}
