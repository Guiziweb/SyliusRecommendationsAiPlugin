<?php

/*
 * This file is part of Guiziweb's SyliusRecommendationsAiPlugin for Sylius.
 * (c) Guiziweb <guiziweb@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Guiziweb\SyliusRecommendationsAiPlugin\EventListener;

use Guiziweb\SyliusRecommendationsAiPlugin\Api\EventService;
use Guiziweb\SyliusRecommendationsAiPlugin\Message\UserEventMessage;
use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Sylius\Component\User\Model\UserInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class ProductViewListener
{
    /**
     * @var ProductRepositoryInterface<ProductInterface>
     */
    private ProductRepositoryInterface $productRepository;

    private ChannelContextInterface $channelContext;

    private LocaleContextInterface $localeContext;

    private TokenStorageInterface $tokenStorage;

    private MessageBusInterface $bus;

    /**
     * @param ProductRepositoryInterface<ProductInterface> $productRepository
     */
    public function __construct(ProductRepositoryInterface $productRepository, ChannelContextInterface $channelContext, LocaleContextInterface $localeContext, TokenStorageInterface $tokenStorage, MessageBusInterface $bus)
    {
        $this->productRepository = $productRepository;
        $this->channelContext = $channelContext;
        $this->localeContext = $localeContext;
        $this->tokenStorage = $tokenStorage;
        $this->bus = $bus;
    }

    public function onProductView(ControllerEvent $event): void
    {
        $controller = $event->getController();

        if (!\is_array($controller) || !$controller[0] instanceof ResourceController) {
            return;
        }

        if ('sylius_shop_product_show' !== $event->getRequest()->attributes->get('_route')) {
            return;
        }

        if (null === $event->getRequest()->attributes->get('slug')) {
            return;
        }

        $slug = $event->getRequest()->attributes->get('slug');

        $channel = $this->channelContext->getChannel();

        if (!$channel instanceof ChannelInterface) {
            return;
        }

        $product = $this->productRepository->findOneByChannelAndSlug(
            $channel,
            $this->localeContext->getLocaleCode(),
            $slug
        );

        if (!$product instanceof ProductInterface) {
            return;
        }

        $token = $this->tokenStorage->getToken();

        if (!$token instanceof TokenInterface) {
            return;
        }
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return;
        }

        $this->bus->dispatch(new UserEventMessage(
            EventService::DETAIL_PAGE_VIEW,
            $product->getId(),
            $user->getId(),
            $this->channelContext->getChannel()->getId()
        ));
    }
}
