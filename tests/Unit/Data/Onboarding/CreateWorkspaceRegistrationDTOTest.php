<?php

declare(strict_types=1);

use App\Data\Onboarding\CreateWorkspaceRegistrationDTO;
use Illuminate\Foundation\Http\FormRequest;

it('normalizes workspace registration input into a typed dto', function (): void {
    /**
     * @param  array<string, string|null>  $validated
     */
    $request = static fn (array $validated): FormRequest => new class($validated) extends FormRequest
    {
        /**
         * @param  array<string, string|null>  $validatedInput
         */
        public function __construct(private readonly array $validatedInput)
        {
            parent::__construct();
        }

        /**
         * @return array<string, string|null>
         */
        public function validated($key = null, $default = null): array
        {
            return $this->validatedInput;
        }
    };

    $dto = CreateWorkspaceRegistrationDTO::fromRequest($request([
        'owner_name' => 'Grace Hopper',
        'workspace_name' => 'Acme Hospital',
        'email' => 'owner@example.com',
        'subscription_package_id' => 'package-1',
        'facility_level' => 'hospital',
        'country_id' => ' country-1 ',
        'domain' => ' acme.test ',
    ]));

    expect($dto->ownerName)->toBe('Grace Hopper')
        ->and($dto->workspaceName)->toBe('Acme Hospital')
        ->and($dto->email)->toBe('owner@example.com')
        ->and($dto->subscriptionPackageId)->toBe('package-1')
        ->and($dto->facilityLevel)->toBe('hospital')
        ->and($dto->countryId)->toBe('country-1')
        ->and($dto->domain)->toBe('acme.test');
});
