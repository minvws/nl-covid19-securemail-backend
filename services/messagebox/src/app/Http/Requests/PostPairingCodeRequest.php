<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PostPairingCodeRequest extends FormRequest
{
    public const FIELD_PAIRING_CODE = 'pairingCode';
    public const FIELD_EMAIL_ADDRESS = 'emailAddress';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            self::FIELD_PAIRING_CODE => [
                'required',
                'string',
                'between:6,7',
            ],
            self::FIELD_EMAIL_ADDRESS => [
                'required',
                'email:filter',

            ],
        ];
    }

    public function getPostPairingCode(): string
    {
        return $this->post(self::FIELD_PAIRING_CODE);
    }

    public function getPostEmailAddress(): string
    {
        return $this->post(self::FIELD_EMAIL_ADDRESS);
    }
}
