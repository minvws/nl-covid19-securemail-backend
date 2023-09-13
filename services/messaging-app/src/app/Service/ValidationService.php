<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Service;

use Cake\Validation\Validator;
use Selective\Validation\Converter\CakeValidationConverter;
use Selective\Validation\Exception\ValidationException;

use function array_key_exists;

class ValidationService
{
    /**
     * @throws ValidationException
     */
    public function getValueFromArray(array $values, string $name, bool $required = true)
    {
        $validator = new Validator();
        if ($required) {
            $validator->requirePresence($name);
            $validator->notEmptyString($name);
        } elseif (!array_key_exists($name, $values)) {
            return null;
        }

        $validation = CakeValidationConverter::createValidationResult($validator->validate($values));
        if ($validation->fails()) {
            throw new ValidationException('Validation failed', $validation);
        }

        return $values[$name];
    }
}
