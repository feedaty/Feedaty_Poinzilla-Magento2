<?php
namespace Zoorate\PoinZilla\Model;

use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;
use Zoorate\PoinZilla\Model\Api\PoinZilla as Api;

class RetryByDateProcessor
{
    private const CALLS = ['externalOrder','externalConsumer'];

    public function __construct(
        private ResourceConnection $resource,
        private LoggerInterface $logger,
        private Api $api
    ) {}

    /** Ritorna [successi, falliti] */
    public function run(\DateTime $from, \DateTime $to): array
    {
        $conn  = $this->resource->getConnection();
        $table = $this->resource->getTableName('zoorate_api_log');

        $rows = $conn->fetchAll(
            $conn->select()->from($table)
                ->where('call_name IN (?)', self::CALLS)
                ->where('created_at >= ?', $from->format('Y-m-d H:i:s'))
                ->where('created_at <= ?', $to->format('Y-m-d H:i:s'))
        );

        $ok=0; $ko=0;
        foreach ($rows as $row) {
            try {
                $log = new \Magento\Framework\DataObject($row);
                $res = $row['call_name']==='externalOrder'
                    ? $this->api->retryOrderRequest($log)
                    : $this->api->retryConsumerRequest($log);
                $res ? $ok++ : $ko++;
            } catch (\Throwable $e) {
                $this->logger->error('[PZ RetryByDate] ID '.$row['id'].': '.$e->getMessage());
                $ko++;
            }
        }
        return [$ok,$ko];
    }
}
