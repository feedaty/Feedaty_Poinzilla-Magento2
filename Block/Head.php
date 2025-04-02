<?php

namespace Zoorate\PoinZilla\Block;

use Magento\Customer\Model\SessionFactory;
use Zoorate\PoinZilla\Helper\Data;
use Magento\Store\Model\StoreManagerInterface;

class Head extends \Magento\Framework\View\Element\Template
{
    private $options = [];

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    protected $sessionFactory;

    protected $helper;

    protected $storeManager;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param SessionFactory $sessionFactory
     * @param Data $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        SessionFactory $sessionFactory,
        StoreManagerInterface $storeManager,
        Data $helper,
        array $data = []
    ) {
        $this->storeManager = $storeManager;
        $this->sessionFactory = $sessionFactory;
        $this->helper = $helper;

        // Popoliamo dinamicamente le options
        $this->options = [
            'poinzilla_sdk_file_url'     => $this->helper->getSdkFileUrl(),
            'poinzilla_iframe_site_url'  => $this->helper->getIframeUrl(),
            'poinzilla_api_url'          => $this->helper->getApiUrl()
        ];

        parent::__construct($context, $data);
    }


    public function getOption($key)
    {
        if (isset($this->options[$key])) {
            return $this->options[$key];
        }
        return '';
    }

    public function getHelper()
    {
        return $this->helper;
    }

    public function isCustomerLoggedIn()
    {
        if (!$this->customerSession) {
            $customerSession = $this->sessionFactory->create();
        } else {
            $customerSession = $this->customerSession;
        }
        return $customerSession->isLoggedIn();
    }

    public function getCurrentCustomer()
    {
        if (!$this->customerSession) {
            $customerSession = $this->sessionFactory->create();
        } else {
            $customerSession = $this->customerSession;
        }
        return $customerSession->getCustomer();
    }

    /**
     * @param $user_email
     * @return string
     */
    public function generateDigest($user_email, $storeId = null): string
    {
        return hash_hmac(
            'sha256',
            $this->helper->getMerchantCode($storeId) . $user_email,
            $this->helper->getPrivateKey($storeId)
        );
    }

    public function getCustomerFirstName()
    {
        return $this->getCurrentCustomer()->getFirstname();
    }

    public function getCustomerLastName()
    {
        return $this->getCurrentCustomer()->getLastname();
    }

    public function getReferralCode()
    {
        return $this->_request->getParam('referral_code');
    }

    public function getDefaultView()
    {
        return $this->_request->getParam('default_view');
    }

    /**
     * Restituisce lo store ID corrente
     */
    public function getCurrentStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }
}
