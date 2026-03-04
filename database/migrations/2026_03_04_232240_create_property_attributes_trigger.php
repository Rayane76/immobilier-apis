<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // -----------------------------------------------------------------
        // Function: validate_property_attributes
        //
        // Rules enforced on INSERT / UPDATE of properties.attributes or
        // properties.property_type_id:
        //
        //  1. attributes must be a JSON object (not array or scalar)
        //  2. every attribute marked is_required must be present & non-null
        //  3. each present value must match the declared type:
        //       string   → JSON string
        //       integer  → JSON number without decimal part
        //       decimal  → JSON number (integer or float)
        //       boolean  → JSON boolean
        //  4. keys not defined for this property_type are rejected
        //
        // Soft-deleted attributes (deleted_at IS NOT NULL) are excluded so
        // that retiring an attribute does not break existing properties.
        // -----------------------------------------------------------------
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION validate_property_attributes()
            RETURNS TRIGGER AS $$
            DECLARE
                v_key        TEXT;
                v_value      JSONB;
                v_def        RECORD;
            BEGIN
                -- 1. Null attributes are always valid
                IF NEW.attributes IS NULL THEN
                    RETURN NEW;
                END IF;

                -- 2. Must be a JSON object, not array/scalar
                IF jsonb_typeof(NEW.attributes) != 'object' THEN
                    RAISE EXCEPTION 'attributes must be a JSON object'
                        USING ERRCODE = 'check_violation';
                END IF;

                -- 3. Validate each attribute definition linked to this property type
                FOR v_def IN
                    SELECT
                        a.title   AS key,
                        a.type    AS type,
                        pta.is_required
                    FROM property_type_attributes pta
                    INNER JOIN attributes a
                        ON  a.id            = pta.attribute_id
                        AND a.deleted_at    IS NULL
                    WHERE pta.property_type_id = NEW.property_type_id
                LOOP
                    v_value := NEW.attributes -> v_def.key;

                    -- 3a. Required field missing or explicitly null
                    IF v_def.is_required
                        AND (v_value IS NULL OR v_value = 'null'::jsonb)
                    THEN
                        RAISE EXCEPTION 'Missing required attribute: %', v_def.key
                            USING ERRCODE = 'check_violation';
                    END IF;

                    -- 3b. Skip type check when the field is absent / null
                    CONTINUE WHEN v_value IS NULL OR v_value = 'null'::jsonb;

                    -- 3c. Type validation against the attributes.type enum
                    --     ('string', 'integer', 'decimal', 'boolean')
                    CASE v_def.type
                        WHEN 'string' THEN
                            IF jsonb_typeof(v_value) != 'string' THEN
                                RAISE EXCEPTION 'Attribute "%" must be a string, got: %',
                                    v_def.key, v_value
                                    USING ERRCODE = 'check_violation';
                            END IF;

                        WHEN 'integer' THEN
                            IF jsonb_typeof(v_value) != 'number'
                                OR v_value::text ~ '\.'
                            THEN
                                RAISE EXCEPTION 'Attribute "%" must be an integer, got: %',
                                    v_def.key, v_value
                                    USING ERRCODE = 'check_violation';
                            END IF;

                        WHEN 'decimal' THEN
                            IF jsonb_typeof(v_value) != 'number' THEN
                                RAISE EXCEPTION 'Attribute "%" must be a decimal number, got: %',
                                    v_def.key, v_value
                                    USING ERRCODE = 'check_violation';
                            END IF;

                        WHEN 'boolean' THEN
                            IF jsonb_typeof(v_value) != 'boolean' THEN
                                RAISE EXCEPTION 'Attribute "%" must be a boolean, got: %',
                                    v_def.key, v_value
                                    USING ERRCODE = 'check_violation';
                            END IF;

                        ELSE
                            RAISE EXCEPTION 'Unknown attribute type "%" for key "%"',
                                v_def.type, v_def.key
                                USING ERRCODE = 'check_violation';
                    END CASE;
                END LOOP;

                -- 4. Reject keys not defined for this property type
                FOR v_key IN
                    SELECT jsonb_object_keys(NEW.attributes)
                LOOP
                    IF NOT EXISTS (
                        SELECT 1
                        FROM property_type_attributes pta
                        INNER JOIN attributes a
                            ON  a.id            = pta.attribute_id
                            AND a.deleted_at    IS NULL
                        WHERE pta.property_type_id = NEW.property_type_id
                          AND a.title             = v_key
                    ) THEN
                        RAISE EXCEPTION 'Unknown attribute key "%" for this property type',
                            v_key
                            USING ERRCODE = 'check_violation';
                    END IF;
                END LOOP;

                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        SQL);

        // Fires only when the relevant columns actually change
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER trg_validate_property_attributes
            BEFORE INSERT OR UPDATE OF attributes, property_type_id
            ON properties
            FOR EACH ROW
            EXECUTE FUNCTION validate_property_attributes();
        SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_validate_property_attributes ON properties;');
        DB::unprepared('DROP FUNCTION IF EXISTS validate_property_attributes();');
    }
};
