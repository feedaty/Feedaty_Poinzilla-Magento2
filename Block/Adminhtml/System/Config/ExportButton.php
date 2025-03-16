<?php

namespace Zoorate\PoinZilla\Block\Adminhtml\System\Config;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\UrlInterface;

class ExportButton extends \Magento\Config\Block\System\Config\Form\Field
{
    protected $_template = 'Zoorate_PoinZilla::system/config/export_button.phtml';

    protected $urlBuilder;

    public function __construct(
        Context $context,
        UrlInterface $urlBuilder,
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $data);
    }

    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    public function getAjaxUrl()
    {
        $websiteId = $this->getRequest()->getParam('website');
        $storeId = $this->getRequest()->getParam('store');

        return $this->urlBuilder->getUrl('poinzilla/export/customers', [
            'website' => $websiteId,
            'store' => $storeId
        ]);
    }

    public function getButtonHtml()
    {
        $websiteId = $this->getRequest()->getParam('website');
        $storeId = $this->getRequest()->getParam('store');

        // Creazione di un'etichetta più leggibile
        $scopeLabel = '';
        if ($storeId) {
            $scopeLabel = __('for Store View ID %1', $storeId);
        } elseif ($websiteId) {
            $scopeLabel = __('for Website ID %1', $websiteId);
        }

        return $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button')
            ->setType('button')
            ->setLabel(__('Export Customers') . ' ' . $scopeLabel)
            ->setOnClick("setLocation('" . $this->getAjaxUrl() . "')")
            ->toHtml();
    }
}
