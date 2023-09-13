<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UnlinkByUuidRequest extends FormRequest
{
    public const FIELD_MESSAGE_UUID = 'messageUuid';
    public const FIELD_REASON = 'reason';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            self::FIELD_MESSAGE_UUID => [
                'required',
                'string',
            ],
            self::FIELD_REASON => [
                'required',
                'string',
            ],
        ];
    }

    public function getPostMessageUuid(): string
    {
        return $this->post(self::FIELD_MESSAGE_UUID);
    }

    public function getPostReason(): string
    {
        return $this->post(self::FIELD_REASON);
    }
}
