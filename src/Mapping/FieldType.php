<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Mapping;

/**
 * The type of an available source field. Drives how the mapping UI renders the
 * field and how writers/transformations treat the value.
 */
enum FieldType: string
{
    case STRING = 'string';
    case INTEGER = 'integer';
    case DECIMAL = 'decimal';
    case MONEY = 'money';
    case DATE = 'date';
    case BOOL = 'bool';
    case URL = 'url';
    case IMAGE = 'image';
    case COLLECTION = 'collection';
}
