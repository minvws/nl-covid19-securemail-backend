<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PostPairingCodeRenewRequest extends FormRequest
{
    public const FIELD_PAIRING_CODE_UUID = 'pairingCodeUuid';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            self::FIELD_PAIRING_CODE_UUID => [
                'required',
                'string',
            ],
        ];
    }

    public function getPostPairingCodeUuid(): string
    {
        return $this->post(self::FIELD_PAIRING_CODE_UUID);
    }
}
