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

    /**
     * See https://support.google.com/merchants/answer/6324350
     *
     * Include the URL for your main product image with the image link [image_link] attribute.
     * This image appears to potential customers in ads and free listings for your product.
     */
    #[SerializedName('g:image_link')]
    public ?string $imageLink = null;

    /**
     * See https://support.google.com/merchants/answer/6324448
     *
     * Use the availability [availability] attribute to tell users and Google whether you have a product in stock.
     */
    #[SerializedName('g:availability')]
    public ?Availability $availability = null;

    /**
     * Use the availability date [availability_date] attribute to tell customers
     * when a preordered or backordered product will be shipped.
     */
    #[SerializedName('g:availability_date')]
    public ?DateTimeImmutable $availabilityDate = null;

    /**
     * Use the cost of goods sold [cost_of_goods_sold] attribute when reporting
     * conversions with cart data to get additional reporting on gross profit.
     */
    #[SerializedName('g:cost_of_goods_sold')]
    public ?Money $costOfGoodsSold = null;

    /**
     * See https://support.google.com/merchants/answer/6324499
     *
     * Use the expiration date [expiration_date] attribute to stop showing a product on a specific date.
     * For example, you can use this attribute for limited stock or seasonal products.
     */
    #[SerializedName('g:expiration_date')]
    public ?DateTimeImmutable $expirationDate = null;

    /**
     * See https://support.google.com/merchants/answer/6324371
     * Use the price [price] attribute to tell users how much you’re charging for your product. This information is shown to users.
     */
    #[SerializedName('g:price')]
    public ?Money $price = null;

    /**
     * See https://support.google.com/merchants/answer/6324471
     *
     * Use the sale price [sale_price] attribute to tell customers how much you charge for your product during a sale.
     * During a sale, your sale price is shown as the current price. If the original price and sale price meet certain requirements,
     * the original price may show along with the sale price, so people can view the difference between prices.
     * In addition to that, a sale price annotation may also be added to highlight that the product is on sale.
     */
    #[SerializedName('g:sale_price')]
    public ?Money $salePrice = null;

    /**
     * See https://support.google.com/merchants/answer/6324460
     *
     * Use the sale price effective date [sale_price_effective_date] attribute to tell us
     * how long you want a specific sale price to be shown to users.
     */
    #[SerializedName('g:sale_price_effective_date')]
    public ?DateTimeImmutable $salePriceEffectiveDate = null;

    /**
     * See https://support.google.com/merchants/answer/6324436
     *
     * All products are automatically assigned a product category from Google’s continuously evolving product taxonomy.
     * Providing high-quality, on-topic titles and descriptions, as well as accurate pricing, brand,
     * and GTIN information will help ensure your products are correctly categorized.
     *
     * The Google product category [google_product_category] attribute can be used
     * to override Google’s automatic categorization in specific cases.
     */
    #[SerializedName('g:google_product_category')]
    public ?string $googleProductCategory = null;

    /**
     * See https://support.google.com/merchants/answer/6324406
     *
     * Use the product type [product_type] attribute to include your own product categorization system in your product data.
     * Unlike the Google product category [google_product_category] attribute, which uses a collection of
     * predefined categories, you choose which value to include for product type.
     * The values you submit can be used to organize the bidding and reporting in your Google Ads Shopping campaign.
     */
    #[SerializedName('g:product_type')]
    public ?string $productType = null;

    /**
     * See https://support.google.com/merchants/answer/6324351
     *
     * Use the brand [brand] attribute to indicate the product's brand name. The brand is used to help identify your
     * product and will be shown to customers. The brand should be clearly visible as an integral part of the packaging
     * or label, and not artificially added in the product image.
     */
    public ?string $brand = null;

    /**
     * See https://support.google.com/merchants/answer/6324461
     *
     * Use the GTIN [gtin] attribute to submit Global Trade Item Numbers (GTINs). A GTIN helps us make your ad or
     * unpaid listing easier for your customers to find. Products submitted without any unique product identifiers
     * are difficult to classify and may not be eligible for all Shopping programs or features.
     *
     * Some products don't have a GTIN assigned, and so you don't need to submit one.However, if the product does have
     * a GTIN assigned, and you don't submit it, the performance of the product could be significantly limited
     */
    public ?string $gtin = null;

    /**
     * See https://support.google.com/merchants/answer/6324482
     *
     * Use the MPN [mpn] attribute to submit your product’s Manufacturer Part Number (MPN). MPNs are used to uniquely
     * identify a specific product among all products from the same manufacturer. Shoppers might search specifically for
     * an MPN, so providing this attribute can help them find your listing.
     */
    public ?string $mpn = null;

    /**
     * See https://support.google.com/merchants/answer/6324478
     *
     * Use the identifier exists [identifier_exists] attribute to indicate that unique product identifiers (UPIs) aren’t
     * available for your product. Unique product identifiers are submitted using the GTIN [gtin], MPN [mpn], and brand [brand] attributes.
     */
    #[SerializedName('g:identifier_exists')]
    public ?bool $identifierExists = null;

    public ?string $condition = null;

    public ?string $color = null;

    public ?string $size = null;

    public ?string $itemGroupId = null;
}
