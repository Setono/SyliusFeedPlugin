<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Validator;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Item\Google\Availability;
use Setono\SyliusFeedPlugin\Item\Google\GoogleShoppingItem;
use Setono\SyliusFeedPlugin\Validator\FeedItemValidator;
use Setono\SyliusFeedPlugin\Validator\RequiredFieldsValidator;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Proves the typed-item validation of §11: a GoogleShoppingItem runs the Google Shopping constraint
 * mapping (missing required fields / over-long title produce violation messages), while a plain
 * FeedItem carries no constraints and only the generic required-field fallback fires.
 */
final class FeedItemValidatorTest extends TestCase
{
    private function feedItemValidator(): FeedItemValidator
    {
        return new FeedItemValidator($this->symfonyValidator(), new RequiredFieldsValidator());
    }

    private function symfonyValidator(): ValidatorInterface
    {
        return Validation::createValidatorBuilder()
            ->addXmlMapping(__DIR__ . '/../../../src/Resources/config/validation/GoogleShoppingItem.xml')
            ->getValidator()
        ;
    }

    private function context(): FeedContext
    {
        return new FeedContext(null, 'en_US', 'USD');
    }

    private function validGoogleShoppingItem(): GoogleShoppingItem
    {
        $item = new GoogleShoppingItem(new \stdClass(), $this->context());
        $item->setId('SKU-1');
        $item->setTitle('Acme Shoe');
        $item->setDescription('Very nice');
        $item->setLink('https://example.com/products/acme-shoe');
        $item->setImageLink('https://example.com/img/main.jpg');
        $item->setAvailability(Availability::IN_STOCK);
        $item->setPrice('9.99 USD');

        return $item;
    }

    /**
     * @test
     */
    public function it_reports_a_google_shopping_item_missing_its_title(): void
    {
        $item = $this->validGoogleShoppingItem();
        $item->remove(GoogleShoppingItem::TITLE);

        $messages = $this->feedItemValidator()->validate($item, []);

        self::assertContains('setono_sylius_feed.google_shopping_item.title.not_blank', $messages);
    }

    /**
     * @test
     */
    public function it_reports_a_google_shopping_item_with_a_too_long_title(): void
    {
        $item = $this->validGoogleShoppingItem();
        $item->setTitle(str_repeat('a', 151));

        $messages = $this->feedItemValidator()->validate($item, []);

        self::assertContains('setono_sylius_feed.google_shopping_item.title.max_length', $messages);
    }

    /**
     * @test
     */
    public function it_considers_a_fully_populated_google_shopping_item_valid(): void
    {
        self::assertSame([], $this->feedItemValidator()->validate($this->validGoogleShoppingItem(), []));
    }

    /**
     * @test
     */
    public function it_reports_a_missing_required_field_on_a_plain_feed_item_via_the_fallback(): void
    {
        $item = new FeedItem(new \stdClass(), $this->context());
        $item->set('g:id', 'SKU-1');

        $messages = $this->feedItemValidator()->validate($item, ['g:id', 'g:title']);

        self::assertSame(['setono_sylius_feed.validation.missing_field: g:title'], $messages);
    }

    /**
     * @test
     */
    public function it_skips_the_typed_constraints_when_the_format_opts_out(): void
    {
        // an empty validation-group list (a non-Google format) must not run the g:* constraints,
        // even though the item is a GoogleShoppingItem lacking every field
        $item = new GoogleShoppingItem(new \stdClass(), $this->context());

        self::assertSame([], $this->feedItemValidator()->validate($item, [], []));
    }

    /**
     * @test
     */
    public function it_still_runs_the_required_field_fallback_when_the_typed_constraints_are_skipped(): void
    {
        $item = new GoogleShoppingItem(new \stdClass(), $this->context());

        self::assertSame(
            ['setono_sylius_feed.validation.missing_field: g:id'],
            $this->feedItemValidator()->validate($item, ['g:id'], []),
        );
    }

    /**
     * @test
     */
    public function it_deduplicates_messages(): void
    {
        $item = $this->validGoogleShoppingItem();
        $item->remove(GoogleShoppingItem::TITLE);

        // both the typed NotBlank constraint and the required-field fallback flag g:title, but the
        // fallback message key differs, so the two are distinct; the typed message appears once
        $messages = $this->feedItemValidator()->validate($item, ['g:title']);

        self::assertSame(
            $messages,
            array_values(array_unique($messages)),
            'The returned messages must be deduplicated',
        );
    }
}
