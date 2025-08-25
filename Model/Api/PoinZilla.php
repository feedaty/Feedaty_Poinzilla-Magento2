<?php

namespace Zoorate\PoinZilla\Model\Api;

use Zoorate\PoinZilla\Helper\Data;
use Magento\Framework\HTTP\Client\Curl as Client;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\ProductRepository;

class PoinZilla
{
    /**
     * @var Data
     */
    protected Data $helper;

    /**
     * @var Client
     */
    protected Client $client;

    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * @var ProductRepository
     */
    protected ProductRepository $productRepository;

    /**
     * PoinZilla constructor.
     * @param Data $helper
     * @param Client $client
     * @param LoggerInterface $logger
     * @param ProductRepository $productRepository
     */
    public function __construct(
        Data $helper,
        Client $client,
        LoggerInterface $logger,
        ProductRepository $productRepository
    )
    {
        $this->helper = $helper;
        $this->client = $client;
        $this->logger = $logger;
        $this->productRepository = $productRepository;
    }

    /**
     * @return Client
     */
    private function getClient(): Client
    {
        return $this->client;
    }

    /**
     * @return string
     */
    protected function getEndpoint(): string
    {
        return $this->helper->getApiUrl();
    }

    /**
     * @return string
     */
    protected function getExternalConsumerEndpoint(): string
    {
        return $this->getEndpoint() . '/api/External/Consumer';
    }

    /**
     * @return string
     */
    protected function getExternalOrderEndpoint(): string
    {
        return $this->getEndpoint() . '/api/External/Order';
    }

    /**
     * @param $cmd
     * @param $data
     * @return bool
     */
    public function postRequest($cmd, $data, $storeId): bool
    {
        $client = $this->getClient();

        if (in_array($cmd, ["externalConsumer", "externalOrder"])) {
            $requestUrl = ($cmd == "externalConsumer") ? $this->getExternalConsumerEndpoint() : $this->getExternalOrderEndpoint();

            $client->addHeader('Content-Type', 'application/json');

            // ✅ Recupera la chiave privata per la specifica store view
            $privateKey = $this->helper->getPrivateKey($storeId);

            // ✅ Debug per verificare la store view
            $this->logger->info("Culture for customer - Store ID: " . $storeId . " - Private Key: " . $privateKey);

            $client->addHeader('X-loyalty-channel-key', $privateKey);

            if ($cmd == "externalOrder") {
                $this->logger->info('Zoorate PoinZilla : Send Order to PoinZilla ' . $requestUrl);
            }

            try {
                $client->post($requestUrl, $data);
            } catch (\Exception $e) {
                $this->logger->error('Zoorate PoinZilla : Error encountered during send order. ' . $e->getMessage());
            }

            $body = $client->getBody();
            $statusCode = $client->getStatus();
            if ($statusCode == 200) {
                $this->helper->apiLog($cmd, $requestUrl, $data, $body, 'Pass', $storeId);
                return true;
            } else {
                //log response error
                $this->logger->error('Zoorate PoinZilla : Error encountered during send order. Status Code: ' . $statusCode . ' - Response: ' . $body);
                $this->helper->apiLog($cmd, $requestUrl, $data, $body, 'Fail', $storeId);
            }
        }

        return false;
    }

    public function retryOrderRequest($log)
    {
        $client = $this->getClient();

        $storeId = (int)$log->getStoreId();
        $data = json_decode($log->getCallBody(), true);

        $id = $log->getId();

        $requestUrl = $this->getExternalOrderEndpoint();

        $client->addHeader('Content-Type', 'application/json');

        $privateKey = $this->helper->getPrivateKey($storeId);
        $client->addHeader('X-loyalty-channel-key', $privateKey);

        try {
            $client->post($requestUrl, $data);
        } catch (\Exception $e) {
            $body = $client->getBody();
            $this->logger->error("[PoinZilla Retry] Error encountered during retry for log ID $id: " . $e->getMessage() . " - Response: " . $body . "requestUrl: " . $requestUrl . " - Data: " . $data);

            return false;
        }

        $statusCode = $client->getStatus();
        $status = $statusCode == 200 ? 'Pass' : 'Fail';

        try {
            $this->helper->updateApiLogRetry($log, $status);
        } catch (\Exception $e) {
            $this->logger->error("[PoinZilla Retry] Failed to update log ID $id: " . $e->getMessage());
            return false;
        }

        return $status;
    }


    public function getModuleEnable($storeId = null)
    {
        return $this->helper->getModuleEnable($storeId);
    }

    public function getSettingMode($storeId = null)
    {
        return $this->helper->getSettingMode($storeId);
    }

    public function getSettingModeCustomers($storeId = null)
    {
        return $this->helper->getsSettingModeCustomers($storeId);
    }

}
