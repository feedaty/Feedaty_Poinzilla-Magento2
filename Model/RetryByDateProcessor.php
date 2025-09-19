<?php
declare(strict_types=1);

namespace Zoorate\PoinZilla\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DataObject;
use Psr\Log\LoggerInterface;
use Zoorate\PoinZilla\Model\Api\PoinZilla as Api;

/**
 * Esegue la retry (forzata) delle chiamate externalOrder/externalConsumer
 * su un intervallo di date, con filtro opzionale di store_id e batching.
 */
class RetryByDateProcessor
{
    /** @var string[] */
    private const CALLS = ['externalOrder', 'externalConsumer'];

    /** Numero record per batch */
    private const BATCH_SIZE = 500;

    public function __construct(
        private ResourceConnection $resource,
        private LoggerInterface $logger,
        private Api $api
    ) {}

    /**
     * Esegue la retry forzata sui log compresi fra $from e $to (inclusivi).
     *
     * @param \DateTime $from   Inizio intervallo (incluso) — timezone già normalizzato dal controller
     * @param \DateTime $to     Fine intervallo (incluso)    — timezone già normalizzato dal controller
     * @param int[]|null $storeIds Elenco di store_id su cui filtrare; null = tutti gli store
     * @return array{0:int,1:int}  [successi, falliti]
     */
    public function run(\DateTime $from, \DateTime $to, ?array $storeIds = null): array
    {
        $conn  = $this->resource->getConnection();
        $table = $this->resource->getTableName('zoorate_api_log');

        $ok = 0;
        $ko = 0;
        $lastId = 0;

        do {
            $select = $conn->select()
                ->from($table)
                ->where('id > ?', $lastId)
                ->where('call_name IN (?)', self::CALLS)
                ->where('created_at >= ?', $from->format('Y-m-d H:i:s'))
                ->where('created_at <= ?', $to->format('Y-m-d H:i:s'))
                ->order('id ASC')
                ->limit(self::BATCH_SIZE);

            if ($storeIds && \count($storeIds) > 0) {
                $select->where('store_id IN (?)', $storeIds);
            }

            $rows = $conn->fetchAll($select);

            if (!$rows) {
                break;
            }

            foreach ($rows as $row) {
                $lastId = (int)$row['id']; // avanza il cursore

                try {
                    // Adattatore semplice: i metodi retry* leggono tramite getter.
                    $log = new DataObject($row);

                    $call = (string)$row['call_name'];
                    $res = ($call === 'externalOrder')
                        ? $this->api->retryOrderRequest($log)
                        : $this->api->retryConsumerRequest($log);

                    $res ? $ok++ : $ko++;
                } catch (\Throwable $e) {
                    $this->logger->error(
                        sprintf('[PZ RetryByDate] Log ID %s: %s', (string)$row['id'], $e->getMessage())
                    );
                    $ko++;
                }
            }
            // Continua finché arrivano righe
        } while (\count($rows) === self::BATCH_SIZE);

        return [$ok, $ko];
    }
}
