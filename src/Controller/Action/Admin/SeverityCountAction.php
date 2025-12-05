<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Controller\Action\Admin;

use Setono\SyliusFeedPlugin\Repository\ViolationRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

final class SeverityCountAction
{
    public function __construct(
        private readonly ViolationRepositoryInterface $violationRepository,
        private readonly Environment $twig,
    ) {
    }

    public function __invoke(int $feed = null): Response
    {
        $severityCounts = $this->violationRepository->findCountsGroupedBySeverity($feed);

        $content = $this->twig->render('@SetonoSyliusFeedPlugin/Admin/Violation/severity_count.html.twig', [
            'severityCounts' => $severityCounts,
        ]);

        return new Response($content);
    }
}
