<?php

namespace Zoorate\PoinZilla\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Encryption\EncryptorInterface;
use Zoorate\PoinZilla\Api\Data\ZoorateApiLogInterfaceFactory;
use Zoorate\PoinZilla\Model\ZoorateApiLogRepository;

class Data extends AbstractHelper
{
    const XML_PATH_POINZILLA = 'poinzilla/';

    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var ZoorateApiLogInterfaceFactory
     */
    protected $zoorateApiLogInterfaceFactory;

    /**
     * @var ZoorateApiLogRepository
     */
    protected $zoorateApiLogRepository;

    public function __construct(
        Context $context,
        EncryptorInterface $encryptor,
        ZoorateApiLogInterfaceFactory $zoorateApiLogInterfaceFactory,
        ZoorateApiLogRepository $zoorateApiLogRepository
    )
    {
        parent::__construct($context);
        $this->encryptor = $encryptor;
        $this->zoorateApiLogInterfaceFactory = $zoorateApiLogInterfaceFactory;
        $this->zoorateApiLogRepository = $zoorateApiLogRepository;
    }

    public function getConfigValue($field, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $field,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getGeneralConfig($code, $storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_POINZILLA . 'general/' . $code, $storeId);
    }

    public function getModuleEnable($storeId = null)
    {
        return $this->getGeneralConfig('enable', $storeId);
    }

    public function getSettingMode($storeId = null)
    {
        return $this->getGeneralConfig('setting_mode', $storeId);
    }

    public function getsSettingModeCustomers($storeId = null)
    {
        return $this->getGeneralConfig('setting_mode_customers', $storeId);
    }

    public function getMerchantCode($storeId = null)
    {
        return $this->getGeneralConfig('merchant_code', $storeId);
    }

    public function getPublicKey($storeId = null)
    {
        return $this->getGeneralConfig('public_key', $storeId);
    }

    public function getPrivateKey($storeId = null)
    {
        return $this->getGeneralConfig('private_key', $storeId);
    }

    public function getSdkFileUrl()
    {
        return $this->scopeConfig->getValue('zoorate_poinzilla/sdk/file_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getIframeUrl()
    {
        return $this->scopeConfig->getValue('zoorate_poinzilla/sdk/iframe_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getApiUrl()
    {
        return $this->scopeConfig->getValue('zoorate_poinzilla/sdk/api_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function apiLog($name, $endpoint, $body, $response, $result, $storeId = null)
    {
        $apiLogData = $this->zoorateApiLogInterfaceFactory->create();
        $apiLogData->setCallName($name);
        $apiLogData->setCallEndpoint($endpoint);
        $apiLogData->setCallBody(json_encode($body));
        $response = json_decode((string)$response, true);
        $apiLogData->setCallResponse(json_encode($response));
        $apiLogData->setCallResult($result);
        $apiLogData->setStoreId($storeId);

        $this->zoorateApiLogRepository->save($apiLogData);
    }
}
