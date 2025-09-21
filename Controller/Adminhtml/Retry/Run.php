<?php
namespace Zoorate\PoinZilla\Controller\Adminhtml\Retry;

use Magento\Backend\App\Action;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Zoorate\PoinZilla\Model\RetryByDateProcessor;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class Run extends Action
{
    const ADMIN_RESOURCE = 'Zoorate_PoinZilla::configuration';

    private TimezoneInterface $timezone;
    private RetryByDateProcessor $processor;
    protected StoreManagerInterface $storeManager;
    private LoggerInterface $logger;
    private Validator $formKeyValidator;

    public function __construct(
        Action\Context $context,
        TimezoneInterface $timezone,
        RetryByDateProcessor $processor,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger,
        Validator $formKeyValidator
    ) {
        parent::__construct($context);
        $this->timezone = $timezone;
        $this->processor = $processor;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->formKeyValidator = $formKeyValidator;
    }

    // in __construct: private \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator

    public function execute()
    {
        $result = $this->resultRedirectFactory->create();

        // valida form_key se presente (anche via GET)
        if (!$this->formKeyValidator->validate($this->getRequest())) {
            $this->messageManager->addErrorMessage(__('Invalid form key.'));
            return $result->setRefererUrl();
        }

        $fromS   = trim((string)$this->getRequest()->getParam('from', ''));
        $toS     = trim((string)$this->getRequest()->getParam('to', ''));
        $website = (string)$this->getRequest()->getParam('website', '');
        $store   = (string)$this->getRequest()->getParam('store', '');

        //Logging
        $this->logger->info("Retry Run called with parameters: from=$fromS, to=$toS, website=$website, store=$store");


        if ($fromS === '' || $toS === '') {
            $this->messageManager->addErrorMessage(__('Invalid parameters. Select both dates.'));
            return $result->setRefererUrl();
        }

        try {
            $tzId = $this->timezone->getConfigTimezone() ?: 'UTC';
            $tz   = new \DateTimeZone($tzId);
            $from = \DateTime::createFromFormat('Y-m-d H:i:s', $fromS.' 00:00:00', $tz);
            $to   = \DateTime::createFromFormat('Y-m-d H:i:s', $toS.' 23:59:59', $tz);
            $errs = \DateTime::getLastErrors();
            if (!$from || !$to || !empty($errs['warning_count']) || !empty($errs['error_count']) || $from > $to) {
                $this->messageManager->addErrorMessage(__('Invalid date range.'));
                return $result->setRefererUrl();
            }

            // scope → storeIds (come già fatto)
            $storeIds = null;
            if ($store !== '') {
                $storeIds = [(int)$this->storeManager->getStore($store)->getId()];
            } elseif ($website !== '') {
                $websiteModel = $this->storeManager->getWebsite($website);
                $stores = $websiteModel->getStores();
                if (!$stores) {
                    $this->messageManager->addErrorMessage(__('The website "%1" has no associated stores.', $website));
                    return $result->setRefererUrl();
                }
                $storeIds = array_map(static fn($s) => (int)$s->getId(), $stores);
            }

            //log storeIds
            $this->logger->info('Retry Run storeIds: ' . ($storeIds ? implode(',', $storeIds) : 'all'));

            [$ok,$ko] = $this->processor->run($from,$to,$storeIds);
            $this->messageManager->addSuccessMessage(
                __('Retry executed. Success: %1 — Failures: %2', $ok, $ko)
            );
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage(
                __('A server error occurred while forcing retries.')
            );
        }

        return $result->setRefererUrl();
    }

}
