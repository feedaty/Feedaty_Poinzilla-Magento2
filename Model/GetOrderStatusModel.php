<?php

namespace Zoorate\PoinZilla\Model;

use Zoorate\PoinZilla\Api\GetOrderStatus;

class GetOrderStatusModel implements GetOrderStatus {

    protected $statusCollectionFactory;
    public function __construct(
        \Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory $statusCollectionFactory
    ) {
        $this->statusCollectionFactory = $statusCollectionFactory;
    }

    public function getOrderStatusArray() {
        return $this->statusCollectionFactory->create()->toOptionArray();
    }
}
