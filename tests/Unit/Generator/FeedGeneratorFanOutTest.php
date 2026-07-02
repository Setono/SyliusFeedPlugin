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
use Setono\SyliusFeedPlugin\Generator\ChunkRange;
use Setono\SyliusFeedPlugin\Generator\FeedGenerator;
use Setono\SyliusFeedPlugin\Generator\FieldMappingEvaluator;
use Setono\SyliusFeedPlugin\Generator\OutputWriter;
use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Lookup\InMemoryLookup;
use Setono\SyliusFeedPlugin\Mapping\FieldDefinition;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\Mapping\MappingResolver;
use Setono\SyliusFeedPlugin\Mapping\ScopeDimension;
use Setono\SyliusFeedPlugin\MappingPreset\MappingPresetRegistryInterface;
use Setono\SyliusFeedPlugin\Model\FeedField;
use Setono\SyliusFeedPlugin\Model\FeedFieldInterface;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Model\FeedSourceInterface;
use Setono\SyliusFeedPlugin\Operator\Equals;
use Setono\SyliusFeedPlugin\Operator\IsTrue;
use Setono\SyliusFeedPlugin\Operator\OperatorRegistry;
use Setono\SyliusFeedPlugin\Reference\ReferenceResolver;
use Setono\SyliusFeedPlugin\Scripting\ExpressionEvaluator;
use Setono\SyliusFeedPlugin\Scripting\FeedTemplateSecurityPolicy;
use Setono\SyliusFeedPlugin\Scripting\SandboxedTwigRenderer;
use Setono\SyliusFeedPlugin\Transformation\MoneyFormat;
use Setono\SyliusFeedPlugin\Transformation\StripTags;
use Setono\SyliusFeedPlugin\Transformation\TransformationChain;
use Setono\SyliusFeedPlugin\Transformation\TransformationRegistry;
use Setono\SyliusFeedPlugin\Transformation\Truncate;
use Setono\SyliusFeedPlugin\Transformation\ValueMap;
use Setono\SyliusFeedPlugin\Validator\FeedItemValidator;
use Setono\SyliusFeedPlugin\Validator\RequiredFieldsValidator;
use Setono\SyliusFeedPlugin\ValueResolver\ValueResolverInterface;
use Setono\SyliusFeedPlugin\Writer\CsvWriter;
use Setono\SyliusFeedPlugin\Writer\FeedWriterRegistryInterface;
use Setono\SyliusFeedPlugin\Writer\NoneSplitManifest;
use Setono\SyliusFeedPlugin\Writer\SplitManifestRegistry;
use Setono\SyliusFeedPlugin\Writer\SupplementalSplitManifest;
use Setono\SyliusFeedPlugin\Writer\XmlWriter;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Validator\Validation;

/**
 * The M7 fan-out acceptance (§6.3): rendering a catalog inline (single chunk) and via fan-out (the
 * body-only chunks concatenated by the finalize step) produces a canonical file that is
 * byte-for-byte identical. Runs the real generator/writer against a range-aware stub data source, so
 * it needs no database, and each source entity carries a distinct id so the test also proves the
 * ordered concatenation keeps every item exactly once, in order.
 */
final class FeedGeneratorFanOutTest extends TestCase
{
    use ProphecyTrait;

    public const ITEMS = 5;

    private string $storageDir;

    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->storageDir = sys_get_temp_dir() . '/setono-feed-fanout-' . bin2hex(random_bytes(6));
        $this->filesystem = new Filesystem(new LocalFilesystemAdapter($this->storageDir));
    }

    protected function tearDown(): void
    {
        $this->filesystem->deleteDirectory('catalog');
    }

    /**
     * @test
     */
    public function it_produces_a_byte_identical_file_via_fan_out(): void
    {
        $generator = $this->createGenerator();
        $feed = $this->feed();
        $context = new FeedContext($this->channel(), 'en_US', 'USD');

        // 1. inline (single-chunk) output — capture its bytes before fan-out overwrites the file.
        $inline = $generator->generate($feed, $context);
        $inlineBytes = $this->filesystem->read($inline->path);

        // 2. fan-out over the same catalog: two chunks forced by splitting the id range [1..5].
        $ranges = [new ChunkRange(1, 2), new ChunkRange(3, 5)];
        foreach ($ranges as $index => $range) {
            $generator->generateChunk($feed, $context, $range, $index);
        }
        $output = $generator->finalizeChunks($feed, $context, count($ranges));

        $fanOutBytes = $this->filesystem->read($output->primaryPath);

        self::assertSame($inline->path, $output->primaryPath, 'fan-out writes the same canonical path');
        self::assertSame($inlineBytes, $fanOutBytes, 'the fan-out output must be byte-identical to the inline output');

        // Sanity: the concatenated file carries the header + every distinct item exactly once, in order.
        $rows = [...Reader::createFromString($fanOutBytes)->getRecords()];
        self::assertCount(self::ITEMS + 1, $rows, 'header + one row per item');
        self::assertSame(['id', 'title', 'availability'], $rows[0]);
        self::assertSame(['SKU-1', 'Item 1', 'in_stock'], $rows[1]);
        self::assertSame(['SKU-5', 'Item 5', 'in_stock'], $rows[5]);
    }

    /**
     * The chunk partials are body-only: no header row, just the item rows for the chunk's id range.
     *
     * @test
     */
    public function it_writes_body_only_partials(): void
    {
        $generator = $this->createGenerator();
        $feed = $this->feed();
        $context = new FeedContext($this->channel(), 'en_US', 'USD');

        $render = $generator->generateChunk($feed, $context, new ChunkRange(1, 2), 0);

        self::assertSame(2, $render->itemCount);
        self::assertTrue($this->filesystem->fileExists('catalog/web_en_us_usd.chunk-0.csv'));

        $partial = $this->filesystem->read('catalog/web_en_us_usd.chunk-0.csv');
        $rows = [...Reader::createFromString($partial)->getRecords()];
        self::assertCount(2, $rows, 'body-only: two item rows, no header');
        self::assertSame(['SKU-1', 'Item 1', 'in_stock'], $rows[0]);
        self::assertSame(['SKU-2', 'Item 2', 'in_stock'], $rows[1]);
    }

    private function createGenerator(): FeedGenerator
    {
        $feedTypeRegistry = $this->prophesize(FeedTypeRegistryInterface::class);
        $feedTypeRegistry->get('product_variant')->willReturn($this->feedType());

        $presetRegistry = $this->prophesize(MappingPresetRegistryInterface::class);
        $presetRegistry->forFeedType('product_variant')->willReturn([]);

        $formatRegistry = $this->prophesize(FormatRegistryInterface::class);
        $formatRegistry->get('csv')->willReturn(new CsvFormat());

        $writerRegistry = $this->prophesize(FeedWriterRegistryInterface::class);
        $writerRegistry->get('xml')->willReturn(new XmlWriter());
        $writerRegistry->get('csv')->willReturn(new CsvWriter());

        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator->getContext()->willReturn(new RequestContext());

        return new FeedGenerator(
            $feedTypeRegistry->reveal(),
            new MappingResolver($presetRegistry->reveal()),
            $formatRegistry->reveal(),
            $writerRegistry->reveal(),
            new FieldMappingEvaluator(
                new TransformationChain(new TransformationRegistry([new Truncate(), new StripTags(), new MoneyFormat(), new ValueMap()])),
                new ReferenceResolver(),
                new OperatorRegistry([new IsTrue()]),
                new ExpressionEvaluator(new InMemoryLookup()),
                new SandboxedTwigRenderer(new FeedTemplateSecurityPolicy(), new InMemoryLookup()),
                new NullLookupReferenceResolver(),
            ),
            new FilterEvaluator(new ReferenceResolver(), new OperatorRegistry([new Equals()])),
            new NullLookupReferenceResolver(),
            new FeedItemValidator(Validation::createValidator(), new RequiredFieldsValidator()),
            new EventDispatcher(),
            $urlGenerator->reveal(),
            new OutputWriter($this->filesystem),
            new SplitManifestRegistry([new NoneSplitManifest(), new SupplementalSplitManifest()]),
        );
    }

    private function feedType(): FeedTypeInterface
    {
        $fields = [
            'id' => $this->entityField('id', 'sku'),
            'title' => $this->entityField('title', 'title'),
            'availability' => $this->field('availability', 'in_stock'),
        ];

        $dataSource = new class() implements DataSourceInterface {
            public function getResourceClass(): string
            {
                return \stdClass::class;
            }

            public function getItems(FeedContext $context, FilterSet $filters): iterable
            {
                for ($id = 1; $id <= FeedGeneratorFanOutTest::ITEMS; ++$id) {
                    yield self::entity($id);
                }
            }

            public function count(FeedContext $context, FilterSet $filters): int
            {
                return FeedGeneratorFanOutTest::ITEMS;
            }

            public function getIdRange(FeedContext $context, FilterSet $filters): ChunkRange
            {
                return new ChunkRange(1, FeedGeneratorFanOutTest::ITEMS);
            }

            public function getItemsInRange(FeedContext $context, FilterSet $filters, ChunkRange $range): iterable
            {
                for ($id = $range->start; $id <= min($range->end, FeedGeneratorFanOutTest::ITEMS); ++$id) {
                    if ($id < 1) {
                        continue;
                    }

                    yield self::entity($id);
                }
            }

            private static function entity(int $id): object
            {
                return (object) ['id' => $id, 'sku' => 'SKU-' . $id, 'title' => 'Item ' . $id];
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
                return [ScopeDimension::CHANNEL, ScopeDimension::LOCALE, ScopeDimension::CURRENCY];
            }

            public function getAvailableFields(): array
            {
                return $this->fields;
            }
        };
    }

    private function field(string $name, string $value): FieldDefinition
    {
        $resolver = new FixedValueResolver($name, FieldType::STRING, $value);

        return new FieldDefinition($name, $resolver->getLabel(), FieldType::STRING, $resolver);
    }

    /**
     * A resolver that reads a per-entity property, so each item renders a distinct row.
     */
    private function entityField(string $name, string $property): FieldDefinition
    {
        $resolver = new class($name, $property) implements ValueResolverInterface {
            public function __construct(
                private readonly string $name,
                private readonly string $property,
            ) {
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
                return ((array) $entity)[$this->property] ?? null;
            }
        };

        return new FieldDefinition($name, $resolver->getLabel(), FieldType::STRING, $resolver);
    }

    private function channel(): ChannelInterface
    {
        $channel = $this->prophesize(ChannelInterface::class);
        $channel->getCode()->willReturn('web');
        $channel->getHostname()->willReturn('example.com');

        return $channel->reveal();
    }

    private function feed(): FeedInterface
    {
        $source = $this->prophesize(FeedSourceInterface::class);
        $source->getFeedType()->willReturn('product_variant');
        $source->getPosition()->willReturn(0);
        $source->getFilters()->willReturn(new ArrayCollection());
        $source->getFields()->willReturn(new ArrayCollection([
            $this->feedField('id', 'id', 0),
            $this->feedField('title', 'title', 1),
            $this->feedField('availability', 'availability', 2),
        ]));

        $feed = $this->prophesize(FeedInterface::class);
        $feed->getCode()->willReturn('catalog');
        $feed->getFormat()->willReturn('csv');
        $feed->getFormatConfig()->willReturn([]);
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
