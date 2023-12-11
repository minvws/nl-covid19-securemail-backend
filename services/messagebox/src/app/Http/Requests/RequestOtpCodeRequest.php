<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Enums\LoginType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RequestOtpCodeRequest extends FormRequest
{
    public const FIELD_LOGIN_TYPE = 'loginType';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            self::FIELD_LOGIN_TYPE => [
                'required',
                Rule::in(LoginType::allValues()),
            ],
        ];
    }

    public function getPostLoginType(): LoginType
    {
        return LoginType::forValue($this->post(self::FIELD_LOGIN_TYPE));
    }
}
