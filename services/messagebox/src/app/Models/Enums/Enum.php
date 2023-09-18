<?php

declare(strict_types=1);

namespace App\Models\Enums;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use InvalidArgumentException;
use JsonSerializable;
use stdClass;
use Stringable;

use function array_filter;
use function array_Key_exists;
use function array_keys;
use function array_map;
use function array_values;
use function call_user_func;
use function class_exists;
use function collect;
use function debug_backtrace;
use function get_object_vars;
use function in_array;
use function is_int;
use function is_string;
use function trigger_error;

use const E_USER_NOTICE;

/**
 * Base class for enums.
 *
 * @property-read string|int $value
 * @property-read string $label
 * @property-read array $children
 */
abstract class Enum implements Castable, JsonSerializable, Stringable
{
    private static array $byValue = [];
    private static array $byName = [];

    /**
     * @var Enum|null
     */
    private ?Enum $_parent = null;

    /**
     * @var static[]
     */
    private array $_children = [];

    /**
     * @var string|int|null
     */
    private $_value;

    /**
     * @var string
     */
    private string $_label;

    /**
     * @var array
     */
    private array $_properties;

    /**
     * Returns the enum schema.
     *
     * @return object
     */
    abstract protected static function enumSchema(): object;

    /**
     * Returns this enum's schema definition.
     *
     * TODO: Should be a more formal object.
     *
     * @return object
     */
    final public static function getSchema(): object
    {
        return static::enumSchema();
    }

    /**
     * We keep a static cache with all possible items so comparisons
     * can be made using instances instead having to compare the value.
     */
    private static function init(): void
    {
        if (isset(static::$byValue[static::class])) {
            return;
        }

        static::$byValue[static::class] = [];
        static::$byName[static::class] = [];

        $rawItems = static::enumSchema()->items;
        static::registerItems($rawItems);
    }

    /**
     * Create enum item for the given raw item.
     *
     * @param object $rawItem
     *
     * @return static
     */
    private static function createItem(object $rawItem): Enum
    {
        $value = $rawItem->value ?? null; // can be null for items that can't be selected but might act as a parent
        $label = $rawItem->label;

        $properties = [];
        foreach (get_object_vars(static::enumSchema()->properties ?? new stdClass()) as $propertyName => $propertyData) {
            if (!in_array($propertyData->scope ?? 'shared', ['php', 'shared'])) {
                continue;
            }

            $propertyValue = $rawItem->$propertyName ?? null;
            $propertyEnumClass = __NAMESPACE__ . '\\' . $propertyData->phpType;
            if (class_exists($propertyEnumClass)) {
                $propertyValue = $propertyEnumClass::forValue($propertyValue);
            }
            $properties[$propertyName] = $propertyValue;
        }

        return new static($value, $label, $properties);
    }

    /**
     * Register enum items for the given raw items.
     *
     * Optionally adding them as children for the given parent.
     *
     * @param array $rawItems
     * @param Enum|null $parent
     */
    private static function registerItems(array $rawItems, ?Enum $parent = null): void
    {
        foreach ($rawItems as $rawItem) {
            $item = static::createItem($rawItem);

            if ($parent !== null) {
                $item->_parent = $parent;
                $parent->children[] = $item;
            }

            if (isset($rawItem->items)) {
                static::registerItems($rawItem->items, $item);
            }

            if (isset($rawItem->value)) {
                static::$byValue[static::class][$rawItem->value] = $item;
            }

            $name = $rawItem->name ?? $rawItem->value ?? null;
            if (isset($name)) {
                static::$byName[static::class][$name] = $item;
            }
        }
    }

    /**
     * Returns all enum items.
     *
     * return static[]
     */
    public static function all(): array
    {
        static::init();
        return array_values(static::$byValue[static::class]);
    }

    /**
     * Returns all valid values.
     */
    public static function allValues(): array
    {
        static::init();

        return array_keys(static::$byValue[static::class]);
    }

    public static function allValuesForProperty(string $property): array
    {
        static::init();

        return collect(static::all())->map(fn($enum) => $enum->$property)->toArray();
    }

    /**
     * Returns the default item for this enum (if any).
     *
     * Can be null.
     *
     * @return static|null
     */
    public static function defaultItem(): ?Enum
    {
        $defaultValue = static::enumSchema()->default ?? null;
        return isset($defaultValue) ? static::forValue($defaultValue) : null;
    }

    /**
     * Returns the tsConst name from the schema (if any).
     *
     * Can be null.
     *
     * @return string|null
     */
    public static function tsConst(): ?string
    {
        return static::enumSchema()->tsConst ?? null;
    }

    /**
     * Returns the enum item for the given value.
     *
     * @param mixed $value
     * @param bool $exceptionForInvalidValue Throws an InvalidArgumentException for an invalid value,
     *                                              null is returned if set to false.
     *
     * @return static|null Item for the given value or null if null given.
     *
     * @throws InvalidArgumentException If the value is not a valid value.
     */
    final public static function forValue($value, bool $exceptionForInvalidValue = true): ?Enum
    {
        if (!is_string($value) && !is_int($value)) {
            return null;
        }

        static::init();

        if ($exceptionForInvalidValue && !array_key_exists($value, static::$byValue[static::class])) {
            throw new InvalidArgumentException('Invalid value "' . $value . '"');
        }

        return static::$byValue[static::class][$value] ?? null;
    }

    /**
     * @param int|string $value
     */
    final public static function forValueByProperty($value, string $property): ?self
    {
        static::init();

        return collect(static::all())->first(function (Enum $enum) use ($value, $property) {
            return $enum->$property === $value;
        });
    }

    /**
     * Returns the enum items for the given value.
     *
     * @param string[] $values Values.
     * @param bool $exceptionForInvalidValue Throws an InvalidArgumentException for an invalid value,
     *                                           element is filtered if set to false.
     *
     * @return static[] Enum items for the given values.
     *
     * @throws InvalidArgumentException If the array contains an invalid value.
     */
    final public static function forValues(array $values, bool $exceptionForInvalidValue = true): array
    {
        return array_filter(
            array_map(
                fn ($v) => static::forValue($v, $exceptionForInvalidValue),
                $values
            ),
            fn ($o) => $o !== null
        );
    }

    /**
     * Constructor.
     *
     * @param string|int|null $value Item value. Value is null for items that can't be selected.
     * @param string $label Label.
     * @param array $properties Extra properties.
     */
    final private function __construct($value, string $label, array $properties)
    {
        $this->_value = $value;
        $this->_label = $label;
        $this->_properties = $properties;
    }

    /**
     * Return if the given property is set.
     *
     * @param string $name
     *
     * @return bool
     */
    public function __isset(string $name): bool
    {
        return
            in_array($name, ['value', 'label', 'parent', 'children']) ||
            isset($this->_properties[$name]) ||
            (array_key_exists($name, static::enumSchema()->traitProperties ?? []) && isset($this->$name));
    }

    /**
     * Returns the value for the given property or null if it doesn't exist.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function __get(string $name)
    {
        if (in_array($name, ['value', 'label', 'parent', 'children'])) {
            return $this->{"_$name"};
        } elseif (array_key_exists($name, $this->_properties)) {
            return $this->_properties[$name];
        } elseif (array_key_exists($name, static::enumSchema()->traitProperties ?? [])) {
            $propertyData = static::enumSchema()->traitProperties->$name;
            /** @phpstan-ignore-next-line */
            return call_user_func([$this, $propertyData->method]);
        } else {
            $trace = debug_backtrace();
            trigger_error(
                'Undefined property via __get(): ' . $name .
                ' in ' . $trace[0]['file'] .
                ' on line ' . $trace[0]['line'],
                E_USER_NOTICE
            );
        }
    }

    /**
     * Returns the enum value with the given name.
     *
     * @param string $name Enum value name.
     * @param array $args Arguments (ignored).
     *
     * @return static
     *
     * @throws InvalidArgumentException If the name is not a valid name.
     */
    public static function __callStatic(string $name, array $args): Enum
    {
        static::init();

        if (!array_Key_exists($name, static::$byName[static::class])) {
            throw new InvalidArgumentException('Invalid name "' . $name . '" for "' . static::class . '"');
        }

        return static::$byName[static::class][$name];
    }

    /**
     * Get the caster class to use when casting from / to this cast target.
     */
    public static function castUsing(array $arguments): CastsAttributes
    {
        return new class (static::class) implements CastsAttributes {
            public function __construct(
                private readonly string $class
            ) {
            }

            public function get($model, $key, $value, $attributes)
            {
                /** @phpstan-ignore-next-line */
                return call_user_func([$this->class, 'forValue'], $value);
            }

            public function set($model, $key, $value, $attributes)
            {
                return $value->value ?? null;
            }
        };
    }

    /**
     * Serialize to JSON.
     *
     * @return mixed|string|null
     */
    public function jsonSerialize()
    {
        return $this->_value;
    }

    /**
     * String representation of value.
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->_value;
    }
}
