<?php

namespace DamConsultants\Akima\Controller\Index;

use Psr\Log\LoggerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Product\Action as ProductAction;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Controller\ResultFactory;
use DamConsultants\Akima\Model\ResourceModel\Collection\DefaultMetaPropertyCollectionFactory;
use \DamConsultants\Akima\Model\DefaultMetaPropertyFactory;

class SynMetaProperty extends \Magento\Framework\App\Action\Action
{
	protected $_logger;
	protected $_productCollectionFactory;
	protected $productRepository;
	protected $storeManager;
	protected $productAction;
	protected $resultJsonFactory;
	protected $_resultRedirect;
	protected $collection;
	protected $_helperdata;
	protected $defaultMetaPropertyFactory;
	protected $_productRepositoryModel;
	protected $messageManager;
    /**
     * Get
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \DamConsultants\Akima\Helper\Data $HelperData
     * @param \Magento\Framework\Controller\Result\JsonFactory $jsonFactory
     * @param DefaultMetaPropertyFactory $DefaultMetaPropertyFactory
     * @param DefaultMetaPropertyCollectionFactory $collection
     * @param StoreManagerInterface $storeManager
     * @param ProductRepository $productRepositoryModel
     * @param ProductAction $action
     * @param \Psr\Log\LoggerInterface $logger
     * @param ResultFactory $result
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \DamConsultants\Akima\Helper\Data $HelperData,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        DefaultMetaPropertyFactory $DefaultMetaPropertyFactory,
        DefaultMetaPropertyCollectionFactory $collection,
        StoreManagerInterface $storeManager,
        ProductRepository $productRepositoryModel,
        ProductAction $action,
        \Psr\Log\LoggerInterface $logger,
        ResultFactory $result,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->_logger = $logger;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->productRepository = $productRepository;
        $this->storeManager = $storeManager;
        $this->productAction = $action;
        $this->resultJsonFactory = $jsonFactory;
        $this->_resultRedirect = $result;
        $this->collection = $collection;
        $this->_helperdata = $HelperData;
        $this->defaultMetaPropertyFactory = $DefaultMetaPropertyFactory;
        $this->_productRepositoryModel = $productRepositoryModel;
        $this->messageManager = $messageManager;
        return parent::__construct($context);
    }
    /**
     * Execute
     *
     * @return $this
     */
    public function execute()
	{
		$get_meta_data = $this->getDefaultMetaData();
		$metaproperties_data = $get_meta_data['metadata'];
		$resultRedirect = $this->resultRedirectFactory->create();
		$result = $this->resultJsonFactory->create();
		$model = $this->defaultMetaPropertyFactory->create();
		$metaCollection = $this->collection->create();
		$meta_property = [];

		// Gather existing meta properties in the database
		if (count($metaCollection) > 0) {
			foreach ($metaCollection as $collection) {
				$meta_property[] = $collection['property_id'];
			}
		}

		if (isset($metaproperties_data)) {
			if ($metaproperties_data['status'] == 1) {
				// Loop through the incoming metaproperty data
				foreach ($metaproperties_data['data'] as $key => $meta) {
					$p_id = $meta['id'];

					if (!in_array($p_id, $meta_property)) {
						// Insert new properties that are not in the database
						$data =  [
							'property_name' => $meta['label'],
							'property_id' => $p_id,
							'bynder_property_slug' => $key,
							'property_search_query' => "",
							'possible_values' => "",
							'status' => 1 // Setting new property status to 1
						];

						if ($model->setData($data)->save()) {
							$message = __('Data Sync Successfully..!');
							$result_data = $result->setData(['status' => 1, 'message' => 'Data Sync Successfully..!']);
						} else {
							$message = __('Data not Sync..!');
							$result_data = $result->setData(['status' => 0, 'message' => 'Data not Sync..!']);
						}
					} else {
						$message = __("New Data Not Available That's Why Data Not Sync ..!");
						$result_data = $result->setData([
							'status' => 0,
							'message' => "New Data Not Available That's Why Data Not Sync ..!"
						]);
					}
				}

				// Update status to 0 for properties in the database that are not in the incoming data
				$incoming_ids = array_column($metaproperties_data['data'], 'id'); // Get all incoming property IDs
				foreach ($metaCollection as $collection) {
					if (!in_array($collection['property_id'], $incoming_ids)) {
						echo $collection['property_id']. "<br>";
						// Update the status of properties that are not in the incoming data to 0
						$model->load($collection['id'])->setStatus(0)->save();
					}
				}
			} else {
				$message = __('Data not Sync..!');
				$result_data = $result->setData(['status' => 0, 'message' => 'No metaproperties found!']);
			}
			
			$this->messageManager->addSuccessMessage($message);
			return $result_data;
		}
	}
    /**
     * Get Default Meta Data
     */
    public function getDefaultMetaData()
    {
        $property_name = "";
        $response_data = [];
        $newArr = [];
        $attribute_array = [];
        $metadata = $this->_helperdata->getBynderMetaProperites();
        $metaproperty_repsonse = json_decode($metadata, true);
        
        /* $total_items = count($metaproperty_repsonse);
        if($total_items > 0){
            foreach($metaproperty_repsonse as $key=>$v){
                $attribute_array['data'][$v['id']] = $key;
                $attribute_array['key_data'][$key] = $v['id'];
            }
        } */

        if (count($metaproperty_repsonse) > 0) {
            $response_data['metadata'] = $metaproperty_repsonse;
        } else {
            $response_data['metadata'] = [];
        }
        return $response_data;
    }
}
