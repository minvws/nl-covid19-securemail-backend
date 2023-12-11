<?php

declare(strict_types=1);

namespace MinVWS\MessagingApi\Service;

use Cake\Validation\Validator;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\Exception as CarbonException;
use MyCLabs\Enum\Enum;
use Selective\Validation\Converter\CakeValidationConverter;
use Selective\Validation\Exception\ValidationException;
use Selective\Validation\ValidationResult;
use UnexpectedValueException;

use function array_key_exists;

class ValidationService
{
    /**
     * @throws ValidationException
     */
    public function getDateValueByArray(array $values, string $name, bool $required = true): ?CarbonImmutable
    {
        $value = $this->getValueFromArray($values, $name, $required);

        if (!$required && $value === null) {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('c', $value);
        } catch (CarbonException) {
            $validationResult = new ValidationResult();
            $validationResult->addError($name, 'Invalid value');
            throw new ValidationException('Validation failed', $validationResult);
        }
    }

    /**
     * @throws ValidationException
     */
    public function getValueFromArray(array $values, string $name, bool $required = true)
    {
        $validator = new Validator();
        if (!$required && !array_key_exists($name, $values)) {
            return null;
        }

        $validator->requirePresence($name);
        $required ? $validator->notEmptyString($name) : $validator->allowEmptyString($name);

        $validation = CakeValidationConverter::createValidationResult($validator->validate($values));
        if ($validation->fails()) {
            throw new ValidationException('Validation failed', $validation);
        }

        return $values[$name];
    }

    /**
     * @throws ValidationException
     */
    public function getValueByTypeFromArray(array $values, string $name, string $type): Enum
    {
        try {
            return new $type($this->getValueFromArray($values, $name));
        } catch (UnexpectedValueException) {
            $validationResult = new ValidationResult();
            $validationResult->addError($name, 'Invalid value');
            throw new ValidationException('Validation failed', $validationResult);
        }
    }
}
