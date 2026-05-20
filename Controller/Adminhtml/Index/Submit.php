<?php

namespace DamConsultants\Akima\Controller\Adminhtml\Index;

use DamConsultants\Akima\Model\ResourceModel\Collection\MetaPropertyCollectionFactory;

class Submit extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;
    /**
     * @var $_helperData
     */
    protected $_helperData;
    /**
     * @var $metaProperty
     */
    protected $metaProperty;
    /**
     * @var $metaPropertyCollectionFactory
     */
    protected $metaPropertyCollectionFactory;

    /**
     * Submit.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \DamConsultants\Akima\Helper\Data $helperData
     * @param \DamConsultants\Akima\Model\MetaPropertyFactory $metaProperty
     * @param MetaPropertyCollectionFactory $metaPropertyCollectionFactory
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \DamConsultants\Akima\Helper\Data $helperData,
        \DamConsultants\Akima\Model\MetaPropertyFactory $metaProperty,
        MetaPropertyCollectionFactory $metaPropertyCollectionFactory,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->_helperData = $helperData;
        $this->metaProperty = $metaProperty;
        $this->metaPropertyCollectionFactory = $metaPropertyCollectionFactory;
        $this->resultPageFactory = $resultPageFactory;
    }
    /**
     * Execute: save the submitted MetaProperty mapping and redirect back to the listing.
     *
     * @return \Magento\Framework\Controller\Result\Redirect|void
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        try {
            $metadata = $this->_helperData->getBynderMetaProperites();
            $data = json_decode($metadata, true);
            $properites_system_slug = $this->getRequest()->getParam('system_slug');
            $select_meta_tag = $this->getRequest()->getParam('select_meta_tag');
            $collection = $this->metaPropertyCollectionFactory->create();
            $meta = [];
            $properties_details = [];
            $all_properties_slug = [];
            
            $get_collection_data = $collection->getData();
            if (count($get_collection_data) > 0) {
                foreach ($get_collection_data as $metacollection) {
                    $properties_details[$metacollection['system_slug']] = [
                        "id" => $metacollection['id'],
                        "property_name" => $metacollection['property_name'],
                        "property_id" => $metacollection['property_id'],
                        "magento_attribute" => $metacollection['magento_attribute'],
                        "attribute_id" => $metacollection['attribute_id'],
                        "bynder_property_slug" => $metacollection['bynder_property_slug'],
                        "system_slug" => $metacollection['system_slug'],
                        "system_name" => $metacollection['system_name'],
                    ];
                }
                
                $all_properties_slug = array_keys($properties_details);
                
                foreach ($properites_system_slug as $key => $form_system_slug) {
                    if (in_array($form_system_slug, $all_properties_slug)) {
                        /* update data */
                        $pro_id = $properties_details[$form_system_slug]["id"];
                        $model = $this->metaProperty->create()->load($pro_id);
                    } else {
                        /* insert data */
                        $model = $this->metaProperty->create();
                    }
                    $model->setData('property_name', $data['data'][$select_meta_tag[$key]]['label']);
                    $model->setData('property_id', $data['data'][$select_meta_tag[$key]]['id']);
                    $model->setData('bynder_property_slug', $data['data'][$select_meta_tag[$key]]['name']);
                    $model->setData('system_slug', $form_system_slug);
                    $model->setData('system_name', $form_system_slug);
                    $model->save();
                }
            } else {
                /* insert all data */
                foreach ($properites_system_slug as $key => $form_system_slug) {
                    $model = $this->metaProperty->create();
                    $model->setData('property_name', $data['data'][$select_meta_tag[$key]]['label']);
                    $model->setData('property_id', $data['data'][$select_meta_tag[$key]]['id']);
                    $model->setData('bynder_property_slug', $data['data'][$select_meta_tag[$key]]['name']);
                    $model->setData('system_slug', $form_system_slug);
                    $model->setData('system_name', $form_system_slug);
                    $model->save();
                }

            }

            $this->messageManager->addSuccessMessage(__('Submitted MetaProperty...!'));
            return $resultRedirect->setPath('bynder/index/metaproperty');
        } catch (\Exception $e) {
            $this->messageManager->addException($e, __('We can\'t submit your request, Please try again.'));
        }
    }
}
