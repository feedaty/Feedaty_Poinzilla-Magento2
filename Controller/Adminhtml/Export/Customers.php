<?php
namespace Zoorate\PoinZilla\Controller\Adminhtml\Export;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Customers extends Action
{
    protected $customerCollectionFactory;
    protected $storeManager;
    protected $scopeConfig;

    public function __construct(
        Action\Context $context,
        CollectionFactory $customerCollectionFactory,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    public function execute()
    {
        $fileName = 'customers.csv';
        $content = $this->getCsvFileContent();

        return $this->resultFactory->create(ResultFactory::TYPE_RAW)
            ->setHeader('Content-Type', 'text/csv')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"')
            ->setContents($content);
    }

    private function getCsvFileContent()
    {
        $output = '';
        $headers = ['firstName', 'lastName', 'email', 'externalID', 'points', 'group', 'culture']; // ✅ Aggiunto il campo 'culture'
        $output .= implode(';', $headers) . "\n";

        $customerCollection = $this->customerCollectionFactory->create();

        foreach ($customerCollection as $customer) {
            $firstName = $customer->getFirstname() ?: 'default';
            $lastName = $customer->getLastname() ?: 'default';
            $email = $customer->getEmail();
            $externalID = $customer->getId();
            $points = 0;
            $group = $customer->getGroupId();

            // ✅ Ottenere la cultura del cliente basata sul negozio
            $storeId = $customer->getStoreId();
            $localeCode = $this->scopeConfig->getValue(
                'general/locale/code',
                ScopeInterface::SCOPE_STORE,
                $storeId
            );

            // ✅ Lista delle lingue accettate
            $acceptedCultures = ['it', 'en', 'es', 'fr', 'de'];

            // ✅ Estrarre la parte della lingua prima del "_"
            $culture = substr($localeCode, 0, 2);

            // ✅ Se la lingua non è accettata, impostare il default "en"
            if (!in_array($culture, $acceptedCultures)) {
                $culture = 'en';
            }

            // ✅ Aggiungere il valore della cultura al CSV
            $output .= implode(';', [$firstName, $lastName, $email, $externalID, $points, $group, $culture]) . "\n";
        }

        return $output;
    }
}
