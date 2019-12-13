<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Controller\Action\Admin;

use Setono\SyliusFeedPlugin\Repository\ViolationRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

final class SeverityCountAction
{
    /** @var ViolationRepositoryInterface */
    private $violationRepository;

    /** @var Environment */
    private $twig;

    public function __construct(ViolationRepositoryInterface $violationRepository, Environment $twig)
    {
        $this->violationRepository = $violationRepository;
        $this->twig = $twig;
    }

    public function __invoke(): Response
    {
        $severityCounts = $this->violationRepository->findCountsGroupedBySeverity();

        $content = $this->twig->render('@SetonoSyliusFeedPlugin/Admin/Violation/severity_count.html.twig', [
            'severityCounts' => $severityCounts,
        ]);

        return new Response($content);
    }
}
