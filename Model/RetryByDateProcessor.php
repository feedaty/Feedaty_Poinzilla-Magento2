<?php
declare(strict_types=1);

namespace Zoorate\PoinZilla\Model;

use Psr\Log\LoggerInterface;
use Zoorate\PoinZilla\Model\Api\PoinZilla as Api;
use Zoorate\PoinZilla\Model\ResourceModel\ZoorateApiLog\CollectionFactory as ApiLogCollectionFactory;

class RetryByDateProcessor
{
    /**  chiamate considerare per la forzatura */
    private const CALLS = ['externalOrder', 'externalConsumer'];

    /**  batch (pagine della collection) */
    private const PAGE_SIZE = 500;
    private LoggerInterface $logger;
    private ApiLogCollectionFactory $collectionFactory;
    private Api $api;


    /**
     * @param ApiLogCollectionFactory $collectionFactory
     * @param LoggerInterface $logger
     * @param Api $api
     */
    public function __construct(
        ApiLogCollectionFactory $collectionFactory,
        LoggerInterface $logger,
        Api $api
    ) {
        $this->api = $api;
        $this->collectionFactory = $collectionFactory;
        $this->logger = $logger;
    }

    /**
     * Esegue il retry delle chiamate fallite (call_name IN CALLS) nel range di date specificato.
     *
     * @param \DateTime $from Data/ora inizio (inclusivo)
     * @param \DateTime $to   Data/ora fine (inclusivo)
     * @param int[]|null $storeIds Filtra per store_id se non null
     * @return int[] Array con due valori: [0] = ok, [1] = ko
     */
    public function run(\DateTime $from, \DateTime $to, ?array $storeIds = null): array
    {
        $ok = 0;
        $ko = 0;

        // Prima istanza per calcolare numero pagine (getSize/getLastPageNumber)
        $prototype = $this->collectionFactory->create();
        $this->applyFilters($prototype, $from, $to, $storeIds);

        // Ordina per id ASC per stabilità (adegua il campo se diverso)
        $prototype->addOrder('id', 'ASC');

        $pageSize = self::PAGE_SIZE;
        $prototype->setPageSize($pageSize);
        $lastPage = (int)$prototype->getLastPageNumber();

        if ($lastPage === 0) {
            return [0, 0];
        }

        // Paginazione: ricrea la collection a ogni giro per evitare memory leak
        for ($page = 1; $page <= $lastPage; $page++) {
            $collection = $this->collectionFactory->create();
            $this->applyFilters($collection, $from, $to, $storeIds);
            $collection->addOrder('id', 'ASC');
            $collection->setPageSize($pageSize);
            $collection->setCurPage($page);

            foreach ($collection as $log) {
                try {
                    $call = (string)$log->getData('call_name');

                    $res = ($call === 'externalOrder')
                        ? $this->api->retryOrderRequest($log)
                        : $this->api->retryConsumerRequest($log);

                    $res ? $ok++ : $ko++;
                } catch (\Throwable $e) {
                    $this->logger->error(
                        sprintf('[PZ RetryByDate] Log ID %s: %s', (string)$log->getId(), $e->getMessage())
                    );
                    $ko++;
                }
            }

            // libera quanto prima
            $collection->clear();
        }

        return [$ok, $ko];
    }

    /**
     * Applica i filtri standard alla collection.
     */
    private function applyFilters(
        \Zoorate\PoinZilla\Model\ResourceModel\ZoorateApiLog\Collection $collection,
        \DateTime $from,
        \DateTime $to,
        ?array $storeIds
    ): void {
        // call_name IN (...)
        $collection->addFieldToFilter('call_name', ['in' => self::CALLS]);

        // created_at BETWEEN from/to (inclusivo)
        $collection->addFieldToFilter('created_at', [
            'from' => $from->format('Y-m-d H:i:s'),
            'to'   => $to->format('Y-m-d H:i:s'),
            'date' => true
        ]);

        // filtro store_id se richiesto
        if ($storeIds && \count($storeIds) > 0) {
            $collection->addFieldToFilter('store_id', ['in' => $storeIds]);
        }
    }
}
