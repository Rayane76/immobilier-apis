<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

/**
 * Mirrors the validate_property_attributes() Postgres trigger so clients get
 * clear validation errors before the DB round-trip.
 *
 * Checks performed (same order as the trigger):
 *  1. attributes must be an associative (non-list) array
 *  2. every is_required attribute must be present and non-null
 *  3. each present value must match the declared type
 *       string  → PHP string  + optional options enum
 *       integer → PHP int (or numeric string without decimal)
 *       decimal → PHP int|float|numeric string
 *       boolean → PHP bool
 *       + min_value / max_value range for integer / decimal
 *  4. keys not defined for this property_type are rejected
 */
class ValidPropertyAttributes implements ValidationRule
{
    /**
     * @param  int|null  $propertyTypeId  Resolved by the DTO's rules() method.
     */
    public function __construct(private readonly ?int $propertyTypeId) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Nothing to validate
        if ($value === null) {
            return;
        }

        // 1. Must be an associative array (JSON object), not a list
        if (!is_array($value) || array_is_list($value)) {
            $fail('The :attribute must be a JSON object (key-value pairs), not an array or scalar.');
            return;
        }

        if ($this->propertyTypeId === null) {
            // Cannot resolve the property type – skip deep validation.
            // The trigger will catch any violations at the DB level.
            return;
        }

        // Load attribute definitions once
        /** @var array<string, object> $definitions  keyed by attribute title */
        $definitions = DB::table('property_type_attributes as pta')
            ->join('attributes as a', function ($join) {
                $join->on('a.id', '=', 'pta.attribute_id')
                    ->whereNull('a.deleted_at');
            })
            ->where('pta.property_type_id', $this->propertyTypeId)
            ->select('a.title', 'a.type', 'a.options', 'a.min_value', 'a.max_value', 'pta.is_required')
            ->get()
            ->keyBy('title');

        // 2. Check all required attributes are present and non-null
        foreach ($definitions as $key => $def) {
            if ($def->is_required && (!array_key_exists($key, $value) || $value[$key] === null)) {
                $fail("The :attribute is missing the required attribute \"{$key}\".");
            }
        }

        // 3. Type-check each supplied value
        foreach ($value as $key => $val) {
            // 4. Reject unknown keys (checked inline to give per-key messages)
            if (!isset($definitions[$key])) {
                $fail("The :attribute contains an unknown key \"{$key}\" for this property type.");
                continue;
            }

            // Null values for optional fields are fine
            if ($val === null) {
                continue;
            }

            $def = $definitions[$key];

            match ($def->type) {
                'string'  => $this->validateString($key, $val, $def, $fail),
                'integer' => $this->validateInteger($key, $val, $def, $fail),
                'decimal' => $this->validateDecimal($key, $val, $def, $fail),
                'boolean' => $this->validateBoolean($key, $val, $fail),
                default   => $fail("Unknown attribute type \"{$def->type}\" for key \"{$key}\"."),
            };
        }
    }

    // -------------------------------------------------------------------------
    // Type validators
    // -------------------------------------------------------------------------

    private function validateString(string $key, mixed $val, object $def, Closure $fail): void
    {
        if (!is_string($val)) {
            $fail("The :attribute key \"{$key}\" must be a string.");
            return;
        }

        // Options enum check
        if (!empty($def->options)) {
            $options = is_string($def->options) ? json_decode($def->options, true) : $def->options;

            if (is_array($options) && count($options) > 0 && !in_array($val, $options, true)) {
                $list = implode(', ', array_map(fn($o) => "\"{$o}\"", $options));
                $fail("The :attribute key \"{$key}\" must be one of: {$list}.");
            }
        }
    }

    private function validateInteger(string $key, mixed $val, object $def, Closure $fail): void
    {
        // Accept PHP int or a numeric string with no decimal part
        $isInteger = is_int($val) || (is_string($val) && ctype_digit(ltrim($val, '-')));

        if (!$isInteger) {
            $fail("The :attribute key \"{$key}\" must be an integer.");
            return;
        }

        $this->validateRange($key, (float) $val, $def, $fail);
    }

    private function validateDecimal(string $key, mixed $val, object $def, Closure $fail): void
    {
        if (!is_numeric($val)) {
            $fail("The :attribute key \"{$key}\" must be a numeric value.");
            return;
        }

        $this->validateRange($key, (float) $val, $def, $fail);
    }

    private function validateBoolean(string $key, mixed $val, Closure $fail): void
    {
        if (!is_bool($val)) {
            $fail("The :attribute key \"{$key}\" must be a boolean (true/false).");
        }
    }

    private function validateRange(string $key, float $num, object $def, Closure $fail): void
    {
        if ($def->min_value !== null && $num < (float) $def->min_value) {
            $fail("The :attribute key \"{$key}\" must be at least {$def->min_value}.");
        }

        if ($def->max_value !== null && $num > (float) $def->max_value) {
            $fail("The :attribute key \"{$key}\" may not exceed {$def->max_value}.");
        }
    }
}
