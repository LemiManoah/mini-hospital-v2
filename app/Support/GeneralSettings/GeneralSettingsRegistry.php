<?php

declare(strict_types=1);

namespace App\Support\GeneralSettings;

final class GeneralSettingsRegistry
{
    /**
     * @return list<array{
     *     title: string,
     *     description: string,
     *     fields: list<array{
     *         field: string,
     *         key: string,
     *         label: string,
     *         description: string,
     *         type: 'boolean'|'text'|'select',
     *         default: bool|string|null
     *     }>
     * }>
     */
    public static function sections(): array
    {
        return [
            [
                'title' => 'Billing And Payment Rules',
                'description' => 'Control when clinical work can move ahead relative to payment and how insured patients are handled.',
                'fields' => [
                    [
                        'field' => 'require_payment_before_consultation',
                        'key' => 'payments.require_payment_before_consultation',
                        'label' => 'Require payment before consultation',
                        'description' => 'Block consultation from starting until the visit has been paid.',
                        'type' => 'boolean',
                        'default' => false,
                    ],
                    [
                        'field' => 'require_payment_before_laboratory',
                        'key' => 'payments.require_payment_before_laboratory',
                        'label' => 'Require payment before laboratory',
                        'description' => 'Block lab ordering or processing until payment is confirmed.',
                        'type' => 'boolean',
                        'default' => false,
                    ],
                    [
                        'field' => 'require_payment_before_pharmacy',
                        'key' => 'payments.require_payment_before_pharmacy',
                        'label' => 'Require payment before pharmacy',
                        'description' => 'Prevent dispensing before the bill or approved cover is in place.',
                        'type' => 'boolean',
                        'default' => false,
                    ],
                    [
                        'field' => 'require_payment_before_procedures',
                        'key' => 'payments.require_payment_before_procedures',
                        'label' => 'Require payment before procedures',
                        'description' => 'Stop procedures and other services from being performed before payment.',
                        'type' => 'boolean',
                        'default' => false,
                    ],
                    [
                        'field' => 'allow_insured_bypass_upfront_payment',
                        'key' => 'payments.allow_insured_bypass_upfront_payment',
                        'label' => 'Allow insured patients to bypass upfront payment',
                        'description' => 'Lets covered patients continue when insurance approval rules allow it.',
                        'type' => 'boolean',
                        'default' => true,
                    ],
                ],
            ],
            [
                'title' => 'Currency And Numbering',
                'description' => 'Set the facility currency and the prefixes used on common operational documents.',
                'fields' => [
                    [
                        'field' => 'default_currency_id',
                        'key' => 'pricing.default_currency_id',
                        'label' => 'Default facility currency',
                        'description' => 'Used as the preferred currency choice for pricing and billing screens.',
                        'type' => 'select',
                        'default' => null,
                    ],
                    [
                        'field' => 'patient_number_prefix',
                        'key' => 'numbering.patient_number_prefix',
                        'label' => 'Patient number prefix',
                        'description' => 'Short prefix for generated patient numbers. Example: PAT',
                        'type' => 'text',
                        'default' => 'PAT',
                    ],
                    [
                        'field' => 'receipt_number_prefix',
                        'key' => 'numbering.receipt_number_prefix',
                        'label' => 'Payment receipt prefix',
                        'description' => 'Short prefix for visit payment receipt numbers. Example: RCT',
                        'type' => 'text',
                        'default' => 'RCT',
                    ],
                ],
            ],
            [
                'title' => 'Pharmacy Rules',
                'description' => 'Capture the pharmacy behaviors that usually vary between facilities.',
                'fields' => [
                    [
                        'field' => 'enable_batch_tracking_when_dispensing',
                        'key' => 'pharmacy.enable_batch_tracking_when_dispensing',
                        'label' => 'Enable batch tracking when dispensing',
                        'description' => 'Require dispensing flows to stay batch-aware for traceability.',
                        'type' => 'boolean',
                        'default' => true,
                    ],
                    [
                        'field' => 'allow_partial_dispense',
                        'key' => 'pharmacy.allow_partial_dispense',
                        'label' => 'Allow partial dispensing',
                        'description' => 'Permit pharmacists to dispense less than prescribed when stock is short.',
                        'type' => 'boolean',
                        'default' => true,
                    ],
                ],
            ],
            [
                'title' => 'Laboratory Release Rules',
                'description' => 'Decide how much review is required before clinicians can see a result.',
                'fields' => [
                    [
                        'field' => 'require_review_before_lab_release',
                        'key' => 'laboratory.require_review_before_release',
                        'label' => 'Require review before release',
                        'description' => 'A second lab step must review the result before it can move ahead.',
                        'type' => 'boolean',
                        'default' => true,
                    ],
                    [
                        'field' => 'require_approval_before_lab_release',
                        'key' => 'laboratory.require_approval_before_release',
                        'label' => 'Require approval before release',
                        'description' => 'Keep the final approval step mandatory before clinicians can view results.',
                        'type' => 'boolean',
                        'default' => true,
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, array{
     *     field: string,
     *     key: string,
     *     label: string,
     *     description: string,
     *     type: 'boolean'|'text'|'select',
     *     default: bool|string|null
     * }>
     */
    public static function fields(): array
    {
        $fields = [];

        foreach (self::sections() as $section) {
            foreach ($section['fields'] as $field) {
                $fields[$field['field']] = $field;
            }
        }

        return $fields;
    }

    /**
     * @param  array<string, string|null>  $stored
     * @return array<string, bool|string|null>
     */
    public static function resolveValues(array $stored): array
    {
        $values = [];

        foreach (self::fields() as $fieldName => $field) {
            $storedValue = $stored[$field['key']] ?? null;

            $values[$fieldName] = match ($field['type']) {
                'boolean' => $storedValue === null
                    ? (bool) $field['default']
                    : in_array(mb_strtolower($storedValue), ['1', 'true', 'yes', 'on'], true),
                default => $storedValue ?? $field['default'],
            };
        }

        return $values;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, string|null>
     */
    public static function serializeValues(array $validated): array
    {
        $serialized = [];

        foreach (self::fields() as $fieldName => $field) {
            $value = $validated[$fieldName] ?? $field['default'];

            $serialized[$field['key']] = match ($field['type']) {
                'boolean' => (bool) $value ? '1' : '0',
                default => is_string($value) && mb_trim($value) !== '' ? mb_trim($value) : null,
            };
        }

        return $serialized;
    }
}
