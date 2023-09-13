<?php

declare(strict_types=1);

ini_set('display_errors', '1');

function safeName(string $value): string
{
    return lcfirst(
        str_replace(
            [" ", "-", "_", "\t", "\r", "\n", "\f", "\v"],
            '',
            ucwords($value, " -_\t\r\n\f\v")
        )
    );
}

/**
 * @throws Exception
 */
function safeNames(array $items, object $schema): void
{
    foreach ($items as $item) {
        if (empty($item->name)) {
            if (!is_string($item->value)) {
                throw new Exception('name-property should be present if value is not a string');
            }

            $item->name = safeName($item->value);
        }

        if (($schema->tree ?? false) && is_array($item->items ?? null)) {
            safeNames($item->items, $schema);
        }
    }
}

function addPhpTypeToSchemaProperties(object $schema): void
{
    foreach ($schema->properties ?? [] as $name => $data) {
        $phpType = $data->type;

        $refSchemaPath = __DIR__ . "/{$data->type}.json";
        if (file_exists($refSchemaPath)) {
            $refSchema = loadSchema($refSchemaPath);
            $phpType = $refSchema->phpClass;
        }

        $data->phpType = $phpType;
    }
}

/**
 * @throws Exception
 */
function loadSchema(string $path): object
{
    $content = file_get_contents($path);

    if ($content === false) {
        throw new Exception(sprintf('Schema not found for: %s', $path));
    }

    $schema = json_decode($content);
    if ($schema === null) {
        die('Invalid schema file: ' . $path . '!');
    }

    safeNames($schema->items, $schema);
    addPhpTypeToSchemaProperties($schema);

    return $schema;
}

function getPhpStaticMethodsForItems(array $items, object $schema, int $level = 0): array
{
    $staticMethods = [];

    foreach ($items as $item) {
        // NOTE: the double {$item->name}() is intentional, PHPStorm needs the method name twice;
        //       first with only the name, second time with arguments (which we don't have)
        //       else the documentation for the method is parsed incorrectly
        $staticMethods[] = " * " . str_repeat(" ", 2 * $level) . "@method static {$schema->phpClass} {$item->name}() {$item->name}() {$item->label}";

        if (($schema->tree ?? false) && is_array($item->items ?? null)) {
            $childStaticMethods = getPhpStaticMethodsForItems($item->items, $schema, $level + 1);
            $staticMethods = array_merge($staticMethods, $childStaticMethods);
        }
    }

    return $staticMethods;
}

function generatePhpClass(object $schema, string $filename): string
{
    $staticMethods = getPhpStaticMethodsForItems($schema->items, $schema);
    $properties = [];

    $scalarType = property_exists($schema, 'scalarType') ? $schema->scalarType : 'string';
    $properties[] = "\n * @property-read $scalarType \$value";

    foreach ($schema->properties ?? [] as $name => $data) {
        if (in_array($data->scope ?? "shared", ["php", "shared"])) {
            $description = $data->description ?? '';
            $properties[] = " * @property-read {$data->phpType} \${$name} {$description}";
        }
    }

    foreach ($schema->traitProperties ?? [] as $name => $data) {
        $description = $data->description ?? '';
        $properties[] = " * @property-read {$data->type} \${$name} {$description}";
    }

    if (isset($schema->tree) && isset($schema->phpClass)) {
        $properties[] = " * @property-read {$schema->phpClass}|null \$parent Parent.";
        $properties[] = " * @property-read {$schema->phpClass}[] \$children Children.";
    }

    $declarations = implode("\n", array_merge($staticMethods, $properties));

    $traits = '';
    foreach ($schema->traits ?? [] as $trait) {
        $traits .= "    use {$trait};\n";
    }

    $template = file_get_contents(__DIR__ . '/Enum.php.tpl');

    if ($template === false) {
        return '';
    }

    $schemaExport =
        trim(
            implode(
                "\n",
                array_map(
                    fn ($l) => str_repeat(' ', 8) . $l,
                    explode("\n", var_export($schema, true))
                )
            )
        );

    return str_replace(
        ['[class]', '[description]', '[declarations]', '[traits]', '[schema]', '[filename]'],
        [$schema->phpClass, $schema->description ?? '', $declarations, $traits, $schemaExport, $filename],
        $template
    );
}

function typeScriptEnumValue(object $schema, object $item): string
{
    return ucfirst($schema->tsConst) . '.VALUE_' . str_replace('-', '_', strval($item->value));
}

function generateTypeScriptItems(object $schema, array $properties): array
{
    $data = [];
    foreach ($schema->items as $item) {
        if (count($properties) > 0 || ($schema->tree ?? false)) {
            $entry = ['label' => $item->label, 'value' => typeScriptEnumValue($schema, $item)];

            foreach ($properties as $propertyName) {
                $entry[$propertyName] = $item->$propertyName ?? null;
            }

            if (($schema->tree ?? false) && isset($item->items)) {
                $entry['items'] = generateTypeScriptItems($schema, $properties);
            }

            $data[] = $entry;
        } else {
            $data['[' . typeScriptEnumValue($schema, $item) . ']'] = $item->label;
        }
    }

    return $data;
}

function generateTypeScriptEnum(object $schema): string
{
    $data = [];
    foreach ($schema->items as $item) {
        $data[] = "  'VALUE_" . str_replace('-', '_', strval($item->value)) . "' = '" . $item->value . "',";
    }

    return ucfirst($schema->tsConst) . " {\n" . implode("\n", $data) . "\n}";
}

function generateTypeScriptData(object $schema): array
{
    $properties = [];
    foreach (get_object_vars($schema->properties ?? new stdClass()) as $name => $def) {
        if (!isset($def->scope) || $def->scope === 'shared' || $def->scope == 'ts') {
            $properties[] = $name;
        }
    }

    return generateTypeScriptItems($schema, $properties);
}

function generateTypeScriptCode(object $schema, string $filename): string
{
    $data = generateTypeScriptData($schema);
    $enum = generateTypeScriptEnum($schema);
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    // Since we generate typescript, and not json, lets REMOVE THE QUOTES from the generated enum values
    // ie. replace "[BCOPhaseV1.VALUE_1a]" with [BCOPhaseV1.VALUE_1a]
    $options = preg_replace('/"(\[?[^"]*?\.VALUE_[^"]*?\]?)"/', '$1', strval($json));
    $template = file_get_contents(__DIR__ . '/Enum.ts.tpl');

    if ($template === false) {
        return '';
    }

    return str_replace(
        ['[name]', '[description]', '[options]', '[filename]', '[enum]'],
        [$schema->tsConst, $schema->description ?? '', $options, $filename, $enum],
        $template
    );
}

function generateTypeScriptAllEnumsType(array $tsConsts): string
{
    $code = "/**\n * *** WARNING ***\n * This code is auto-generated. Any changes will be reverted by generating the schema!\n */\n\n";
    $allEnums = [];
    foreach ($tsConsts as $tsConst) {
        $code .= "import { " . ucfirst($tsConst) . " } from './" . $tsConst . "';\n";
        array_push($allEnums, ucfirst($tsConst));
    }
    $code .= "\nexport type AllEnums = " . implode(" | ", $allEnums) . ";";
    return $code;
}

$tsSnippets = [];

$schemaPaths = glob(__DIR__ . '/*.json');

if ($schemaPaths === false) {
    echo 'No JSON files found';
    return;
}

foreach ($schemaPaths as $schemaPath) {
    $schema = loadSchema($schemaPath);

    $filename = basename($schemaPath);

    if (isset($schema->phpClass)) {
        echo "Generate PHP class for $filename\n";

        $phpCode = generatePhpClass($schema, $filename);

        $classPath = __DIR__ . '/../app/Models/Enums/' . $schema->phpClass . '.php';
        if (file_exists($classPath) && md5_file($classPath) === md5($phpCode)) {
            echo "Skipping, code has not changed!\n";
        } else {
            file_put_contents($classPath, $phpCode);
            echo "PHP class stored in $classPath\n";
        }
    }

    if (isset($schema->tsConst)) {
        echo "Generate TypeScript code for $filename\n";
        $tsSnippets[$schema->tsConst] = generateTypeScriptCode($schema, $filename);
    }

    echo "---\n";
}

foreach ($tsSnippets as $file => $tsCode) {
    $tsPath = __DIR__ . '/../resources/js/types/enums/' . $file . '.ts';

    if (file_exists($tsPath) && md5_file($tsPath) === md5($tsCode)) {
        echo "Don't store TypeScript code, code has not changed!\n";
    } else {
        echo "Store TypeScript code in $tsPath\n";
        file_put_contents($tsPath, $tsCode);
    }
}

$tsConsts = array_keys($tsSnippets);
asort($tsConsts);
$tsBarrel = generateTypeScriptAllEnumsType($tsConsts);
$allTsPath = __DIR__ . '/../resources/js/types/enums/allEnums.ts';
if (file_exists($allTsPath) && md5_file($allTsPath) === md5($tsBarrel)) {
    echo "Don't store TypeScript code, code has not changed!\n";
} else {
    echo "Store TypeScript code in $allTsPath\n";
    file_put_contents($allTsPath, $tsBarrel);
}
