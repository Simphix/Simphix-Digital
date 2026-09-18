<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Single source of truth for what each CAC registration category needs —
 * the customer form, validation, the checklist sidebar, and the admin/user
 * detail pages all render from here, so a requirement is only ever written
 * down once. Sourced from the CAC requirements checklist supplied by ops
 * (Business Name / LTD / Incorporated Trustees & NGO).
 *
 * Pricing is deliberately NOT here — it lives on the `cac` Service's
 * ServiceFields (field_code below), managed in /admin/services.
 */
class CacRequirements
{
    public const SERVICE_NAME = 'cac';

    /** Allowed upload extensions per document kind. */
    private const IMAGE_DOC = ['file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'];
    private const ANY_DOC = ['file', 'mimes:jpg,jpeg,png,pdf,doc,docx', 'max:6144'];

    public const ID_TYPES = [
        'nin_slip' => 'NIN slip',
        'national_id' => 'National ID card',
        'voters_card' => "Voter's card",
        'drivers_license' => "Driver's license",
        'intl_passport' => 'International passport',
    ];

    public static function all(): array
    {
        return [
            'business_name' => [
                'slug' => 'business-name',
                'label' => 'Business Name',
                'icon' => 'briefcase',
                'field_code' => 'cac_business_name',
                'tagline' => 'Sole proprietorship or partnership under a registered name',
                'kind' => 'individual',
                'people_label' => 'Applicant',
                'people_min' => 1,
                'names_count' => 2,
                'timeline' => ['Name search & reservation: 1–3 days', 'CAC approval: typically within a few working days'],
                'checklist' => [
                    'First name, surname & other names',
                    'Phone number & email address',
                    'Residential address',
                    'NIN, state of origin, LGA & date of birth',
                    '1st and 2nd business name choice',
                    'Business address & type of business',
                    'Passport photograph, image of NIN slip & image of signature',
                ],
                'deliverables' => ['Certificate of Registration', 'Status report'],
            ],
            'private_limited' => [
                'slug' => 'private-limited',
                'label' => 'Private Company Limited by Shares',
                'icon' => 'building-2',
                'field_code' => 'cac_private_limited',
                'tagline' => 'LTD company with one or more shareholders',
                'kind' => 'shareholders',
                'people_label' => 'Shareholder',
                'people_min' => 1,
                'names_count' => 2,
                'timeline' => ['Name reservation: 1–3 days', 'Incorporation & CAC approval: typically within a few working days'],
                'checklist' => [
                    'For each shareholder: ID card, signature, passport photograph & NIN slip',
                    'Email, phone number, home address (house number, street, LGA)',
                    'NIN, state of origin, LGA & date of birth',
                    'Shareholding percentage of each shareholder (must total 100% if more than one)',
                    'Business address (shop/house number, street, LGA) & nature of business(es)',
                    'Witness: name, phone, email, home address & signature',
                    'Secretary details (optional)',
                    '1st and 2nd company name choice',
                ],
                'deliverables' => ['Certificate of Incorporation', 'Memorandum & Articles of Association', 'Status report'],
            ],
            'incorporated_trustee' => self::trusteeDefinition('incorporated_trustee', 'incorporated-trustee', 'Incorporated Trustee', 'landmark', 'cac_incorporated_trustee', 'Registered trustees for a religious, charitable or social body'),
            'ngo' => self::trusteeDefinition('ngo', 'ngo', 'NGO', 'heart-handshake', 'cac_ngo', 'Non-profit / non-governmental organisation'),
        ];
    }

    private static function trusteeDefinition(string $key, string $slug, string $label, string $icon, string $code, string $tagline): array
    {
        return [
            'slug' => $slug,
            'label' => $label,
            'icon' => $icon,
            'field_code' => $code,
            'tagline' => $tagline,
            'kind' => 'trustees',
            'people_label' => 'Trustee',
            'people_min' => 2,
            'names_count' => 2,
            'timeline' => [
                'Name reservation: 1–3 days',
                'Newspaper publication (1 national + 1 local/state paper): 2–4 weeks',
                'Public objection period: usually 28 days',
                'CAC approval: 2–3 weeks after the objection period',
            ],
            'checklist' => [
                '2 proposed names in order of preference (must not include “Limited/Ltd”)',
                'Clear non-profit aims & objectives (charity, education, health, religious, community development…)',
                'Minimum 2 trustees (3–5 recommended), each 18+ with full name, address, phone, email, occupation, date of birth, means of ID & passport photograph',
                'Chairman of Trustees and Secretary (may be chosen from the trustees)',
                'Minutes of meeting appointing trustees and approving name, trustees, objectives & constitution',
                'Constitution: name, objectives, membership rules, trustee powers & duties, meetings & quorum, finance, use of funds (non-profit clause), dissolution clause',
                'Physical registered address in Nigeria (no P.O. Box)',
                'CAC forms signed by Chairman & Secretary, and declaration of compliance (Form IT)',
                'Newspaper publication notice & CAC statutory fees (covered by your registration fee where agreed with our team)',
            ],
            'deliverables' => ['Certificate of Incorporation', 'Approved Constitution', 'Status report', 'Registered trustees list'],
        ];
    }

    public static function get(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }

    public static function fromSlug(string $slug): ?string
    {
        foreach (self::all() as $key => $def) {
            if ($def['slug'] === $slug) {
                return $key;
            }
        }

        return null;
    }

    /** Validation rules for a category's submission form. */
    public static function rules(string $key): array
    {
        $def = self::get($key);
        $kind = $def['kind'];
        $adultCutoff = Carbon::now()->subYears(18)->format('Y-m-d');

        $rules = [
            'names' => ['required', 'array', 'size:' . $def['names_count']],
            'names.*' => ['required', 'string', 'max:150'],
            'people' => ['required', 'array', 'min:' . $def['people_min'], 'max:15'],
            'people.*.phone' => ['required', 'string', 'max:20'],
            'people.*.email' => ['required', 'email', 'max:150'],
            'people.*.address' => ['required', 'string', 'max:300'],
            'people.*.dob' => ['required', 'date', 'before_or_equal:' . $adultCutoff],
        ];

        if ($kind === 'trustees') {
            $rules['names.*'][] = 'not_regex:/\b(limited|ltd)\b/i';
            $rules += [
                'people.*.full_name' => ['required', 'string', 'max:150'],
                'people.*.occupation' => ['required', 'string', 'max:150'],
                'people.*.id_type' => ['required', 'in:' . implode(',', array_keys(self::ID_TYPES))],
                'files.people.*.id_file' => ['required', ...self::IMAGE_DOC],
                'files.people.*.passport' => ['required', ...self::IMAGE_DOC],
                'objectives' => ['required', 'string', 'min:30', 'max:3000'],
                'registered_address' => ['required', 'string', 'max:300', 'not_regex:/p\.?\s*o\.?\s*box/i'],
                'chairman' => ['required', 'integer', 'min:0'],
                'secretary' => ['required', 'integer', 'min:0'],
                'files.minutes' => ['required', ...self::ANY_DOC],
                'files.constitution' => ['required', ...self::ANY_DOC],
                'files.cac_forms' => ['required', ...self::ANY_DOC],
            ];
        } else {
            $rules += [
                'people.*.first_name' => ['required', 'string', 'max:100'],
                'people.*.surname' => ['required', 'string', 'max:100'],
                'people.*.other_names' => ['nullable', 'string', 'max:100'],
                'people.*.nin' => ['required', 'digits:11'],
                'people.*.state_of_origin' => ['required', 'string', 'max:100'],
                'people.*.lga' => ['required', 'string', 'max:100'],
                'files.people.*.passport' => ['required', ...self::IMAGE_DOC],
                'files.people.*.nin_slip' => ['required', ...self::IMAGE_DOC],
                'files.people.*.signature' => ['required', ...self::IMAGE_DOC],
                'business_address' => ['required', 'string', 'max:300'],
                'business_type' => ['required', 'string', 'max:255'],
            ];

            if ($kind === 'shareholders') {
                $rules += [
                    'people.*.percent' => ['nullable', 'numeric', 'min:0.01', 'max:100'],
                    'files.people.*.id_card' => ['required', ...self::IMAGE_DOC],
                    'witness.name' => ['required', 'string', 'max:150'],
                    'witness.phone' => ['required', 'string', 'max:20'],
                    'witness.email' => ['required', 'email', 'max:150'],
                    'witness.address' => ['required', 'string', 'max:300'],
                    'files.witness_signature' => ['required', ...self::IMAGE_DOC],
                    'secretary_details.name' => ['nullable', 'string', 'max:150'],
                    'secretary_details.phone' => ['nullable', 'string', 'max:20'],
                    'secretary_details.email' => ['nullable', 'email', 'max:150'],
                    'secretary_details.address' => ['nullable', 'string', 'max:300'],
                ];
            }
        }

        return $rules;
    }

    /** Friendly field names for validation messages (people are numbered from 1). */
    public static function attributes(string $key): array
    {
        $def = self::get($key);
        $who = $def['people_label'];
        $attrs = [
            'names.0' => '1st name choice', 'names.1' => '2nd name choice',
            'business_address' => 'business address', 'business_type' => $def['kind'] === 'shareholders' ? 'nature of business' : 'type of business',
            'registered_address' => 'registered address', 'objectives' => 'objectives',
            'files.minutes' => 'minutes of meeting', 'files.constitution' => 'constitution', 'files.cac_forms' => 'signed CAC forms / Form IT',
            'files.witness_signature' => 'witness signature', 'witness.name' => 'witness name', 'witness.phone' => 'witness phone',
            'witness.email' => 'witness email', 'witness.address' => 'witness address',
        ];
        $fields = ['first_name' => 'first name', 'surname' => 'surname', 'full_name' => 'full name', 'phone' => 'phone number', 'email' => 'email',
            'address' => 'address', 'nin' => 'NIN', 'state_of_origin' => 'state of origin', 'lga' => 'LGA', 'dob' => 'date of birth',
            'occupation' => 'occupation', 'id_type' => 'means of ID', 'percent' => 'shareholding %'];
        $files = ['passport' => 'passport photograph', 'nin_slip' => 'NIN slip', 'signature' => 'signature', 'id_card' => 'ID card', 'id_file' => 'ID upload'];
        for ($i = 0; $i < 15; $i++) {
            foreach ($fields as $f => $l) {
                $attrs["people.$i.$f"] = "$who " . ($i + 1) . " $l";
            }
            foreach ($files as $f => $l) {
                $attrs["files.people.$i.$f"] = "$who " . ($i + 1) . " $l";
            }
        }

        return $attrs;
    }

    /** Human-readable slot labels for uploaded documents. */
    public static function slotLabel(string $slot): string
    {
        $base = preg_replace('/^(person_\d+_)/', '', $slot);
        $label = [
            'passport' => 'Passport photograph',
            'nin_slip' => 'NIN slip',
            'signature' => 'Signature',
            'id_card' => 'ID card',
            'id_file' => 'Means of ID',
            'minutes' => 'Minutes of meeting',
            'constitution' => 'Constitution',
            'cac_forms' => 'Signed CAC forms / Form IT',
            'witness_signature' => 'Witness signature',
        ][$base] ?? ucfirst(str_replace('_', ' ', $base));

        if (preg_match('/^person_(\d+)_/', $slot, $m)) {
            return $label . ' (#' . ((int) $m[1] + 1) . ')';
        }

        return $label;
    }
}
