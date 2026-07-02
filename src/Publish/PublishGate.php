<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Publish;

use Setono\SyliusFeedPlugin\Model\FeedContextResultInterface;

/**
 * Runs a context's candidate through its configured guardrails (§6.6). A tripped `block` guardrail
 * blocks promotion; a tripped `warn` guardrail only records a reason. Unknown or malformed guardrail
 * entries are skipped so a stale/typo'd config never hard-fails a run, and an empty list means
 * "no gate". The config originates from persisted JSON, so every entry is treated as untrusted.
 */
final class PublishGate implements PublishGateInterface
{
    public const SEVERITY_BLOCK = 'block';

    public const SEVERITY_WARN = 'warn';

    public function __construct(
        private readonly GuardrailRegistryInterface $guardrailRegistry,
    ) {
    }

    public function evaluate(
        FeedContextResultInterface $candidate,
        ?FeedContextResultInterface $baseline,
        array $guardrails,
    ): PublishDecision {
        $blocked = false;
        $reasons = [];

        foreach ($guardrails as $guardrail) {
            if (!is_array($guardrail)) {
                continue;
            }

            $type = $guardrail['type'] ?? null;
            if (!is_string($type) || !$this->guardrailRegistry->has($type)) {
                continue;
            }

            $severity = $guardrail['severity'] ?? self::SEVERITY_BLOCK;
            if (!is_string($severity)) {
                $severity = self::SEVERITY_BLOCK;
            }

            $tripped = $this->guardrailRegistry
                ->get($type)
                ->evaluate($candidate, $baseline, $this->toParams($guardrail['params'] ?? []));

            if (!$tripped) {
                continue;
            }

            $reasons[] = sprintf('%s: guardrail tripped (severity: %s)', $type, $severity);

            if (self::SEVERITY_BLOCK === $severity) {
                $blocked = true;
            }
        }

        return new PublishDecision($blocked, $reasons);
    }

    /**
     * Coerces an untrusted config value into a string-keyed params map for a guardrail.
     *
     * @return array<string, mixed>
     */
    private function toParams(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $params = [];
        foreach ($value as $key => $item) {
            $params[(string) $key] = $item;
        }

        return $params;
    }
}
