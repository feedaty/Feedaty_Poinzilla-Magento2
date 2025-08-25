<?php

namespace Zoorate\Poinzilla\Cron;

use Psr\Log\LoggerInterface;
use Zoorate\Poinzilla\Model\Api\PoinZilla\External as ApiExternal;
use Zoorate\Poinzilla\Model\ResourceModel\ZoorateApiLog\CollectionFactory as ApiLogCollectionFactory;
use Zoorate\Poinzilla\Helper\Data as PoinzillaHelper;

class OrdersRetry
{
    private ApiLogCollectionFactory $collectionFactory;
    private LoggerInterface $logger;
    private ApiExternal $api;
    private PoinzillaHelper $helper;

    public function __construct(
        ApiLogCollectionFactory $collectionFactory,
        LoggerInterface $logger,
        ApiExternal $api,
        PoinzillaHelper $helper
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->logger = $logger;
        $this->api = $api;
        $this->helper = $helper;
    }

    public function execute(): void
    {
        $collection = $this->collectionFactory->create();

        $collection->addFieldToFilter('call_name', 'externalOrder')
            ->addFieldToFilter('call_result', ['neq' => 'Pass'])
            ->addFieldToFilter('retry', ['lt' => 3]);

        foreach ($collection as $log) {
            $id = $log->getId();
            $retry = (int) $log->getData('retry');

            try {
                $success = $this->api->retryOrderRequest($log);
                $status = $success ? 'Pass' : 'Fail';
                $this->logger->info("[PoinZilla Retry] Log ID $id retried ($retry → " . ($retry + 1) . ") - Status: $status");

            } catch (\Throwable $e) {
                $this->logger->error("[PoinZilla Retry] Exception on log ID $id: " . $e->getMessage());
            }
        }
    }
}
