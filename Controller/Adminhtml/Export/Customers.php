<?php
namespace Zoorate\PoinZilla\Controller\Adminhtml\Export;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;

class Customers extends Action
{
    protected $customerCollectionFactory;

    public function __construct(
        Action\Context $context,
        CollectionFactory $customerCollectionFactory
    ) {
        $this->customerCollectionFactory = $customerCollectionFactory;
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
        $headers = ['firstName', 'lastName', 'email', 'externalID', 'points', 'group'];
        $output .= implode(';', $headers) . "\n";

        $customerCollection = $this->customerCollectionFactory->create();

        foreach ($customerCollection as $customer) {
            $firstName = $customer->getFirstname() ?: 'default';
            $lastName = $customer->getLastname() ?: 'default';
            $email = $customer->getEmail();
            $externalID = $customer->getId();
            $points = 0;
            $group = $customer->getGroupId();

            $output .= implode(';', [$firstName, $lastName, $email, $externalID, $points, $group]) . "\n";
        }

        return $output;
    }
}
