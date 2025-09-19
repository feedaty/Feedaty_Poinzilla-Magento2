<?php
namespace Zoorate\PoinZilla\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;

class RetryButton extends Field
{
    protected $_template = 'Zoorate_PoinZilla::system/config/retry_button.phtml';

    public function __construct(Context $context, array $data = [])
    {
        parent::__construct($context, $data);
    }

    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    public function getPostUrl(): string
    {
        return $this->getUrl('poinzilla/retry/run'); // Controller admin
    }
}
