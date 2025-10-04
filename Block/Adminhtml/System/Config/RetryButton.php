<?php
declare(strict_types=1);

namespace Zoorate\PoinZilla\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\UrlInterface;

class RetryButton extends Field
{
    protected $_template = 'Zoorate_PoinZilla::system/config/retry_button.phtml';

    public function __construct(
        Context $context,
        private UrlInterface $urlBuilder,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    protected function _getElementHtml(AbstractElement $element)
    {
        // come ExportButton: deleghiamo al template
        return $this->_toHtml();
    }

    /**
     * Base URL del controller admin, con secret key e scope corrente già applicati.
     * La JS aggiungerà from/to (e form_key opzionale) come querystring.
     */
    public function getBaseUrl(): string
    {
        $website = (string)$this->getRequest()->getParam('website');
        $store   = (string)$this->getRequest()->getParam('store');

        return $this->urlBuilder->getUrl('poinzilla/retry/run', [
            '_current' => true, // mantiene key/section/route params
            '_query'   => [
                'website' => $website,
                'store'   => $store,
                // non aggiungiamo from/to qui; li mette il JS leggendo i datepicker
            ],
        ]);
    }

    /** Etichetta human-readable come nel tuo ExportButton */
    public function getScopeLabel(): string
    {
        $website = (string)$this->getRequest()->getParam('website');
        $store   = (string)$this->getRequest()->getParam('store');

        if ($store !== '') {
            return (string)__('for Store View %1', $store);
        }
        if ($website !== '') {
            return (string)__('for Website %1', $website);
        }
        return (string)__('for Default Config');
    }

    public function getButtonHtml(): string
    {
        $label = __('Force retry') . ' ' . $this->getScopeLabel();

        return $this->getLayout()->createBlock(\Magento\Backend\Block\Widget\Button::class)
            ->setType('button')
            ->setClass('action-default scalable save primary')
            // onclick delega a funzione JS che costruisce la URL con from/to
            ->setOnClick("pzRetryRun()")
            ->setLabel($label)
            ->toHtml();
    }
}
