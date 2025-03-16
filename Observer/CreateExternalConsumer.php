<?php

namespace Zoorate\PoinZilla\Observer;

use Magento\Framework\Event\Observer;
use Zoorate\PoinZilla\Model\Api\PoinZilla\External;

/**
 * Class CreateExternalConsumer
 * @package Zoorate\PoinZilla\Observer
 */
class CreateExternalConsumer implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var External
     */
    protected External $externalApi;

    /**
     * CreateExternalConsumer constructor.
     * @param External $externalApi
     */
    public function __construct(
        External $externalApi
    )
    {
        $this->externalApi = $externalApi;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $customer = $observer->getData('customer');

        // ✅ Ottenere lo store ID associato al cliente
        $storeId = $customer->getStoreId();

        if ($this->externalApi->getModuleEnable($storeId)) {

            if ($this->externalApi->getSettingMode($storeId)) {
                $customerEmail = $customer->getEmail();

                // ✅ Passare lo store ID per ottenere la configurazione corretta
                $setting_mode_customers = $this->externalApi->getSettingModeCustomers($storeId);
                $setting_mode_customers = explode(',', $setting_mode_customers);

                if (in_array($customerEmail, $setting_mode_customers)) {
                    $this->externalApi->createConsumer($customer, $storeId);
                }
            } else {
                // ✅ Passare lo store ID per assicurarsi che il consumatore sia creato con la giusta configurazione
                $this->externalApi->createConsumer($customer, $storeId);
            }
        }
    }
}
