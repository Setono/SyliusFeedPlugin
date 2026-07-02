<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Generator;

use League\Flysystem\FilesystemOperator;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Writer\FeedWriterInterface;
use Setono\SyliusFeedPlugin\Writer\SplitManifestInterface;
use Setono\SyliusFeedPlugin\Writer\WriterConfigInterface;

/**
 * Streams a context's items to storage with optional splitting and gzip (§12).
 *
 * Each part is buffered to a `php://temp` stream (spilling to disk past ~2 MB, so memory stays
 * bounded to one part) and flushed as soon as the next part starts — or, when nothing ever rotated,
 * flushed once to the un-suffixed canonical path so the single-file output is byte-identical to the
 * pre-split behaviour. When a context does split, parts are written as {code}/{contextKey}-{n}.{ext}
 * and the format's {@see SplitManifestInterface} may emit a manifest at the un-suffixed canonical
 * path tying them together. When gzip is enabled, each written file is deflated through a bounded
 * `zlib.deflate` stream filter (gzip framing) and carries a `.gz` suffix.
 */
final class OutputWriter implements OutputWriterInterface
{
    public function __construct(
        private readonly FilesystemOperator $feedFilesystem,
    ) {
    }

    public function write(
        iterable $items,
        FeedWriterInterface $writer,
        WriterConfigInterface $config,
        FeedContext $context,
        string $directory,
        string $basename,
        string $extension,
        array $splitLimit,
        bool $gzip,
        SplitManifestInterface $manifest,
    ): OutputResult {
        $maxItems = $splitLimit['maxItems'] ?? null;
        $maxBytes = $splitLimit['maxBytes'] ?? null;

        $stream = $this->openPart($writer, $context, $config);

        $itemCount = 0;
        $partItemCount = 0;
        $partIndex = 1;

        $paths = [];
        $bytes = 0;

        foreach ($items as $item) {
            if ($partItemCount > 0 && $this->limitReached($partItemCount, $stream, $maxItems, $maxBytes)) {
                $writer->writeEpilogue();
                $writer->close();
                [$path, $size] = $this->persist($stream, $this->partPath($directory, $basename, $partIndex, $extension), $gzip);
                fclose($stream);
                $paths[] = $path;
                $bytes += $size;

                ++$partIndex;
                $partItemCount = 0;
                $stream = $this->openPart($writer, $context, $config);
            }

            $writer->writeItem($item);
            ++$itemCount;
            ++$partItemCount;
        }

        $writer->writeEpilogue();
        $writer->close();

        // Never rotated: a single file at the un-suffixed canonical path, byte-identical to output
        // produced before splitting existed.
        if (1 === $partIndex) {
            [$path, $size] = $this->persist($stream, $this->canonicalPath($directory, $basename, $extension), $gzip);
            fclose($stream);

            return new OutputResult($path, [$path], $itemCount, $size);
        }

        // The context split: flush the final part, then let the strategy emit a manifest.
        [$path, $size] = $this->persist($stream, $this->partPath($directory, $basename, $partIndex, $extension), $gzip);
        fclose($stream);
        $paths[] = $path;
        $bytes += $size;

        [$primaryPath, $paths, $bytes] = $this->writeManifest(
            $manifest,
            $paths,
            $bytes,
            $context,
            $config,
            $this->canonicalPath($directory, $basename, $extension),
            $gzip,
        );

        return new OutputResult($primaryPath, $paths, $itemCount, $bytes);
    }

    public function writeBody(
        iterable $items,
        FeedWriterInterface $writer,
        WriterConfigInterface $config,
        FeedContext $context,
        string $directory,
        string $basename,
        string $extension,
    ): OutputResult {
        $stream = $this->openStream();
        $writer->open($stream, $context, $config);
        // Body only: no preamble/epilogue — the raw item bytes are the partial's content.

        $itemCount = 0;
        foreach ($items as $item) {
            $writer->writeItem($item);
            ++$itemCount;
        }

        $writer->close();

        [$path, $size] = $this->persist($stream, $this->canonicalPath($directory, $basename, $extension), false);
        fclose($stream);

        return new OutputResult($path, [$path], $itemCount, $size);
    }

    public function finalizeFromPartials(
        array $partialPaths,
        FeedWriterInterface $writer,
        WriterConfigInterface $config,
        FeedContext $context,
        string $directory,
        string $basename,
        string $extension,
    ): OutputResult {
        $stream = $this->openPart($writer, $context, $config);

        foreach ($partialPaths as $partialPath) {
            $this->copyPartial($stream, $partialPath);
        }

        $writer->writeEpilogue();
        $writer->close();

        [$path, $size] = $this->persist($stream, $this->canonicalPath($directory, $basename, $extension), false);
        fclose($stream);

        foreach ($partialPaths as $partialPath) {
            if ($this->feedFilesystem->fileExists($partialPath)) {
                $this->feedFilesystem->delete($partialPath);
            }
        }

        return new OutputResult($path, [$path], 0, $size);
    }

    /**
     * Streams a body-only partial's bytes into the open canonical stream, between the preamble and
     * the epilogue, without buffering the whole partial in memory. A missing partial contributes
     * nothing (a chunk that yielded no items writes an empty file, which may not exist).
     *
     * @param resource $stream
     */
    private function copyPartial($stream, string $partialPath): void
    {
        if (!$this->feedFilesystem->fileExists($partialPath)) {
            return;
        }

        $source = $this->feedFilesystem->readStream($partialPath);
        stream_copy_to_stream($source, $stream);
        fclose($source);
    }

    /**
     * @param list<string> $partPaths
     *
     * @return array{0: string, 1: list<string>, 2: int} [primaryPath, paths (manifest first when emitted), totalBytes]
     */
    private function writeManifest(
        SplitManifestInterface $manifest,
        array $partPaths,
        int $bytes,
        FeedContext $context,
        WriterConfigInterface $config,
        string $canonicalPath,
        bool $gzip,
    ): array {
        $stream = $this->openStream();
        $manifest->writeManifest($stream, $partPaths, $context, $config);

        $stat = fstat($stream);
        if (false === $stat || 0 === $stat['size']) {
            // A self-describing (e.g. "none") strategy writes nothing: no manifest file, and the
            // first part is the canonical entry point.
            fclose($stream);

            return [$partPaths[0], $partPaths, $bytes];
        }

        [$manifestPath, $manifestSize] = $this->persist($stream, $canonicalPath, $gzip);
        fclose($stream);

        // The manifest is the canonical entry point, so it leads the returned path set.
        array_unshift($partPaths, $manifestPath);

        return [$manifestPath, $partPaths, $bytes + $manifestSize];
    }

    /**
     * Whether the current part has reached its size limit and the next item must start a new part.
     *
     * @param resource $stream
     */
    private function limitReached(int $partItemCount, $stream, ?int $maxItems, ?int $maxBytes): bool
    {
        if (null !== $maxItems && $partItemCount >= $maxItems) {
            return true;
        }

        if (null !== $maxBytes) {
            $position = ftell($stream);
            if (false !== $position && $position >= $maxBytes) {
                return true;
            }
        }

        return false;
    }

    /**
     * Opens a fresh part stream and primes the writer with the preamble.
     *
     * @return resource
     */
    private function openPart(FeedWriterInterface $writer, FeedContext $context, WriterConfigInterface $config)
    {
        $stream = $this->openStream();
        $writer->open($stream, $context, $config);
        $writer->writePreamble();

        return $stream;
    }

    /**
     * Writes a buffered part/manifest stream to storage (gzipping it first when enabled) and returns
     * the final path and its on-disk size.
     *
     * @param resource $stream
     *
     * @return array{0: string, 1: int}
     */
    private function persist($stream, string $path, bool $gzip): array
    {
        $out = $stream;
        if ($gzip) {
            $path .= '.gz';
            $out = $this->gzip($stream);
        }

        $stat = fstat($out);
        rewind($out);
        $this->feedFilesystem->writeStream($path, $out);

        if ($gzip) {
            fclose($out);
        }

        return [$path, false === $stat ? 0 : $stat['size']];
    }

    /**
     * Deflates the source stream into a new gzip-framed stream through a bounded filter, so only the
     * filter's window (not the whole content) is held in memory.
     *
     * @param resource $source
     *
     * @return resource
     */
    private function gzip($source)
    {
        $target = $this->openStream();

        // window 31 = 15 (max window) + 16 (gzip framing), so the output is a valid gzip stream that
        // gzdecode() can read.
        $filter = stream_filter_append($target, 'zlib.deflate', \STREAM_FILTER_WRITE, ['level' => -1, 'window' => 31]);

        rewind($source);
        stream_copy_to_stream($source, $target);

        if (false !== $filter) {
            stream_filter_remove($filter);
        }

        rewind($target);

        return $target;
    }

    private function canonicalPath(string $directory, string $basename, string $extension): string
    {
        return sprintf('%s/%s.%s', $directory, $basename, $extension);
    }

    private function partPath(string $directory, string $basename, int $index, string $extension): string
    {
        return sprintf('%s/%s-%d.%s', $directory, $basename, $index, $extension);
    }

    /**
     * @return resource
     */
    private function openStream()
    {
        $stream = fopen('php://temp', 'w+b');

        // Defensive: an in-memory stream cannot be made to fail on demand, so this is uncoverable.
        // @codeCoverageIgnoreStart
        if (!is_resource($stream)) {
            throw new \RuntimeException('Could not open a temporary stream');
        }
        // @codeCoverageIgnoreEnd

        return $stream;
    }
}
