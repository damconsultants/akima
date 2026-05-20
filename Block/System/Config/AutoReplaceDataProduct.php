<?php

namespace DamConsultants\Akima\Block\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
use \Magento\Store\Model\StoreManagerInterface;

class AutoReplaceDataProduct extends Field
{
    /**
     * Block template.
     *
     * @var string
     */
    protected $_template = 'DamConsultants_Akima::system/config/comment.phtml';
    /**
     * @var $_storeManager
     */
    protected $_storeManager;
    /**
     * @var $HelperBackend
     */
    protected $HelperBackend;
    /**
     * @var $_toHtml
     */
    protected $_toHtml;
    /**
     * @var $getUrl
     */
    protected $getUrl;

    /**
     * Button
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param \Magento\Backend\Helper\Data $HelperBackend
     * @param array $data
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        \Magento\Backend\Helper\Data $HelperBackend,
        array $data = []
    ) {
        $this->_storeManager = $storeManager;
        $this->HelperBackend = $HelperBackend;
        parent::__construct($context, $data);
    }
    /**
     * Render
     *
     * @return $this
     * @param object $element
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }
    /**
     * Return get Elemrent Html
     *
     * @return string
     * @param object $element
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        
        return $this->_toHtml();
    }
    /**
     * Get Custom Url
     *
     * @return string
     */
    public function getCustomUrl()
    {
        return $this->getUrl();
    }
    /**
     * Get Button Html
     *
     * @return string
     */
    public function getButtonHtml()
    {
        $activeButton = $this->getLayout()
        ->createBlock(\Magento\Backend\Block\Widget\Button::class)
        ->setData([
            'id'      => 'auto_replace_bynder_info',
            'label'   => __("Click here for more information."),
            'onclick' => 'javascript:AutoReplaceDataProduct(); return false;',
        ]);
        return $activeButton->toHtml();
    }
}
