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
        if ($this->externalApi->getModuleEnable()) {
            $customer = $observer->getData('customer');

            if ($this->externalApi->getSettingMode()) {
                $customerEmail = $customer->getEmail();

                $setting_mode_customers = $this->externalApi->getSettingModeCustomers();
                $setting_mode_customers = explode(',', $setting_mode_customers);
                if (in_array($customerEmail, $setting_mode_customers)) {
                    $this->externalApi->createConsumer($customer);
                }
            } else {
                $this->externalApi->createConsumer($customer);
            }
        }
    }
}
