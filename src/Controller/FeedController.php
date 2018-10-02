<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Controller;

use Setono\SyliusFeedPlugin\Entity\FeedInterface;
use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Resource\ResourceActions;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FeedController extends ResourceController
{
    public function showAction(Request $request): Response
    {
        $configuration = $this->requestConfigurationFactory->create($this->metadata, $request);

        $this->isGrantedOr403($configuration, ResourceActions::SHOW);

        /** @var FeedInterface $resource */
        $resource = $this->findOr404($configuration);

        $this->eventDispatcher->dispatch(ResourceActions::SHOW, $configuration, $resource);

        $feedFile = new \SplFileInfo($this->getFeedPath($resource));

        if (!$feedFile->isFile()) {
            throw $this->createNotFoundException('The feed does not exist or has not been generated yet');
        }

        $response = new BinaryFileResponse($feedFile->getPathname());

        return $response;
    }

    protected function getFeedPath(FeedInterface $feed): string
    {
        /** @var ChannelInterface $channel */
        $channel = $this->get('sylius.context.channel')->getChannel();
        $locale = $this->get('sylius.context.locale')->getLocaleCode();

        return $this->getParameter('setono_sylius_feed.dir') . '/' . $channel->getCode() . '/' . $locale . '/' . $feed->getSlug() . '.xml';
    }
}
