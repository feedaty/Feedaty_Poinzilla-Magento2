<?php

namespace Zoorate\PoinZilla\Model;

use Magento\Framework\Model\AbstractModel;
use Zoorate\PoinZilla\Api\Data\ZoorateApiLogInterface;

class ZoorateApiLog extends AbstractModel implements ZoorateApiLogInterface
{
    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(\Zoorate\PoinZilla\Model\ResourceModel\ZoorateApiLog::class);
    }

    /**
     * @inheritDoc
     */
    public function getId()
    {
        return $this->getData(self::ID);
    }

    /**
     * @inheritDoc
     */
    public function setId($id)
    {
        return $this->setData(self::ID, $id);
    }

    /**
     * @inheritDoc
     */
    public function getCallName()
    {
        return $this->getData(self::CALL_NAME);
    }

    /**
     * @inheritDoc
     */
    public function setCallName($callName)
    {
        return $this->setData(self::CALL_NAME, $callName);
    }

    /**
     * @inheritDoc
     */
    public function getCallEndpoint()
    {
        return $this->getData(self::CALL_ENDPOINT);
    }

    /**
     * @inheritDoc
     */
    public function setCallEndpoint($callEndpoint)
    {
        return $this->setData(self::CALL_ENDPOINT, $callEndpoint);
    }

    /**
     * @inheritDoc
     */
    public function getCallBody()
    {
        return $this->getData(self::CALL_BODY);
    }

    /**
     * @inheritDoc
     */
    public function setCallBody($callBody)
    {
        return $this->setData(self::CALL_BODY, $callBody);
    }

    /**
     * @inheritDoc
     */
    public function getCallResponse()
    {
        return $this->getData(self::CALL_RESPONSE);
    }

    /**
     * @inheritDoc
     */
    public function setCallResponse($callResponse)
    {
        return $this->setData(self::CALL_RESPONSE, $callResponse);
    }

    /**
     * @inheritDoc
     */
    public function getCallResult()
    {
        return $this->getData(self::CALL_RESULT);
    }

    /**
     * @inheritDoc
     */
    public function setCallResult($callResult)
    {
        return $this->setData(self::CALL_RESULT, $callResult);
    }

    /**
     * @inheritDoc
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }
}
