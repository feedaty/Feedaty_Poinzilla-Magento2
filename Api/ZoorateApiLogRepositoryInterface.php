<?php

namespace Zoorate\PoinZilla\Api;

interface ZoorateApiLogRepositoryInterface
{
    /**
     * Save zoorate_api_log
     * @param \Zoorate\PoinZilla\Api\Data\ZoorateApiLogInterface $zoorateApiLog
     * @return \Zoorate\PoinZilla\Api\Data\ZoorateApiLog
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Zoorate\PoinZilla\Api\Data\ZoorateApiLogInterface $zoorateApiLog
    );

    /**
     * Retrieve zoorate_api_log
     * @param string $id
     * @return \Zoorate\PoinZilla\Api\Data\ZoorateApiLogInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($id);

    /**
     * Delete zoorate_api_log
     * @param \Zoorate\PoinZilla\Api\Data\ZoorateApiLogInterface $zoorateApiLog
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Zoorate\PoinZilla\Api\Data\ZoorateApiLogInterface $zoorateApiLog
    );

    /**
     * Delete zoorate_api_log by ID
     * @param string $id
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($id);
}
