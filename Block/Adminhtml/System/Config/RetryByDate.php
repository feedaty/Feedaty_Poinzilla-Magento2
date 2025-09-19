<?php
namespace Zoorate\PoinZilla\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Exception\LocalizedException;

class RetryByDate extends Field
{
    protected $_template = 'Zoorate_PoinZilla::system/config/retry_by_date.phtml';

    public function __construct(
        Context $context,
        FormKey $formKey,
        array $data = []
    ) {
        $this->_formKey = $formKey;
        parent::__construct($context, $data);
    }

    /**
     * Render block HTML
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        return $this->_toHtml();
    }

    /** Return the post URL for the form
     *
     * @return string
     */
    public function getPostUrl(): string
    {
        return $this->getUrl('poinzilla/retry/run');
    }

    /** Return the form key value
     *
     * @return string
     * @throws LocalizedException
     */
    public function getFormKeyValue(): string
    {
        return $this->_formKey->getFormKey();
    }
}

