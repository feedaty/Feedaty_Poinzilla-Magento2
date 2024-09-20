<?php

namespace Zoorate\PoinZilla\Api\Data;

interface ZoorateApiLogInterface
{
    const ID = 'id';
    const CALL_RESULT = 'call_result';
    const CALL_NAME = 'call_name';
    const CALL_ENDPOINT = 'call_endpoint';
    const CALL_BODY = 'call_body';
    const CALL_RESPONSE = 'call_response';
    const CREATED_AT = 'created_at';

    /**
     * Get id
     * @return string|null
     */
    public function getId();

    /**
     * Set id
     * @param string $id
     * @return \Zoorate\PoinZilla\Api\Data\ZoorateApiLog
     */
    public function setId($id);

    /**
     * Get call_name
     * @return string|null
     */
    public function getCallName();

    /**
     * Set call_name
     * @param string $callName
     * @return \Zoorate\PoinZilla\Api\Data\ZoorateApiLog
     */
    public function setCallName($callName);

    /**
     * Get call_endpoint
     * @return string|null
     */
    public function getCallEndpoint();

    /**
     * Set call_endpoint
     * @param string $callEndpoint
     * @return \Zoorate\PoinZilla\Api\Data\ZoorateApiLog
     */
    public function setCallEndpoint($callEndpoint);

    /**
     * Get call_body
     * @return string|null
     */
    public function getCallBody();

    /**
     * Set call_body
     * @param string $callBody
     * @return \Zoorate\PoinZilla\Api\Data\ZoorateApiLog
     */
    public function setCallBody($callBody);

    /**
     * Get call_response
     * @return string|null
     */
    public function getCallResponse();

    /**
     * Set call_response
     * @param string $callResponse
     * @return \Zoorate\PoinZilla\Api\Data\ZoorateApiLog
     */
    public function setCallResponse($callResponse);

    /**
     * Get call_result
     * @return string|null
     */
    public function getCallResult();

    /**
     * Set call_result
     * @param string $callResult
     * @return \Zoorate\PoinZilla\Api\Data\ZoorateApiLog
     */
    public function setCallResult($callResult);

    /**
     * Get created_at
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Set created_at
     * @param string $createdAt
     * @return \Zoorate\PoinZilla\Api\Data\ZoorateApiLog
     */
    public function setCreatedAt($createdAt);
}
