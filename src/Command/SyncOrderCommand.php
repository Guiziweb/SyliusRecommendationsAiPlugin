<?php

/*
 * This file is part of Guiziweb's SyliusRecommendationsAiPlugin for Sylius.
 * (c) Guiziweb <guiziweb@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Guiziweb\SyliusRecommendationsAiPlugin\Command;

use Doctrine\ORM\EntityNotFoundException;
use Exception;
use Google\ApiCore\OperationResponse;
use Google\Cloud\Retail\V2\Client\UserEventServiceClient;
use Google\Cloud\Retail\V2\ImportUserEventsRequest;
use Google\Cloud\Retail\V2\ImportUserEventsResponse;
use Google\Cloud\Retail\V2\UserEvent;
use Google\Cloud\Retail\V2\UserEventImportSummary;
use Google\Cloud\Retail\V2\UserEventInlineSource;
use Google\Cloud\Retail\V2\UserEventInputConfig;
use Guiziweb\SyliusRecommendationsAiPlugin\Api\EventService;
use Guiziweb\SyliusRecommendationsAiPlugin\DTO\ProductData;
use Guiziweb\SyliusRecommendationsAiPlugin\Service\EventFormatterService;
use Guiziweb\SyliusRecommendationsAiPlugin\Service\RequestFormatterService;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class SyncOrderCommand extends Command
{
    protected static $defaultName = 'sync:google-orders';

    private UserEventServiceClient $userEventServiceClient;

    private EventFormatterService $eventFormatterService;

    private RequestFormatterService $requestFormatterService;

    /**
     * @var OrderRepositoryInterface<OrderInterface>
     */
    private OrderRepositoryInterface $orderRepository;

    /**
     * @var ChannelRepositoryInterface<ChannelInterface>
     */
    private ChannelRepositoryInterface $channelRepository;

    /**
     * @param OrderRepositoryInterface<OrderInterface> $orderRepository
     * @param ChannelRepositoryInterface<ChannelInterface> $channelRepository
     */
    public function __construct(EventFormatterService $eventFormatterService, RequestFormatterService $requestFormatterService, OrderRepositoryInterface $orderRepository, ChannelRepositoryInterface $channelRepository)
    {
        parent::__construct();
        $this->eventFormatterService = $eventFormatterService;
        $this->requestFormatterService = $requestFormatterService;
        $this->orderRepository = $orderRepository;
        $this->channelRepository = $channelRepository;
        $this->userEventServiceClient = new UserEventServiceClient();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Synchronizes orders with Google Recommendations AI')
            ->addArgument('channel_code', InputArgument::REQUIRED, 'The channel code for the products')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $channelCode = $input->getArgument('channel_code'); // Récupération de l'argument

        $channel = $this->channelRepository->findOneByCode($channelCode);

        if (!$channel instanceof ChannelInterface) {
            throw new EntityNotFoundException("'$channelCode' not found");
        }

        $orders = $this->orderRepository->findAll();

        $userEvents = [];

        foreach ($orders as $order) {
            if (!$order instanceof OrderInterface) {
                continue;
            }

            $productDataArray = $this->getProductDataArray($order);

            $userEvent = $this->createUserEvent($order, $productDataArray, $channel);

            if (!$userEvent instanceof UserEvent) {
                continue;
            }

            $userEvents[] = $userEvent;
        }

        $importRequest = new ImportUserEventsRequest();
        $importRequest->setParent($this->requestFormatterService->formatUserEventPath());

        $userEventInlineSource = new UserEventInlineSource();
        $userEventInlineSource->setUserEvents($userEvents);

        $inputConfig = new UserEventInputConfig();
        $inputConfig->setUserEventInlineSource($userEventInlineSource);

        $importRequest->setInputConfig($inputConfig);

        try {
            /** @var OperationResponse $operation */
            $operation = $this->userEventServiceClient->importUserEvents($importRequest);
            if ($operation->isDone()) {
                /** @var ImportUserEventsResponse $result */
                $result = $operation->getResult();

                $importSummary = $result->getImportSummary();

                if ($importSummary instanceof UserEventImportSummary) {
                    $output->writeln('Order imported joined event :' . $importSummary->getJoinedEventsCount());
                    $output->writeln('Order imported unjoined event :' . $importSummary->getUnjoinedEventsCount());
                }
            }

            if ($operation->getError()) {
                $output->writeln($operation->getError()->getMessage());
            }
        } catch (Exception $exception) {
            $output->writeln(\sprintf('<error>%s</error>', ($exception->getMessage())));

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * @return ProductData[] $productDataArray
     */
    private function getProductDataArray(OrderInterface $order): array
    {
        $productDataArray = [];
        foreach ($order->getItems() as $item) {
            $product = $item->getProduct();
            if ($product instanceof ProductInterface) {
                $productDataArray[] = new ProductData($product, $item->getQuantity());
            }
        }

        return $productDataArray;
    }

    /**
     * @param ProductData[] $productDataArray
     */
    private function createUserEvent(OrderInterface $order, array $productDataArray, ChannelInterface $channel): ?UserEvent
    {
        $customer = $order->getCustomer();

        if (!$customer instanceof CustomerInterface) {
            return null;
        }

        $user = $customer->getUser();

        if (!$user instanceof UserInterface) {
            return null;
        }

        return $this->eventFormatterService->generateEventUserFromSyliusOrder(
            EventService::PURCHASE_COMPLETE,
            $productDataArray,
            $user->getId(),
            $channel,
            $order
        );
    }
}
