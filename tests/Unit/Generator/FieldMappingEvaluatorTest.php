<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Generator;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Generator\FieldMappingEvaluator;
use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Lookup\InMemoryLookup;
use Setono\SyliusFeedPlugin\Lookup\LookupReferenceResolver;
use Setono\SyliusFeedPlugin\Lookup\LookupReferenceResolverInterface;
use Setono\SyliusFeedPlugin\Mapping\FieldDefinition;
use Setono\SyliusFeedPlugin\Mapping\FieldMapping;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\Model\LookupTable;
use Setono\SyliusFeedPlugin\Operator\Equals;
use Setono\SyliusFeedPlugin\Operator\IsTrue;
use Setono\SyliusFeedPlugin\Operator\OperatorRegistry;
use Setono\SyliusFeedPlugin\Reference\ReferenceResolver;
use Setono\SyliusFeedPlugin\Repository\LookupTableRepositoryInterface;
use Setono\SyliusFeedPlugin\Scripting\ExpressionEvaluator;
use Setono\SyliusFeedPlugin\Scripting\FeedTemplateSecurityPolicy;
use Setono\SyliusFeedPlugin\Scripting\SandboxedTwigRenderer;
use Setono\SyliusFeedPlugin\Transformation\Concat;
use Setono\SyliusFeedPlugin\Transformation\TransformationChain;
use Setono\SyliusFeedPlugin\Transformation\TransformationRegistry;

/**
 * Integration of the reference-resolution rule, the shared operator vocabulary and the scripting
 * surfaces into the per-field pipeline (§10): source resolution (field/literal/expression/twig),
 * the transformation chain, and the emit condition — all against a single item.
 */
final class FieldMappingEvaluatorTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_resolves_a_field_source_through_the_bound_source_resolver(): void
    {
        $item = $this->item();

        $this->evaluator()->apply($item, [FieldMapping::field('g:title', 'title')], $this->fields(['title' => 'Acme Shoe']));

        self::assertSame('Acme Shoe', $item->get('g:title'));
    }

    /**
     * The exact §10 case: a field is joined with another source field, resolved on demand by name.
     *
     * @test
     */
    public function it_concatenates_the_value_with_another_source_field(): void
    {
        $item = $this->item();
        $mapping = FieldMapping::field('g:title', 'title')->transform(Concat::of(['value', 'brand'], ' '));

        $this->evaluator()->apply($item, [$mapping], $this->fields(['title' => 'Running Shoe', 'brand' => 'Acme']));

        self::assertSame('Running Shoe Acme', $item->get('g:title'));
    }

    /**
     * @test
     */
    public function it_resolves_a_reference_to_an_earlier_output_field(): void
    {
        $item = $this->item();
        $mappings = [
            FieldMapping::field('g:brand', 'brand'),
            FieldMapping::field('g:title', 'title')->transform(Concat::of(['value', 'g:brand'], ' by ')),
        ];

        $this->evaluator()->apply($item, $mappings, $this->fields(['title' => 'Shoe', 'brand' => 'Acme']));

        self::assertSame('Shoe by Acme', $item->get('g:title'));
    }

    /**
     * @test
     */
    public function it_gates_emission_with_the_only_if_shorthand(): void
    {
        $mapping = FieldMapping::field('g:item_group_id', 'group')->onlyIf('is_configurable');

        $emitted = $this->item();
        $this->evaluator()->apply($emitted, [$mapping], $this->fields(['group' => 'PROD-1', 'is_configurable' => true]));
        self::assertSame('PROD-1', $emitted->get('g:item_group_id'));

        $suppressed = $this->item();
        $this->evaluator()->apply($suppressed, [$mapping], $this->fields(['group' => 'PROD-1', 'is_configurable' => false]));
        self::assertFalse($suppressed->has('g:item_group_id'));
    }

    /**
     * @test
     */
    public function it_gates_emission_with_the_full_operator_vocabulary(): void
    {
        $mapping = FieldMapping::literal('g:sale', 'yes')->when('availability', 'equals', "'in_stock'");

        $emitted = $this->item();
        $this->evaluator()->apply($emitted, [$mapping], $this->fields(['availability' => 'in_stock']));
        self::assertSame('yes', $emitted->get('g:sale'));

        $suppressed = $this->item();
        $this->evaluator()->apply($suppressed, [$mapping], $this->fields(['availability' => 'out_of_stock']));
        self::assertFalse($suppressed->has('g:sale'));
    }

    /**
     * @test
     */
    public function it_resolves_an_expression_source_against_the_item(): void
    {
        $item = $this->item();

        $this->evaluator()->apply($item, [FieldMapping::expression('g:answer', '40 + 2')], $this->fields([]));

        self::assertSame(42, $item->get('g:answer'));
    }

    /**
     * @test
     */
    public function it_resolves_a_twig_source_against_the_entity(): void
    {
        $item = new FeedItem(new class() {
            public function getName(): string
            {
                return 'widget';
            }
        }, new FeedContext());

        $this->evaluator()->apply($item, [FieldMapping::twig('g:label', '{{ entity.getName()|upper }}')], $this->fields([]));

        self::assertSame('WIDGET', $item->get('g:label'));
    }

    /**
     * The M4 acceptance: a title with a per-item lookup value appended, working end to end through
     * the mapping pipeline. Here the lookup is reached via the `lookup()` scripting function (the
     * `lookup:{table}:{column}` source-reference form, which needs the LookupTable's `joinField`,
     * lands with that resource in M5).
     *
     * @test
     */
    public function it_appends_a_lookup_value_to_a_title_end_to_end(): void
    {
        $lookup = new InMemoryLookup();
        $lookup->addTable('badges', ['SKU-1' => ['badge' => 'Bestseller']]);

        $item = $this->item();
        $mapping = FieldMapping::twig('g:title', '{{ "Acme Shoe" }} {{ lookup("badges", "SKU-1", "badge") }}');

        $this->evaluator($lookup)->apply($item, [$mapping], $this->fields([]));

        self::assertSame('Acme Shoe Bestseller', $item->get('g:title'));
    }

    /**
     * The exact §10.1 case: a title joined with a per-item lookup value resolved via a
     * `lookup:{code}:{column}` source reference, keyed off the table's joinField.
     *
     * @test
     */
    public function it_resolves_a_lookup_source_reference_via_the_join_field(): void
    {
        $table = new LookupTable();
        $table->setCode('badges');
        $table->setJoinField('code');
        $table->setRows(['SKU-1' => ['badge' => 'Bestseller']]);

        $repository = $this->prophesize(LookupTableRepositoryInterface::class);
        $repository->findOneByCode('badges')->willReturn($table);

        $item = $this->item();
        $mapping = FieldMapping::field('g:title', 'title')->transform(Concat::of(['value', 'lookup:badges:badge'], ' '));

        $this->evaluator(null, new LookupReferenceResolver($repository->reveal()))
            ->apply($item, [$mapping], $this->fields(['title' => 'Acme Shoe', 'code' => 'SKU-1']));

        self::assertSame('Acme Shoe Bestseller', $item->get('g:title'));
    }

    private function evaluator(?InMemoryLookup $lookup = null, ?LookupReferenceResolverInterface $lookupReferenceResolver = null): FieldMappingEvaluator
    {
        $lookup ??= new InMemoryLookup();
        $referenceResolver = new ReferenceResolver();

        return new FieldMappingEvaluator(
            new TransformationChain(new TransformationRegistry([new Concat($referenceResolver)])),
            $referenceResolver,
            new OperatorRegistry([new IsTrue(), new Equals()]),
            new ExpressionEvaluator($lookup),
            new SandboxedTwigRenderer(new FeedTemplateSecurityPolicy(), $lookup),
            $lookupReferenceResolver ?? new NullLookupReferenceResolver(),
        );
    }

    private function item(): FeedItem
    {
        return new FeedItem(new \stdClass(), new FeedContext());
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return array<string, FieldDefinition>
     */
    private function fields(array $values): array
    {
        $fields = [];
        foreach ($values as $name => $value) {
            $fields[$name] = new FieldDefinition(
                $name,
                'test.' . $name,
                FieldType::STRING,
                new FixedValueResolver($name, FieldType::STRING, $value),
            );
        }

        return $fields;
    }
}
