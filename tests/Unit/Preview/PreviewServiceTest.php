<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Preview;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\DataSource\DataSourceInterface;
use Setono\SyliusFeedPlugin\FeedType\FeedTypeInterface;
use Setono\SyliusFeedPlugin\FeedType\FeedTypeRegistryInterface;
use Setono\SyliusFeedPlugin\Filter\FilterEvaluator;
use Setono\SyliusFeedPlugin\Filter\FilterSet;
use Setono\SyliusFeedPlugin\Format\FormatInterface;
use Setono\SyliusFeedPlugin\Format\FormatRegistryInterface;
use Setono\SyliusFeedPlugin\Generator\FieldMappingEvaluator;
use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Lookup\InMemoryLookup;
use Setono\SyliusFeedPlugin\Mapping\FieldDefinition;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\Mapping\MappingResolver;
use Setono\SyliusFeedPlugin\MappingPreset\MappingPresetRegistryInterface;
use Setono\SyliusFeedPlugin\Model\FeedField;
use Setono\SyliusFeedPlugin\Model\FeedFieldInterface;
use Setono\SyliusFeedPlugin\Model\FeedFilter;
use Setono\SyliusFeedPlugin\Model\FeedFilterInterface;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Model\FeedSourceInterface;
use Setono\SyliusFeedPlugin\Operator\Equals;
use Setono\SyliusFeedPlugin\Operator\IsTrue;
use Setono\SyliusFeedPlugin\Operator\OperatorRegistry;
use Setono\SyliusFeedPlugin\Preview\PreviewService;
use Setono\SyliusFeedPlugin\Reference\ReferenceResolver;
use Setono\SyliusFeedPlugin\Scripting\ExpressionEvaluator;
use Setono\SyliusFeedPlugin\Scripting\FeedTemplateSecurityPolicy;
use Setono\SyliusFeedPlugin\Scripting\SandboxedTwigRenderer;
use Setono\SyliusFeedPlugin\Tests\Unit\Generator\NullLookupReferenceResolver;
use Setono\SyliusFeedPlugin\Transformation\TransformationChain;
use Setono\SyliusFeedPlugin\Transformation\TransformationRegistry;
use Setono\SyliusFeedPlugin\Validator\FeedItemValidator;
use Setono\SyliusFeedPlugin\Validator\RequiredFieldsValidator;
use Setono\SyliusFeedPlugin\ValueResolver\ValueResolverInterface;
use Setono\SyliusFeedPlugin\Writer\CsvWriterConfig;
use Setono\SyliusFeedPlugin\Writer\WriterConfigInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Validator\Validation;

/**
 * Runs the preview over a stub feed type + data source (mirroring the generator tests) to prove the
 * dry-run pipeline reports the same include/exclude decisions the generator makes — the funnel
 * counts, the mapped output of included items, and the reason each excluded item was dropped —
 * without writing anything.
 */
final class PreviewServiceTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_reports_the_funnel_and_the_included_sample(): void
    {
        $service = $this->createPreviewService($this->catalog());

        $result = $service->preview($this->feed(), new FeedContext(null, 'en_US', 'USD'));

        // three sampled → one dropped by the pre-filter, one dropped by validation (empty id) → one kept
        self::assertSame(3, $result->funnel->source);
        self::assertSame(2, $result->funnel->afterPreFilters);
        self::assertSame(1, $result->funnel->afterMappingValidation);
        self::assertSame(1, $result->funnel->afterPostFilters);
        self::assertSame(1, $result->funnel->included);

        self::assertCount(1, $result->included);
        self::assertSame(['id' => 'SKU-1', 'title' => 'Shoe', 'availability' => 'in_stock'], $result->included[0]);

        $reasons = array_column($result->excluded, 'reason');
        self::assertContains('filter:pre:availability', $reasons);
        self::assertNotEmpty(array_filter($reasons, static fn (string $reason): bool => str_starts_with($reason, 'validation:')));
    }

    /**
     * @test
     */
    public function it_bounds_the_sample_to_the_limit(): void
    {
        $entities = [];
        for ($i = 1; $i <= 5; ++$i) {
            $entities[] = (object) ['id' => 'SKU-' . $i, 'title' => 'Product ' . $i, 'availability' => 'in_stock'];
        }

        $service = $this->createPreviewService($entities);

        $result = $service->preview($this->feed(), new FeedContext(null, 'en_US', 'USD'), 2);

        self::assertSame(2, $result->funnel->source, 'only the first $limit items per source are sampled');
        self::assertSame(2, $result->funnel->included);
        self::assertCount(2, $result->included);
    }

    /**
     * @test
     */
    public function it_previews_a_single_included_item_by_id(): void
    {
        $service = $this->createPreviewService($this->catalog());

        $verdict = $service->previewItem($this->feed(), new FeedContext(null, 'en_US', 'USD'), 'SKU-1');

        self::assertTrue($verdict['included']);
        self::assertNull($verdict['reason']);
        self::assertSame('SKU-1', $verdict['output']['id']);
    }

    /**
     * @test
     */
    public function it_reports_the_rule_that_drops_a_single_item(): void
    {
        $service = $this->createPreviewService($this->catalog());

        $verdict = $service->previewItem($this->feed(), new FeedContext(null, 'en_US', 'USD'), 'SKU-2');

        self::assertFalse($verdict['included']);
        self::assertSame('filter:pre:availability', $verdict['reason']);
    }

    /**
     * @test
     */
    public function it_reports_not_found_for_an_unknown_id(): void
    {
        $service = $this->createPreviewService($this->catalog());

        $verdict = $service->previewItem($this->feed(), new FeedContext(null, 'en_US', 'USD'), 'DOES-NOT-EXIST');

        self::assertFalse($verdict['included']);
        self::assertSame('not_found', $verdict['reason']);
        self::assertSame([], $verdict['output']);
    }

    /**
     * @return list<object>
     */
    private function catalog(): array
    {
        return [
            (object) ['id' => 'SKU-1', 'title' => 'Shoe', 'availability' => 'in_stock'],
            (object) ['id' => 'SKU-2', 'title' => 'Boot', 'availability' => 'out_of_stock'],
            (object) ['id' => '', 'title' => 'Ghost', 'availability' => 'in_stock'],
        ];
    }

    /**
     * @param list<object> $entities
     */
    private function createPreviewService(array $entities): PreviewService
    {
        $feedTypeRegistry = $this->prophesize(FeedTypeRegistryInterface::class);
        $feedTypeRegistry->get('product_variant')->willReturn($this->feedType($entities));

        $presetRegistry = $this->prophesize(MappingPresetRegistryInterface::class);

        $formatRegistry = $this->prophesize(FormatRegistryInterface::class);
        $formatRegistry->get('test_format')->willReturn($this->format());

        return new PreviewService(
            $feedTypeRegistry->reveal(),
            new MappingResolver($presetRegistry->reveal()),
            $formatRegistry->reveal(),
            new FieldMappingEvaluator(
                new TransformationChain(new TransformationRegistry([])),
                new ReferenceResolver(),
                new OperatorRegistry([new Equals(), new IsTrue()]),
                new ExpressionEvaluator(new InMemoryLookup()),
                new SandboxedTwigRenderer(new FeedTemplateSecurityPolicy(), new InMemoryLookup()),
                new NullLookupReferenceResolver(),
            ),
            new FilterEvaluator(new ReferenceResolver(), new OperatorRegistry([new Equals()])),
            new NullLookupReferenceResolver(),
            new FeedItemValidator(Validation::createValidator(), new RequiredFieldsValidator()),
            new EventDispatcher(),
        );
    }

    /**
     * @param list<object> $entities
     */
    private function feedType(array $entities): FeedTypeInterface
    {
        $fields = [
            'id' => $this->field('id'),
            'title' => $this->field('title'),
            'availability' => $this->field('availability'),
        ];

        $dataSource = new class($entities) implements DataSourceInterface {
            /**
             * @param list<object> $entities
             */
            public function __construct(private readonly array $entities)
            {
            }

            public function getResourceClass(): string
            {
                return \stdClass::class;
            }

            public function getItems(FeedContext $context, FilterSet $filters): iterable
            {
                yield from $this->entities;
            }

            public function count(FeedContext $context, FilterSet $filters): int
            {
                return count($this->entities);
            }
        };

        return new class($dataSource, $fields) implements FeedTypeInterface {
            /**
             * @param array<string, FieldDefinition> $fields
             */
            public function __construct(
                private readonly DataSourceInterface $dataSource,
                private readonly array $fields,
            ) {
            }

            public function getCode(): string
            {
                return 'product_variant';
            }

            public function getLabel(): string
            {
                return 'test.product_variant';
            }

            public function getDataSource(): DataSourceInterface
            {
                return $this->dataSource;
            }

            public function createItem(object $entity, FeedContext $context): FeedItem
            {
                return new FeedItem($entity, $context);
            }

            public function getScopeDimensions(): array
            {
                return [];
            }

            public function getAvailableFields(): array
            {
                return $this->fields;
            }
        };
    }

    /**
     * A field whose resolver reads the same-named property off the entity, so each stub entity
     * resolves to its own values.
     */
    private function field(string $name): FieldDefinition
    {
        $resolver = new class($name) implements ValueResolverInterface {
            public function __construct(private readonly string $name)
            {
            }

            public function getName(): string
            {
                return $this->name;
            }

            public function getLabel(): string
            {
                return 'test.' . $this->name;
            }

            public function getType(): FieldType
            {
                return FieldType::STRING;
            }

            public function supports(string $resourceClass): bool
            {
                return true;
            }

            public function resolve(object $entity, FeedContext $context): mixed
            {
                return ((array) $entity)[$this->name] ?? null;
            }
        };

        return new FieldDefinition($name, $resolver->getLabel(), FieldType::STRING, $resolver);
    }

    private function format(): FormatInterface
    {
        return new class() implements FormatInterface {
            public function getCode(): string
            {
                return 'test_format';
            }

            public function getWriter(): string
            {
                return 'csv';
            }

            public function getConfig(): WriterConfigInterface
            {
                return new CsvWriterConfig();
            }

            public function getRequiredFields(): array
            {
                return ['id'];
            }

            public function getItemValidationGroups(): array
            {
                return [];
            }
        };
    }

    private function feed(string $stage = FeedFilterInterface::STAGE_PRE): FeedInterface
    {
        $filter = new FeedFilter();
        $filter->setField('availability');
        $filter->setOperator('equals');
        $filter->setValue("'out_of_stock'");
        $filter->setAction(FeedFilterInterface::ACTION_EXCLUDE);
        $filter->setStage($stage);

        $source = $this->prophesize(FeedSourceInterface::class);
        $source->getFeedType()->willReturn('product_variant');
        $source->getPosition()->willReturn(0);
        $source->getFilters()->willReturn(new ArrayCollection([$filter]));
        $source->getFields()->willReturn(new ArrayCollection([
            $this->feedField('id', 'id', 0),
            $this->feedField('title', 'title', 1),
            $this->feedField('availability', 'availability', 2),
        ]));

        $feed = $this->prophesize(FeedInterface::class);
        $feed->getCode()->willReturn('shop');
        $feed->getFormat()->willReturn('test_format');
        $feed->getSources()->willReturn(new ArrayCollection([$source->reveal()]));

        return $feed->reveal();
    }

    private function feedField(string $outputField, string $sourceField, int $position): FeedFieldInterface
    {
        $field = new FeedField();
        $field->setOutputField($outputField);
        $field->setSourceType('field');
        $field->setSourceValue($sourceField);
        $field->setPosition($position);

        return $field;
    }
}
