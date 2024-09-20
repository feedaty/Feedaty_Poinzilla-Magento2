<?php

namespace Zoorate\PoinZilla\Controller\Index;

class Test extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_pageFactory;
    protected $external;
    protected $orderFactory;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Zoorate\PoinZilla\Model\Api\PoinZilla\External $external,
        \Magento\Sales\Model\OrderFactory $orderFactory
    ) {
        $this->_pageFactory = $pageFactory;
        $this->external = $external;
        $this->orderFactory = $orderFactory;
        return parent::__construct($context);
    }

    /**
     * View page action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $order = $this->orderFactory->create()->load(7);
        print_r($this->external->createOrder($order));
        exit;
        return $this->_pageFactory->create();
    }
}
