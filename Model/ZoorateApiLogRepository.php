<?php

namespace Zoorate\PoinZilla\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Zoorate\PoinZilla\Api\Data\ZoorateApiLogInterface;
use Zoorate\PoinZilla\Api\Data\ZoorateApiLogInterfaceFactory;
use Zoorate\PoinZilla\Api\ZoorateApiLogRepositoryInterface;
use Zoorate\PoinZilla\Model\ResourceModel\ZoorateApiLog as ResourceZoorateApiLog;
use Zoorate\PoinZilla\Model\ResourceModel\ZoorateApiLog\CollectionFactory as ZoorateApiLogCollectionFactory;

class ZoorateApiLogRepository implements ZoorateApiLogRepositoryInterface
{
    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;

    /**
     * @var ZoorateApiLog
     */
    protected $searchResultsFactory;

    /**
     * @var ZoorateApiLogCollectionFactory
     */
    protected $ZoorateApiLogCollectionFactory;

    /**
     * @var ResourceZoorateApiLog
     */
    protected $resource;

    /**
     * @var ZoorateApiLogInterfaceFactory
     */
    protected $ZoorateApiLogFactory;


    /**
     * @param ResourceZoorateApiLog $resource
     * @param ZoorateApiLogInterfaceFactory $ZoorateApiLogFactory
     * @param ZoorateApiLogCollectionFactory $ZoorateApiLogCollectionFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        ResourceZoorateApiLog $resource,
        ZoorateApiLogInterfaceFactory $ZoorateApiLogFactory,
        ZoorateApiLogCollectionFactory $ZoorateApiLogCollectionFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->ZoorateApiLogFactory = $ZoorateApiLogFactory;
        $this->ZoorateApiLogCollectionFactory = $ZoorateApiLogCollectionFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * @inheritDoc
     */
    public function save(ZoorateApiLogInterface $ZoorateApiLog)
    {
        try {
            $this->resource->save($ZoorateApiLog);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the ZoorateApiLog: %1',
                $exception->getMessage()
            ));
        }
        return $ZoorateApiLog;
    }

    /**
     * @inheritDoc
     */
    public function get($id)
    {
        $ZoorateApiLog = $this->ZoorateApiLogFactory->create();
        $this->resource->load($ZoorateApiLog, $id);
        if (!$ZoorateApiLog->getId()) {
            throw new NoSuchEntityException(__('zoorate_api_log with id "%1" does not exist.', $id));
        }
        return $ZoorateApiLog;
    }

    /**
     * @inheritDoc
     */
    public function delete(ZoorateApiLogInterface $ZoorateApiLog)
    {
        try {
            $ZoorateApiLogModel = $this->ZoorateApiLogFactory->create();
            $this->resource->load($ZoorateApiLogModel, $ZoorateApiLog->getId());
            $this->resource->delete($ZoorateApiLogModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the zoorate_api_log: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById($id)
    {
        return $this->delete($this->get($id));
    }
}
