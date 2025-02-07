<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Specification\Vendor\Google\Shopping;

use Setono\SyliusFeedPlugin\Specification\Attribute\Specification as SpecificationAttribute;
use Setono\SyliusFeedPlugin\Specification\Specification;

/**
 * See https://support.google.com/merchants/answer/7052112?hl=en and https://developers.google.com/shopping-content/reference/rest/v2.1/products
 */
#[SpecificationAttribute('Google Shopping', 'xml')]
class Product extends Specification
{
    public ?string $id = null;

    public ?string $title = null;

    public ?string $description = null;

    public ?string $link = null;

    public ?string $imageLink = null;

    public ?string $availability = null;

    public ?string $price = null;

    public ?string $salePrice = null;

    public ?string $brand = null;

    public ?string $gtin = null;

    public ?string $mpn = null;

    public ?bool $identifierExists = null;

    public ?string $condition = null;

    public ?string $color = null;

    public ?string $size = null;

    public ?string $itemGroupId = null;
}
