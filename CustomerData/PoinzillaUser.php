<?php
namespace Zoorate\PoinZilla\CustomerData;

use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Customer\Model\Session;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Zoorate\PoinZilla\Helper\Data as PoinzillaHelper;
use Magento\Store\Model\StoreManagerInterface;

class PoinzillaUser implements SectionSourceInterface
{
    protected $session;
    protected $customerRepository;
    protected $helper;
    protected $storeManager;

    public function __construct(
        Session $session,
        CustomerRepositoryInterface $customerRepository,
        PoinzillaHelper $helper,
        StoreManagerInterface $storeManager
    ) {
        $this->session = $session;
        $this->customerRepository = $customerRepository;
        $this->helper = $helper;
        $this->storeManager = $storeManager;
    }

    public function getSectionData(): array
    {
        if (!$this->session->isLoggedIn()) {
            return [];
        }

        $storeId = $this->storeManager->getStore()->getId();
        $customer = $this->customerRepository->getById($this->session->getCustomerId());
        $email = $customer->getEmail();
        $digest = hash_hmac(
            'sha256',
            $this->helper->getMerchantCode($storeId) . $email,
            $this->helper->getPrivateKey($storeId)
        );

        return [
            'firstname' => $customer->getFirstname(),
            'lastname' => $customer->getLastname(),
            'email' => $email,
            'digest' => $digest,
            'group_id' => $customer->getGroupId(),
        ];
    }
}
