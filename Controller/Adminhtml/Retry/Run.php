<?php
namespace Zoorate\PoinZilla\Controller\Adminhtml\Retry;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Zoorate\PoinZilla\Model\RetryByDateProcessor;
use \Magento\Store\Model\StoreManagerInterface;

class Run extends Action
{
    const ADMIN_RESOURCE = 'Zoorate_PoinZilla::configuration';

    private JsonFactory $resultJsonFactory;
    private TimezoneInterface $timezone;
    private RetryByDateProcessor $processor;
    protected StoreManagerInterface $storeManager;

    public function __construct(
        Action\Context $context,
        JsonFactory $resultJsonFactory,
        TimezoneInterface $timezone,
        RetryByDateProcessor $processor,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->timezone = $timezone;
        $this->processor = $processor;
        $this->storeManager = $storeManager;
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $req    = $this->getRequest();

        // 1) Input base
        $fromS   = trim((string)$req->getParam('from', ''));
        $toS     = trim((string)$req->getParam('to', ''));
        $website = trim((string)$req->getParam('website', '')); // opzionale
        $store   = trim((string)$req->getParam('store', ''));   // opzionale

        if ($fromS === '' || $toS === '') {
            return $result->setHttpResponseCode(400)->setData([
                'message' => __('Parametri non validi. Seleziona entrambe le date.')
            ]);
        }

        try {
            // 2) Normalizza date nel timezone admin
            $tzId = $this->timezone->getConfigTimezone() ?: 'UTC';
            $tz   = new \DateTimeZone($tzId);

            $from = \DateTime::createFromFormat('Y-m-d H:i:s', $fromS . ' 00:00:00', $tz);
            $to   = \DateTime::createFromFormat('Y-m-d H:i:s', $toS   . ' 23:59:59', $tz);

            $errors = \DateTime::getLastErrors();
            if (!$from || !$to || !empty($errors['warning_count']) || !empty($errors['error_count'])) {
                return $result->setHttpResponseCode(400)->setData([
                    'message' => __('Formato data non valido. Usa YYYY-MM-DD.')
                ]);
            }
            if ($from > $to) {
                return $result->setHttpResponseCode(400)->setData([
                    'message' => __('La data iniziale non può essere successiva alla finale.')
                ]);
            }

            // 3b) RISOLUZIONE SCOPE → elenco di store_id
            //    - se è passato "store", filtra SOLO quello
            //    - altrimenti, se è passato "website", filtra tutti gli store della website
            //    - altrimenti (default scope) non filtrare (tutti gli store)
            $storeIds = null; // null = tutti

            if ($store !== '') {
                try {
                    $storeModel = $this->storeManager->getStore($store); // accetta id o code
                    $storeIds   = [(int)$storeModel->getId()];
                } catch (\Throwable $e) {
                    return $result->setHttpResponseCode(400)->setData([
                        'message' => __('Store non valido: %1', $store)
                    ]);
                }
            } elseif ($website !== '') {
                try {
                    $websiteModel = $this->storeManager->getWebsite($website); // id o code
                    $stores       = $websiteModel->getStores();
                    if (!$stores) {
                        return $result->setHttpResponseCode(400)->setData([
                            'message' => __('La website "%1" non ha store associati.', $website)
                        ]);
                    }
                    $storeIds = array_map(static fn($s) => (int)$s->getId(), $stores);
                } catch (\Throwable $e) {
                    return $result->setHttpResponseCode(400)->setData([
                        'message' => __('Website non valida: %1', $website)
                    ]);
                }
            }

            // 4) Esecuzione processor (accetta opzionalmente $storeIds)
            [$ok, $ko] = $this->processor->run($from, $to, $storeIds);

            // 5) Risposta JSON
            return $result->setData([
                'message' => __('Retry eseguita. Successi: %1 — Falliti: %2', $ok, $ko),
                'ok'      => $ok,
                'ko'      => $ko
            ]);

        } catch (\Throwable $e) {
            // $this->logger->error('[RetryByDate] '.$e->getMessage());
            return $result->setHttpResponseCode(500)->setData([
                'message' => __('Si è verificato un errore durante la forzatura delle retry.')
            ]);
        }
    }

}
