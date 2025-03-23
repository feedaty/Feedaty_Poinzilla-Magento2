<?php

namespace Zoorate\PoinZilla\Observer;

use Psr\Log\LoggerInterface;
use Zoorate\PoinZilla\Model\Api\PoinZilla\External;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

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

    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();

        if ($order instanceof \Magento\Framework\Model\AbstractModel) {
            // ✅ Ottenere lo Store ID dell'ordine
            $storeId = $order->getStoreId();

            if ($this->externalApi->getModuleEnable($storeId)) {

                $oldStatus = $order->getOrigData('status');
                $newStatus = $order->getStatus();

                if ($this->externalApi->getSettingMode($storeId)) {
                    $customerEmail = $order->getCustomerEmail();

                    $setting_mode_customers = explode(',', $this->externalApi->getSettingModeCustomers($storeId) ?? '');

                    if (in_array($customerEmail, $setting_mode_customers)) {
                        if ($oldStatus != $newStatus) {
                            // ✅ Passiamo lo Store ID a `createOrder()`
                            $this->externalApi->createOrder($order, $storeId);
                        }
                    }
                    else {
                        $this->logger->warning('PoinZilla : Customer not in setting mode');
                    }
                } else {
                    if ($oldStatus != $newStatus) {
                        // ✅ Passiamo lo Store ID a `createOrder()`
                        $this->externalApi->createOrder($order, $storeId);
                    }
                }
            }
        }
    }
}
