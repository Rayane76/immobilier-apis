<?php

namespace App\Scribe;

use Knuckles\Camel\Extraction\ExtractedEndpointData;
use Knuckles\Scribe\Extracting\Strategies\Strategy;
use ReflectionClass;
use ReflectionNamedType;
use Spatie\LaravelData\Data;

/**
 * Scribe strategy that reads QUERY parameters from Spatie Laravel Data classes
 * on GET / HEAD endpoints.
 *
 * The companion strategy (GetFromSpatieData) handles POST / PATCH / PUT body
 * parameters. This one runs in the queryParameters pipeline and only fires
 * when the route's primary HTTP method is GET or HEAD.
 */
class GetSpatieDataAsQueryParams extends Strategy
{
    public function __invoke(ExtractedEndpointData $endpointData, array $routeRules = []): ?array
    {
        // Only apply to read (non-mutating) routes.
        $methods = array_map('strtoupper', $endpointData->httpMethods);
        if (!array_intersect($methods, ['GET', 'HEAD'])) {
            return null;
        }

        $method = $endpointData->method;
        if (!$method) {
            return null;
        }

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

            return $this->extractQueryParamsFromDataClass($reflection);
        }

        return null;
    }

    private function extractQueryParamsFromDataClass(ReflectionClass $reflection): array
    {
        $constructor = $reflection->getConstructor();
        $params      = [];

        if ($constructor) {
            foreach ($constructor->getParameters() as $param) {
                $type       = $param->getType();
                $scribeType = 'string';
                $nullable   = false;
                $required   = true;

                if ($type instanceof ReflectionNamedType) {
                    $nullable   = $type->allowsNull();
                    $scribeType = $this->phpTypeToScribe($type->getName());
                }

                // A default value or nullable type means optional.
                if ($param->isOptional() || $nullable) {
                    $required = false;
                }

                // Read docblock comment from the constructor parameter itself.
                $description = $this->docCommentForParam($reflection, $param->getName());

                // Use default value as example if it's a scalar.
                $example = null;
                if ($param->isOptional()) {
                    $default = $param->getDefaultValue();
                    if (is_scalar($default)) {
                        $example = $default;
                    }
                }

                $params[$param->getName()] = [
                    'name'        => $param->getName(),
                    'description' => $description,
                    'required'    => $required,
                    'example'     => $example,
                    'type'        => $scribeType,
                ];
            }
        }

        return $params;
    }

    private function docCommentForParam(ReflectionClass $reflection, string $paramName): string
    {
        $constructor = $reflection->getConstructor();
        if (!$constructor) {
            return '';
        }

        $docComment = $constructor->getDocComment();
        if (!$docComment) {
            return '';
        }

        // Try to match a @param or inline /** … */ comment above the property.
        if (preg_match('/@param\s+\S+\s+\$' . preg_quote($paramName) . '\s+(.+)/', $docComment, $m)) {
            return trim($m[1]);
        }

        return '';
    }

    private function phpTypeToScribe(string $type): string
    {
        return match ($type) {
            'int', 'integer'  => 'integer',
            'float', 'double' => 'number',
            'bool', 'boolean' => 'boolean',
            'array'           => 'object',
            default           => 'string',
        };
    }
}
