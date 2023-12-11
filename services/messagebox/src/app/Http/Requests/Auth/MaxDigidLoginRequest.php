<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class MaxDigidLoginRequest extends FormRequest
{
    private const FIELD_CODE = 'code';
    private const FIELD_STATE = 'state';

    public function rules(): array
    {
        return [
            self::FIELD_CODE => [
                'string',
                'required'
            ],
            self::FIELD_STATE => [
                'string',
                'required'
            ],
        ];
    }

    public function getCode(): string
    {
        return $this->get(self::FIELD_CODE);
    }

    public function getState(): string
    {
        return $this->get(self::FIELD_STATE);
    }
}
