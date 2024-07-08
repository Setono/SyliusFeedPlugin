<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Feed\Model\Google\Shopping;

use Webmozart\Assert\Assert;

class Product
{
    private ?string $id = null;

    private ?string $title = null;

    private ?string $description = null;

    private ?string $link = null;

    private ?string $imageLink = null;

    private array $additionalImageLinks = [];

    private ?Availability $availability = null;

    private ?Price $price = null;

    private ?Price $salePrice = null;

    private ?DateRange $salePriceEffectiveDate = null;

    private ?string $brand = null;

    private ?string $gtin = null;

    private ?string $mpn = null;

    private ?bool $identifierExists = null;

    private ?Condition $condition = null;

    private ?string $itemGroupId = null;

    private ?string $googleProductCategory = null;

    private ?string $productType = null;

    private ?string $shipping = null;

    private ?string $size = null;

    private ?string $color = null;

    private array $customLabels = [];

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function setLink(?string $link): void
    {
        $this->link = $link;
    }

    public function getImageLink(): ?string
    {
        return $this->imageLink;
    }

    public function setImageLink(?string $imageLink): void
    {
        $this->imageLink = $imageLink;
    }

    public function getAdditionalImageLinks(): array
    {
        return $this->additionalImageLinks;
    }

    public function setAdditionalImageLinks(array $additionalImageLinks): void
    {
        $this->additionalImageLinks = $additionalImageLinks;
    }

    public function addAdditionalImageLink(string $additionalImageLink): void
    {
        $this->additionalImageLinks[] = $additionalImageLink;
    }

    public function hasAdditionalImageLinks(): bool
    {
        return count($this->additionalImageLinks) > 0;
    }

    public function getAvailability(): ?Availability
    {
        return $this->availability;
    }

    public function setAvailability(?Availability $availability): void
    {
        $this->availability = $availability;
    }

    public function getPrice(): ?Price
    {
        return $this->price;
    }

    public function setPrice(?Price $price): void
    {
        $this->price = $price;
    }

    public function getSalePrice(): ?Price
    {
        return $this->salePrice;
    }

    public function setSalePrice(?Price $salePrice): void
    {
        $this->salePrice = $salePrice;
    }

    public function getSalePriceEffectiveDate(): ?DateRange
    {
        return $this->salePriceEffectiveDate;
    }

    public function setSalePriceEffectiveDate(?DateRange $salePriceEffectiveDate): void
    {
        $this->salePriceEffectiveDate = $salePriceEffectiveDate;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function setBrand(?string $brand): void
    {
        $this->brand = $brand;
    }

    public function getGtin(): ?string
    {
        return $this->gtin;
    }

    public function setGtin(?string $gtin): void
    {
        $this->gtin = $gtin;
    }

    public function getMpn(): ?string
    {
        return $this->mpn;
    }

    public function setMpn(?string $mpn): void
    {
        $this->mpn = $mpn;
    }

    public function getIdentifierExists(): ?bool
    {
        return $this->identifierExists;
    }

    public function setIdentifierExists(?bool $identifierExists): void
    {
        $this->identifierExists = $identifierExists;
    }

    public function getCondition(): ?Condition
    {
        return $this->condition;
    }

    public function setCondition(?Condition $condition): void
    {
        $this->condition = $condition;
    }

    public function getItemGroupId(): ?string
    {
        return $this->itemGroupId;
    }

    public function setItemGroupId(?string $itemGroupId): void
    {
        $this->itemGroupId = $itemGroupId;
    }

    public function getGoogleProductCategory(): ?string
    {
        return $this->googleProductCategory;
    }

    public function setGoogleProductCategory(?string $googleProductCategory): void
    {
        $this->googleProductCategory = $googleProductCategory;
    }

    public function getProductType(): ?string
    {
        return $this->productType;
    }

    public function setProductType(?string $productType): void
    {
        $this->productType = $productType;
    }

    public function getShipping(): ?string
    {
        return $this->shipping;
    }

    public function setShipping(?string $shipping): void
    {
        $this->shipping = $shipping;
    }

    public function getSize(): ?string
    {
        return $this->size;
    }

    public function setSize(?string $size): void
    {
        $this->size = $size;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): void
    {
        $this->color = $color;
    }

    public function getCustomLabels(): array
    {
        return $this->customLabels;
    }

    public function setCustomLabel(string $label, int $index): void
    {
        Assert::greaterThanEq($index, 0);
        Assert::lessThanEq($index, 4);

        $this->customLabels[$index] = $label;
    }
}
