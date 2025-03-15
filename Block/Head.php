<?php

namespace Zoorate\PoinZilla\Block;

use Magento\Customer\Model\SessionFactory;
use Zoorate\PoinZilla\Helper\Data;

class Head extends \Magento\Framework\View\Element\Template
{
    private $options = [
        'poinzilla_sdk_file_url' => 'https://develop.dev.poinzilla.com/sdk/sdk.umd.js',
        'poinzilla_sdk_css_url' => '',
        'poinzilla_iframe_site_url' => 'https://develop.dev.poinzilla.com/widget/',
        'poinzilla_api_url' => 'https://develop.dev.poinzilla.com/be/'
    ];

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    protected $sessionFactory;

    protected $helper;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param SessionFactory $sessionFactory
     * @param Data $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        SessionFactory $sessionFactory,
        Data $helper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->sessionFactory = $sessionFactory;
        $this->helper = $helper;
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
    public function generateDigest($user_email): string
    {
        return hash_hmac(
            'sha256',
            $this->helper->getMerchantCode() . $user_email,
            $this->helper->getPrivateKey()
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
}
