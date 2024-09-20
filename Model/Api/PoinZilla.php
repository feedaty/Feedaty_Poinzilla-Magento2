<?php

namespace Zoorate\PoinZilla\Model\Api;

use Zoorate\PoinZilla\Helper\Data;
use Magento\Framework\HTTP\Client\Curl as Client;
use Psr\Log\LoggerInterface;
use \Magento\Catalog\Model\ProductRepository;

class PoinZilla
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

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

    private function getClient()
    {
        return $this->client;
    }

    protected function getEndpoint()
    {
        return 'https://api.poinzilla.com/';
    }

    protected function getExternalConsumerEndpoint(): string
    {
        return $this->getEndpoint() . 'api/External/Consumer';
    }

    protected function getExternalOrderEndpoint(): string
    {
        return $this->getEndpoint() . 'api/External/Order';
    }

    public function postRequest($cmd, $data): bool
    {
        $client = $this->getClient();


        if ($cmd == "externalConsumer") {
            $requestUrl = $this->getExternalConsumerEndpoint();
            $client->addHeader('Content-Type', 'application/json');
            $client->addHeader('X-loyalty-channel-key', $this->helper->getPublicKey());
            $client->post($requestUrl, $data);
        }
        elseif ($cmd == "externalOrder") {
            $requestUrl = $this->getExternalOrderEndpoint();
            $this->logger->info('Zoorate PoinZilla : Send Order to PoinZilla' . $requestUrl);
            $client->addHeader('Content-Type', 'application/json');
            $client->addHeader('X-loyalty-channel-key', $this->helper->getPublicKey());
            try {
                $client->post($requestUrl, $data);
            } catch (\Exception $e) {
                $this->logger->error('Zoorate PoinZilla : Error encountered during send order. ' . $e->getMessage());
            }
        }

        $body = $client->getBody();
        $statusCode = $client->getStatus();
        if($statusCode == 200) {
            $this->helper->apiLog($cmd, $requestUrl, $data, $body, 'Pass');
            return true;
        } else {
            $this->helper->apiLog($cmd, $requestUrl, $data, $body, 'Fail');
        }

        return false;
    }

    public function getModuleEnable()
    {
        return $this->helper->getModuleEnable();
    }

    public function getSettingMode()
    {
        return $this->helper->getSettingMode();
    }

    public function getSettingModeCustomers()
    {
        return $this->helper->getsSettingModeCustomers();
    }

}
