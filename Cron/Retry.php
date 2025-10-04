<?php

namespace Zoorate\PoinZilla\Cron;

use Psr\Log\LoggerInterface;
use Zoorate\PoinZilla\Model\Api\PoinZilla as PoinZillaApi;
use Zoorate\PoinZilla\Model\ResourceModel\ZoorateApiLog\CollectionFactory as ApiLogCollectionFactory;
use Zoorate\PoinZilla\Helper\Data as PoinzillaHelper;

class Retry
{
    private ApiLogCollectionFactory $collectionFactory;
    private LoggerInterface $logger;
    private PoinZillaApi $api;
    private PoinzillaHelper $helper;

    public function __construct(
        ApiLogCollectionFactory $collectionFactory,
        LoggerInterface $logger,
        PoinZillaApi $api,
        PoinzillaHelper $helper
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->logger = $logger;
        $this->api = $api;
        $this->helper = $helper;
    }

    /**
     * Retry failed API calls for both externalOrder and externalConsumer.
     */
    public function execute(): void
    {
        // Max retry attempts
        $maxRetries = 5;

        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('call_result', ['neq' => 'Pass']);
        $collection->addFieldToFilter('retry', ['lt' => $maxRetries]);

        foreach ($collection as $log) {
            $id = (int)$log->getId();
            $retry = (int)$log->getData('retry');
            $cmd = (string)$log->getData('call_name');

            try {
                if ($cmd === 'externalOrder') {
                    $success = $this->api->retryOrderRequest($log);
                }
                else {
                    $success = $this->api->retryConsumerRequest($log);
                }
                $status = $success ? 'Pass' : 'Fail';
                $this->logger->info("[PoinZilla Retry] Log ID {$id} retried ({$retry} → " . ($retry + 1) . ") - {$cmd} - Status: {$status}");
            } catch (\Throwable $e) {
                $this->logger->error("[PoinZilla Retry] Exception on log ID {$id} ({$cmd}): " . $e->getMessage());
            }
        }
    }
}
