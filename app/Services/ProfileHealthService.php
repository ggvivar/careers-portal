<?php

namespace App\Services;

class ProfileHealthService
{
    public function evaluate(
        array $applicant,
        array $education,
        array $employment,
        array $documents
    ): array {
        $hasResume = false;
        foreach ($documents as $document) {
            $type = strtolower(trim((string) ($document['type'] ?? '')));
            if (str_contains($type, 'resume') || str_contains($type, 'cv')) {
                $hasResume = true;
                break;
            }
        }

        $items = [
            $this->item(
                'Basic information',
                $this->filled($applicant, ['first_name', 'last_name', 'email', 'phone', 'birthdate']),
                20,
                '#personal',
                'Complete your name, date of birth, email, and mobile number.'
            ),
            $this->item(
                'Location details',
                $this->filled($applicant, ['city', 'province', 'country']),
                10,
                '#personal',
                'Add your city, province, and country.'
            ),
            $this->item(
                'Current and permanent address',
                $this->filled($applicant, ['current_address', 'permanent_address']),
                15,
                '#personal',
                'Provide both current and permanent addresses.'
            ),
            $this->item(
                'Personal background',
                $this->filled($applicant, ['religion', 'civil_status'])
                    && ($applicant['covid_vaccinated'] ?? null) !== null,
                15,
                '#personal',
                'Complete religion, civil status, and vaccination disclosure.'
            ),
            $this->item(
                'Company-relative disclosure',
                $this->relativeDisclosureComplete($applicant),
                10,
                '#personal',
                'Declare whether you have a relative working in the company.'
            ),
            $this->item(
                'Education history',
                count($education) > 0,
                10,
                '#education',
                'Add at least one education record.'
            ),
            $this->item(
                'Employment history',
                count($employment) > 0,
                10,
                '#employment',
                'Add your current or previous employment.'
            ),
            $this->item(
                'Resume or CV',
                $hasResume,
                10,
                '#documents',
                'Upload a current resume or CV.'
            ),
        ];

        $percentage = array_sum(array_map(
            static fn (array $item): int => $item['complete'] ? $item['weight'] : 0,
            $items
        ));

        return [
            'percentage' => min(100, $percentage),
            'items'      => $items,
            'complete'   => $percentage >= 100,
        ];
    }

    private function item(
        string $label,
        bool $complete,
        int $weight,
        string $anchor,
        string $description
    ): array {
        return compact('label', 'complete', 'weight', 'anchor', 'description');
    }

    private function filled(array $data, array $keys): bool
    {
        foreach ($keys as $key) {
            if (! isset($data[$key]) || trim((string) $data[$key]) === '') {
                return false;
            }
        }

        return true;
    }

    private function relativeDisclosureComplete(array $applicant): bool
    {
        $hasRelative = (bool) ($applicant['has_company_relative'] ?? false);
        if (! $hasRelative) {
            return ($applicant['has_company_relative'] ?? null) !== null;
        }

        $name = trim((string) ($applicant['company_relative_name'] ?? ''));
        $relationship = trim((string) ($applicant['company_relative_relation'] ?? ''));
        if ($name === '' || $relationship === '') {
            return false;
        }

        if (in_array(strtolower($relationship), ['other', 'others'], true)) {
            return trim((string) ($applicant['company_relative_relation_other'] ?? '')) !== '';
        }

        return true;
    }
}
