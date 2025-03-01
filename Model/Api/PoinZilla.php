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
        return 'https://api.poinzilla.com/';
    }

    /**
     * @return string
     */
    protected function getExternalConsumerEndpoint(): string
    {
        return $this->getEndpoint() . 'api/External/Consumer';
    }

    /**
     * @return string
     */
    protected function getExternalOrderEndpoint(): string
    {
        return $this->getEndpoint() . 'api/External/Order';
    }

    /**
     * @param $cmd
     * @param $data
     * @return bool
     */
    public function postRequest($cmd, $data): bool
    {
        $client = $this->getClient();

        if (in_array($cmd, ["externalConsumer", "externalOrder"])) {
            $requestUrl = ($cmd == "externalConsumer") ? $this->getExternalConsumerEndpoint() : $this->getExternalOrderEndpoint();

            $client->addHeader('Content-Type', 'application/json');
            $client->addHeader('X-loyalty-channel-key', $this->helper->getPrivateKey());

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
            if($statusCode == 200) {
                $this->helper->apiLog($cmd, $requestUrl, $data, $body, 'Pass');
                return true;
            } else {
                $this->helper->apiLog($cmd, $requestUrl, $data, $body, 'Fail');
            }

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
