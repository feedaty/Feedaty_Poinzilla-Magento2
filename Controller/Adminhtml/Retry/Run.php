<?php
namespace Zoorate\PoinZilla\Controller\Adminhtml\Retry;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Zoorate\PoinZilla\Model\RetryByDateProcessor;

class Run extends Action
{
    const ADMIN_RESOURCE = 'Zoorate_PoinZilla::configuration';

    private JsonFactory $resultJsonFactory;
    private TimezoneInterface $timezone;
    private RetryByDateProcessor $processor;

    public function __construct(
        Action\Context $context,
        JsonFactory $resultJsonFactory,
        TimezoneInterface $timezone,
        RetryByDateProcessor $processor
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->timezone = $timezone;
        $this->processor = $processor;
    }

    public function execute()
    {
        $res = $this->resultJsonFactory->create();
        $from = (string)$this->getRequest()->getParam('from');
        $to   = (string)$this->getRequest()->getParam('to');
        if (!$from || !$to) return $res->setHttpResponseCode(400)->setData(['message'=>__('Parametri non validi.')]);

        try {
            $tz = new \DateTimeZone($this->timezone->getConfigTimezone());
            $fromDt = new \DateTime($from.' 00:00:00', $tz);
            $toDt   = new \DateTime($to  .' 23:59:59', $tz);
            [$ok,$ko] = $this->processor->run($fromDt, $toDt);
            return $res->setData(['message'=>__('Retry eseguita. Successi: %1 — Falliti: %2', $ok, $ko)]);
        } catch (\Throwable $e) {
            return $res->setHttpResponseCode(500)->setData(['message'=>$e->getMessage()]);
        }
    }
}
