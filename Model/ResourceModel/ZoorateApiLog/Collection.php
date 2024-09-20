<?php

namespace Zoorate\PoinZilla\Model\ResourceModel\ZoorateApiLog;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @inheritDoc
     */
    protected $_idFieldName = 'id';

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(
            \Zoorate\PoinZilla\Model\ZoorateApiLog::class,
            \Zoorate\PoinZilla\Model\ResourceModel\ZoorateApiLog::class
        );
    }

    protected function _initSelect()
    {
        parent::_initSelect();

        return $this;
    }

    protected function _renderFiltersBefore()
    {
        parent::_renderFiltersBefore();
    }
}
