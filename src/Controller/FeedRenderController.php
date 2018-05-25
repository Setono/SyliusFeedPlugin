<?php

declare(strict_types=1);

namespace Loevgaard\SyliusFeedPlugin\Controller;

use Loevgaard\SyliusFeedPlugin\Entity\FeedInterface;
use Loevgaard\SyliusFeedPlugin\Repository\FeedRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FeedRenderController extends Controller
{
    /**
     * @param string $uuid
     *
     * @return Response
     */
    public function renderFeedAction(string $uuid): Response
    {
        /** @var FeedRepositoryInterface $feedRepository */
        $feedRepository = $this->get('loevgaard_sylius_feed.repository.feed');

        $feed = $feedRepository->findByUuid($uuid);

        if(!$feed) {
            throw $this->createFeedNotFoundException($uuid);
        }

        $feedFile = new \SplFileInfo($this->getFeedPath($feed));

        if(!$feedFile->isFile()) {
            throw $this->createFeedNotFoundException($uuid);
        }

        $response = new BinaryFileResponse($feedFile->getPathname());

        return $response;
    }

    protected function getFeedPath(FeedInterface $feed) : string
    {
        return $this->getParameter('loevgaard_sylius_feed.dir').'/'.$feed->getSlug().'.xml';
    }

    protected function createFeedNotFoundException(string $uuid) : NotFoundHttpException
    {
        return $this->createNotFoundException('The feed with id `'.$uuid.'` does not exist');
    }
}