<?php

namespace App\Scribe;

use Knuckles\Camel\Extraction\ExtractedEndpointData;
use Knuckles\Scribe\Extracting\Strategies\Strategy;
use ReflectionClass;
use ReflectionNamedType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

/**
 * Scribe strategy that reads body parameters from Spatie Laravel Data classes.
 *
 * Scribe's built-in GetFromFormRequest only handles Illuminate\Foundation\Http\FormRequest
 * subclasses. This strategy fills the gap for controllers that type-hint a
 * Spatie\LaravelData\Data DTO instead.
 *
 * It inspects the constructor parameters of the Data class, maps PHP types to
 * Scribe types, and marks a parameter as optional when it:
 *  - has a default value, OR
 *  - is nullable, OR
 *  - is typed as T|Optional (Spatie's "sometimes" equivalent)
 *
 * Additionally, if the Data class defines a static rules() method, those rules
 * are merged in to capture extra constraints (e.g. file fields like main_image
 * that are not PHP constructor args but appear in rules()).
 */
class GetFromSpatieData extends Strategy
{
    public function __invoke(ExtractedEndpointData $endpointData, array $routeRules = []): ?array
    {
        // GET / HEAD parameters are handled by GetSpatieDataAsQueryParams.
        $httpMethods = array_map('strtoupper', $endpointData->httpMethods);
        if (array_intersect($httpMethods, ['GET', 'HEAD'])) {
            return null;
        }

        $method = $endpointData->method;

        foreach ($method->getParameters() as $param) {
            $type = $param->getType();

            if (!$type instanceof ReflectionNamedType) {
                continue;
            }

            $className = $type->getName();

            if (!class_exists($className)) {
                continue;
            }

            $reflection = new ReflectionClass($className);

            if (!$reflection->isSubclassOf(Data::class)) {
                continue;
            }

            return $this->extractFromDataClass($reflection);
        }

        return null;
    }

    private function extractFromDataClass(ReflectionClass $reflection): array
    {
        $constructor = $reflection->getConstructor();
        $params = [];

        if ($constructor) {
            foreach ($constructor->getParameters() as $param) {
                $type        = $param->getType();
                $required    = true;
                $scribeType  = 'string';
                $nullable    = false;

                if ($type instanceof ReflectionNamedType) {
                    $nullable   = $type->allowsNull();
                    $scribeType = $this->phpTypeToScribe($type->getName());
                }

                // Optional default value or nullable => not required
                if ($param->isOptional() || $nullable) {
                    $required = false;
                }

                // Spatie's |Optional union type => not required
                foreach ($param->getAttributes() as $attr) {
                    $name = $attr->getName();
                    if (str_ends_with($name, 'Sometimes') || str_ends_with($name, 'Nullable')) {
                        $required = false;
                    }
                }

                $params[$param->getName()] = [
                    'name'        => $param->getName(),
                    'description' => '',
                    'required'    => $required,
                    'example'     => $this->exampleForType($scribeType),
                    'type'        => $scribeType,
                ];
            }
        }

        // Merge any extra fields declared only in rules() (e.g. file uploads)
        if ($reflection->hasMethod('rules')) {
            try {
                $rules = $reflection->getMethod('rules')->invoke(null);

                foreach ($rules as $field => $ruleList) {
                    // Skip array wildcards like images.*
                    if (str_contains($field, '*')) {
                        continue;
                    }

                    if (isset($params[$field])) {
                        continue;
                    }

                    $ruleList  = is_array($ruleList) ? $ruleList : explode('|', $ruleList);
                    $required  = in_array('required', $ruleList, true);
                    $scribeType = $this->rulesTypeToScribe($ruleList);

                    $params[$field] = [
                        'name'        => $field,
                        'description' => '',
                        'required'    => $required,
                        'example'     => $this->exampleForType($scribeType),
                        'type'        => $scribeType,
                    ];
                }
            } catch (\Throwable) {
                // rules() may call request() or DB — silently skip on failure
            }
        }

        return $params;
    }

    private function phpTypeToScribe(string $type): string
    {
        return match ($type) {
            'int', 'integer'       => 'integer',
            'float', 'double'      => 'number',
            'bool', 'boolean'      => 'boolean',
            'array'                => 'object',
            default                => 'string',
        };
    }

    private function rulesTypeToScribe(array $rules): string
    {
        foreach ($rules as $rule) {
            if (!is_string($rule)) {
                continue;
            }
            if (in_array($rule, ['integer', 'numeric'], true)) {
                return 'integer';
            }
            if ($rule === 'boolean') {
                return 'boolean';
            }
            if ($rule === 'array') {
                return 'object';
            }
            if (in_array($rule, ['file', 'image'], true)) {
                return 'file';
            }
        }

        return 'string';
    }

    private function exampleForType(string $type): mixed
    {
        return null;
    }
}
