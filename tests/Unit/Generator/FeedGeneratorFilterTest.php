<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Generator;

use Doctrine\Common\Collections\ArrayCollection;
use League\Csv\Reader;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\DataSource\DataSourceInterface;
use Setono\SyliusFeedPlugin\FeedType\FeedTypeInterface;
use Setono\SyliusFeedPlugin\FeedType\FeedTypeRegistryInterface;
use Setono\SyliusFeedPlugin\Filter\FilterEvaluator;
use Setono\SyliusFeedPlugin\Filter\FilterSet;
use Setono\SyliusFeedPlugin\Format\CsvFormat;
use Setono\SyliusFeedPlugin\Format\FormatRegistryInterface;
use Setono\SyliusFeedPlugin\Generator\FeedGenerator;
use Setono\SyliusFeedPlugin\Generator\FieldMappingEvaluator;
use Setono\SyliusFeedPlugin\Lookup\InMemoryLookup;
use Setono\SyliusFeedPlugin\Mapping\FieldDefinition;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\Mapping\ScopeDimension;
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
use Setono\SyliusFeedPlugin\Reference\ReferenceResolver;
use Setono\SyliusFeedPlugin\Scripting\ExpressionEvaluator;
use Setono\SyliusFeedPlugin\Scripting\FeedTemplateSecurityPolicy;
use Setono\SyliusFeedPlugin\Scripting\SandboxedTwigRenderer;
use Setono\SyliusFeedPlugin\Transformation\TransformationChain;
use Setono\SyliusFeedPlugin\Transformation\TransformationRegistry;
use Setono\SyliusFeedPlugin\Validator\RequiredFieldsValidator;
use Setono\SyliusFeedPlugin\ValueResolver\ValueResolverInterface;
use Setono\SyliusFeedPlugin\Writer\CsvWriter;
use Setono\SyliusFeedPlugin\Writer\FeedWriterRegistryInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

/**
 * The §11 acceptance for the FilterEvaluator wired into the generator: a source with an
 * exclude-out-of-stock filter drops the out-of-stock item from the output while keeping the
 * in-stock one — proving the filter is selective (not just "drops everything"). The same filter is
 * exercised at both the `pre` and `post` stage.
 */
final class FeedGeneratorFilterTest extends TestCase
{
    use ProphecyTrait;

    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem(new LocalFilesystemAdapter(sys_get_temp_dir() . '/setono-feed-' . bin2hex(random_bytes(6))));
    }

    protected function tearDown(): void
    {
        $this->filesystem->deleteDirectory('shop');
    }

    /**
     * @dataProvider stages
     *
     * @test
     */
    public function it_excludes_the_out_of_stock_item(string $stage): void
    {
        $result = $this->createGenerator()->generate($this->feed($stage), new FeedContext(null, 'en_US', 'USD'));

        self::assertSame(1, $result->itemCount);
        self::assertSame(1, $result->excludedCount);

        $rows = [...Reader::createFromString($this->filesystem->read($result->path))->getRecords()];
        self::assertCount(2, $rows, 'header + the single kept row');
        self::assertSame(['id', 'availability'], $rows[0]);
        self::assertSame(['SKU-1', 'in_stock'], $rows[1]);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public function stages(): iterable
    {
        yield 'pre' => [FeedFilterInterface::STAGE_PRE];
        yield 'post' => [FeedFilterInterface::STAGE_POST];
    }

    private function createGenerator(): FeedGenerator
    {
        $feedTypeRegistry = $this->prophesize(FeedTypeRegistryInterface::class);
        $feedTypeRegistry->get('product_variant')->willReturn($this->feedType());

        $presetRegistry = $this->prophesize(MappingPresetRegistryInterface::class);

        $formatRegistry = $this->prophesize(FormatRegistryInterface::class);
        $formatRegistry->get('csv')->willReturn(new CsvFormat());

        $writerRegistry = $this->prophesize(FeedWriterRegistryInterface::class);
        $writerRegistry->get('csv')->willReturn(new CsvWriter());

        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator->getContext()->willReturn(new RequestContext());

        return new FeedGenerator(
            $feedTypeRegistry->reveal(),
            $presetRegistry->reveal(),
            $formatRegistry->reveal(),
            $writerRegistry->reveal(),
            new FieldMappingEvaluator(
                new TransformationChain(new TransformationRegistry([])),
                new ReferenceResolver(),
                new OperatorRegistry([new IsTrue()]),
                new ExpressionEvaluator(new InMemoryLookup()),
                new SandboxedTwigRenderer(new FeedTemplateSecurityPolicy(), new InMemoryLookup()),
                new NullLookupReferenceResolver(),
            ),
            new FilterEvaluator(new ReferenceResolver(), new OperatorRegistry([new Equals()])),
            new NullLookupReferenceResolver(),
            new RequiredFieldsValidator(),
            new EventDispatcher(),
            $urlGenerator->reveal(),
            $this->filesystem,
        );
    }

    private function feedType(): FeedTypeInterface
    {
        $fields = [
            'id' => $this->field('id'),
            'availability' => $this->field('availability'),
        ];

        $dataSource = new class() implements DataSourceInterface {
            public function getResourceClass(): string
            {
                return \stdClass::class;
            }

            public function getItems(FeedContext $context, FilterSet $filters): iterable
            {
                yield (object) ['id' => 'SKU-1', 'availability' => 'in_stock'];
                yield (object) ['id' => 'SKU-2', 'availability' => 'out_of_stock'];
            }

            public function count(FeedContext $context, FilterSet $filters): int
            {
                return 2;
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

            public function getScopeDimensions(): array
            {
                return [ScopeDimension::CHANNEL, ScopeDimension::LOCALE, ScopeDimension::CURRENCY];
            }

            public function getAvailableFields(): array
            {
                return $this->fields;
            }
        };
    }

    /**
     * A field whose resolver reads the same-named property off the entity, so the two stub entities
     * resolve to different availability values.
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

    private function feed(string $stage): FeedInterface
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
            $this->feedField('availability', 'availability', 1),
        ]));

        $feed = $this->prophesize(FeedInterface::class);
        $feed->getCode()->willReturn('shop');
        $feed->getFormat()->willReturn('csv');
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
