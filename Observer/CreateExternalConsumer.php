<?php

namespace Zoorate\PoinZilla\Observer;

use Zoorate\PoinZilla\Model\Api\PoinZilla\External;

class CreateExternalConsumer implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var External
     */
    protected $externalApi;

    public function __construct(
        External $externalApi
    )
    {
        $this->externalApi = $externalApi;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->externalApi->getModuleEnable()) {
            $customer = $observer->getData('customer');

            if($this->externalApi->getSettingMode()) {
                $customerEmail = $customer->getEmail();

                $setting_mode_customers = explode(',', $this->externalApi->getSettingModeCustomers() ?? '');

                if (in_array($customerEmail, $setting_mode_customers)) {
                    $this->externalApi->createConsumer($customer);
                }
            }
            else {
                $this->externalApi->createConsumer($customer);
            }

        }
    }
}
