<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class LaboratoryManagementController
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        abort_unless(
            $user?->can('specimen_types.view')
            || $user?->can('result_types.view')
            || $user?->can('lab_test_categories.view')
            || $user?->can('lab_test_catalogs.view'),
            403,
        );

        return Inertia::render('laboratory/management', [
            'sections' => [
                [
                    'title' => 'Service Categories',
                    'description' => 'Manage the category groupings used to organize laboratory services and keep catalog browsing tidy.',
                    'href' => '/lab-test-categories',
                    'permission' => 'lab_test_categories.view',
                ],
                [
                    'title' => 'Laboratory Services',
                    'description' => 'Manage the actual laboratory services, pricing, specimen requirements, and result capture setup.',
                    'href' => '/lab-test-catalogs',
                    'permission' => 'lab_test_catalogs.view',
                ],
                [
                    'title' => 'Specimen Types',
                    'description' => 'Manage the specimen options available when picking samples from the incoming investigations queue.',
                    'href' => '/specimen-types',
                    'permission' => 'specimen_types.view',
                ],
                [
                    'title' => 'Result Types',
                    'description' => 'Manage how lab services capture results, such as free entry, defined options, and parameter panels.',
                    'href' => '/result-types',
                    'permission' => 'result_types.view',
                ],
            ],
        ]);
    }
}
