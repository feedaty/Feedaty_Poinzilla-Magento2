<?php

namespace Zoorate\PoinZilla\Model\ResourceModel;

class ZoorateApiLog extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init('zoorate_api_log', 'id');
    }
}
