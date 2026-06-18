<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Item\Google;

use Setono\SyliusFeedPlugin\Item\FeedItem;

/**
 * A typed view over the generic {@see FeedItem} bag for Google Shopping feeds (§4.2): named,
 * autocomplete-friendly accessors that read/write the same underlying bag keys, so known fields
 * get typed access and `$item instanceof GoogleShoppingItem` works in listeners, while custom
 * fields remain reachable through the generic bag API.
 *
 * Enum-typed values (availability, condition) are stored in the bag as their scalar `value`, so
 * writers keep serializing the bag generically.
 */
final class GoogleShoppingItem extends FeedItem
{
    public const ID = 'g:id';

    public const ITEM_GROUP_ID = 'g:item_group_id';

    public const TITLE = 'g:title';

    public const DESCRIPTION = 'g:description';

    public const LINK = 'g:link';

    public const IMAGE_LINK = 'g:image_link';

    public const ADDITIONAL_IMAGE_LINK = 'g:additional_image_link';

    public const AVAILABILITY = 'g:availability';

    public const PRICE = 'g:price';

    public const SALE_PRICE = 'g:sale_price';

    public const CONDITION = 'g:condition';

    public const BRAND = 'g:brand';

    public const GTIN = 'g:gtin';

    public function getId(): ?string
    {
        return $this->getString(self::ID);
    }

    public function setId(string $id): void
    {
        $this->set(self::ID, $id);
    }

    public function getItemGroupId(): ?string
    {
        return $this->getString(self::ITEM_GROUP_ID);
    }

    public function setItemGroupId(string $itemGroupId): void
    {
        $this->set(self::ITEM_GROUP_ID, $itemGroupId);
    }

    public function getTitle(): ?string
    {
        return $this->getString(self::TITLE);
    }

    public function setTitle(string $title): void
    {
        $this->set(self::TITLE, $title);
    }

    public function getDescription(): ?string
    {
        return $this->getString(self::DESCRIPTION);
    }

    public function setDescription(string $description): void
    {
        $this->set(self::DESCRIPTION, $description);
    }

    public function getLink(): ?string
    {
        return $this->getString(self::LINK);
    }

    public function setLink(string $link): void
    {
        $this->set(self::LINK, $link);
    }

    public function getImageLink(): ?string
    {
        return $this->getString(self::IMAGE_LINK);
    }

    public function setImageLink(string $imageLink): void
    {
        $this->set(self::IMAGE_LINK, $imageLink);
    }

    public function getPrice(): ?string
    {
        return $this->getString(self::PRICE);
    }

    public function setPrice(string $price): void
    {
        $this->set(self::PRICE, $price);
    }

    public function getSalePrice(): ?string
    {
        return $this->getString(self::SALE_PRICE);
    }

    public function setSalePrice(string $salePrice): void
    {
        $this->set(self::SALE_PRICE, $salePrice);
    }

    public function getBrand(): ?string
    {
        return $this->getString(self::BRAND);
    }

    public function setBrand(string $brand): void
    {
        $this->set(self::BRAND, $brand);
    }

    public function getGtin(): ?string
    {
        return $this->getString(self::GTIN);
    }

    public function setGtin(string $gtin): void
    {
        $this->set(self::GTIN, $gtin);
    }

    public function getAvailability(): ?Availability
    {
        $value = $this->get(self::AVAILABILITY);

        return is_string($value) ? Availability::tryFrom($value) : null;
    }

    public function setAvailability(Availability $availability): void
    {
        $this->set(self::AVAILABILITY, $availability->value);
    }

    public function getCondition(): ?Condition
    {
        $value = $this->get(self::CONDITION);

        return is_string($value) ? Condition::tryFrom($value) : null;
    }

    public function setCondition(Condition $condition): void
    {
        $this->set(self::CONDITION, $condition->value);
    }

    private function getString(string $field): ?string
    {
        $value = $this->get($field);

        return is_string($value) ? $value : null;
    }
}
