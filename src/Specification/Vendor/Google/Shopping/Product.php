<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Specification\Vendor\Google\Shopping;

use Setono\SyliusFeedPlugin\Specification\Attribute\Specification as SpecificationAttribute;
use Setono\SyliusFeedPlugin\Specification\Specification;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * See https://support.google.com/merchants/answer/7052112?hl=en and https://developers.google.com/shopping-content/reference/rest/v2.1/products
 */
#[SpecificationAttribute('Google Shopping', 'xml')]
class Product extends Specification
{
    /**
     * See https://support.google.com/merchants/answer/6324405
     *
     * Use the ID [id] attribute to uniquely identify each product. The ID won’t be shown to customers who view your
     * products online. However, you can use the ID to look up your product, place bids, and check a product's performance.
     * We recommend that you use your product SKU for this value.
     */
    #[SerializedName('g:id')]
    public ?string $id = null;

    /**
     * See https://support.google.com/merchants/answer/6324415
     *
     * Use one of the title [title] and structured title [structured_title] attributes to clearly identify the product you are selling.
     * The title is one of the most prominent parts of your ad or free listing. A specific and accurate title will help us show your product to the right customers.
     */
    #[SerializedName('g:title')]
    public ?string $title = null;

    /**
     * See https://support.google.com/merchants/answer/6324468
     *
     * Use one of the description [description] and structured description [structured_description] attributes to tell customers about your product.
     * List product features, technical specifications, and visual attributes. A detailed description will help us show your product to the right customers.
     */
    #[SerializedName('g:description')]
    public ?string $description = null;

    /**
     * See https://support.google.com/merchants/answer/6324416
     *
     * When users click on your product, they’re sent to a landing page on your website for that product.
     * Set the URL for this landing page with the link [link] attribute.
     */
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
