<?php

namespace Zoorate\PoinZilla\Observer;

use Psr\Log\LoggerInterface;
use Zoorate\PoinZilla\Model\Api\PoinZilla\External;

class SalesOrderSaveAfter implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var External
     */
    protected External $externalApi;

    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * @param LoggerInterface $logger
     * @param External $externalApi
     */
    public function __construct(
        LoggerInterface $logger,
        External $externalApi
    ) {
        $this->logger = $logger;
        $this->externalApi = $externalApi;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->externalApi->getModuleEnable()) {
            $this->logger->info('Zoorate PoinZilla : Start SalesOrderSaveAfter');
            $order = $observer->getEvent()->getOrder();
            if ($order instanceof \Magento\Framework\Model\AbstractModel) {
                $oldStatus = $order->getOrigData('status');
                $newStatus = $order->getStatus();
                if($this->externalApi->getSettingMode()) {
                    $customerEmail = $order->getCustomerEmail();
                    $this->logger->info('Zoorate PoinZilla : Setting Mode is enable');
                    $setting_mode_customers = explode(',', $this->externalApi->getSettingModeCustomers() ?? '');
                    if (in_array($customerEmail, $setting_mode_customers)) {
                        if ($oldStatus != $newStatus) {
                            $this->externalApi->createOrder($order);
                        }
                    }
                }
                else {
                    if ($oldStatus != $newStatus) {
                        $this->externalApi->createOrder($order);
                    }
                }
            }
        }
    }
}
