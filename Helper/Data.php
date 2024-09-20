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

    public function getModuleEnable()
    {
        return $this->getGeneralConfig('enable');
    }

    public function getSettingMode()
    {
        return $this->getGeneralConfig('setting_mode');
    }

    public function getsSettingModeCustomers()
    {
        return $this->getGeneralConfig('setting_mode_customers');
    }

    public function getMerchantCode()
    {
        return $this->getGeneralConfig('merchant_code');
    }

    public function getPublicKey()
    {
        return $this->getGeneralConfig('public_key');
    }

    public function getPrivateKey()
    {
        return $this->getGeneralConfig('private_key');
    }
    public function apiLog($name, $endpoint, $body, $response, $result)
    {
        $apiLogData = $this->zoorateApiLogInterfaceFactory->create();
        $apiLogData->setCallName($name);
        $apiLogData->setCallEndpoint($endpoint);
        $apiLogData->setCallBody(json_encode($body));
        $response = json_decode((string)$response, true);
        $apiLogData->setCallResponse(json_encode($response));
        $apiLogData->setCallResult($result);

        $this->zoorateApiLogRepository->save($apiLogData);
    }
}
