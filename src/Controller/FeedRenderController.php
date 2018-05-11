<?php

declare(strict_types=1);

namespace Loevgaard\SyliusFeedPlugin\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

final class FeedRenderController extends Controller
{
    /**
     * @param string $uuid
     *
     * @return Response
     */
    public function renderFeedAction(string $uuid): Response
    {
        $feedRepository = $this->get('loevgaard.repository.feed');

        $feed = $feedRepository->findByUuid($uuid);

        if(!$feed) {
            throw $this->createNotFoundException('The feed with id `'.$uuid.'` does not exist');
        }
    }
}
