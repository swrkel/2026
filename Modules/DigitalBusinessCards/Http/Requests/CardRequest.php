<?php

namespace Modules\DigitalBusinessCards\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\DigitalBusinessCards\Models\Card;

class CardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Ownership is enforced in the controller's query scope.
    }

    public function rules(): array
    {
        $cardId  = $this->route('card')?->getKey();
        $table   = config('digital-business-cards.table_prefix', 'dbc_').'cards';
        $maxKb   = (int) config('digital-business-cards.media.max_kilobytes', 4096);

        return [
            'slug' => [
                'nullable', 'string', 'max:120', 'alpha_dash',
                Rule::unique($table, 'slug')->ignore($cardId),
            ],

            'first_name'   => ['required', 'string', 'max:80'],
            'last_name'    => ['nullable', 'string', 'max:80'],
            'job_title'    => ['nullable', 'string', 'max:120'],
            'company'      => ['nullable', 'string', 'max:120'],
            'department'   => ['nullable', 'string', 'max:120'],

            'email'        => ['nullable', 'email:rfc', 'max:190'],
            'phone'        => ['nullable', 'string', 'max:40'],
            'phone_alt'    => ['nullable', 'string', 'max:40'],
            'website'      => ['nullable', 'url', 'max:190'],

            'address_line' => ['nullable', 'string', 'max:190'],
            'city'         => ['nullable', 'string', 'max:90'],
            'region'       => ['nullable', 'string', 'max:90'],
            'postal_code'  => ['nullable', 'string', 'max:24'],
            'country'      => ['nullable', 'string', 'max:90'],

            'bio'          => ['nullable', 'string', 'max:600'],
            'accent_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'theme'        => ['nullable', Rule::in(array_keys(Card::THEMES))],

            'links'            => ['nullable', 'array', 'max:8'],
            'links.*.label'    => ['nullable', 'string', 'max:40'],
            'links.*.url'      => ['nullable', 'url', 'max:190'],

            'photo'        => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', "max:{$maxKb}"],
            'logo'         => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', "max:{$maxKb}"],

            'is_published' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'accent_color.regex' => 'Use a six-digit hex colour, like #2F6F62.',
            'slug.alpha_dash'    => 'The card address can contain letters, numbers and dashes only.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_published' => $this->boolean('is_published'),
            'slug'         => $this->filled('slug') ? trim((string) $this->input('slug')) : null,
        ]);
    }

    /** Only the columns that belong on the model — no files, no _token. */
    public function cardAttributes(): array
    {
        $data = $this->safe()->except(['photo', 'logo', 'links']);

        $data['links'] = collect($this->input('links', []))
            ->filter(fn ($l) => ! empty($l['url']))
            ->map(fn ($l) => ['label' => $l['label'] ?? null, 'url' => $l['url']])
            ->values()
            ->all();

        if (blank($data['slug'] ?? null)) {
            unset($data['slug']);
        }

        return $data;
    }
}
