<?php

namespace DamConsultants\Akima\Cron;

use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Model\Product\Action;
use Magento\Store\Model\StoreManagerInterface;
use DamConsultants\Akima\Model\BynderFactory;
use DamConsultants\Akima\Model\ResourceModel\Collection\MetaPropertyCollectionFactory;
use DamConsultants\Akima\Model\ResourceModel\Collection\BynderSycDataCollectionFactory;

class AutoReplaceFormMagento
{
    /**
     * @var $bynderMediaTable
     */
    protected $_productRepository;
    /**
     * @var $bynderMediaTable
     */
    protected $attribute;
    /**
     * @var $bynderMediaTable
     */
    protected $action;
    /**
     * @var $bynderMediaTable
     */
    protected $datahelper;
    /**
     * @var $bynderMediaTable
     */
    protected $collectionFactory;
    /**
     * @var $bynderMediaTable
     */
    protected $_byndersycData;
    /**
     * @var $bynderMediaTable
     */
    protected $_byndersycDataCollection;
    /**
     * @var $bynderMediaTable
     */
    protected $_resource;
    /**
     * @var $bynderMediaTable
     */
    protected $metaPropertyCollectionFactory;
    /**
     * @var $bynderMediaTable
     */
    protected $storeManagerInterface;
    /**
     * @var $bynderMediaTable
     */
    protected $bynder;
    /**
     * @var $bynderMediaTable
     */
    protected $_logger;
    /**
     * Auto Replace From Magento
     * @param ProductRepository $productRepository
     * @param Attribute $attribute
     * @param Action $action
     * @param StoreManagerInterface $storeManagerInterface
     * @param \DamConsultants\Akima\Helper\Data $DataHelper
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory
     * @param MetaPropertyCollectionFactory $metaPropertyCollectionFactory
     * @param \DamConsultants\Akima\Model\BynderSycDataFactory $byndersycData
     * @param BynderSycDataCollectionFactory $byndersycDataCollection
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Psr\Log\LoggerInterface $logger
     * @param BynderFactory $bynder
     */

    public function __construct(
        ProductRepository $productRepository,
        Attribute $attribute,
        Action $action,
        StoreManagerInterface $storeManagerInterface,
        \DamConsultants\Akima\Helper\Data $DataHelper,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory,
        MetaPropertyCollectionFactory $metaPropertyCollectionFactory,
        \DamConsultants\Akima\Model\BynderSycDataFactory $byndersycData,
        BynderSycDataCollectionFactory $byndersycDataCollection,
        \Magento\Framework\App\ResourceConnection $resource,
        \Psr\Log\LoggerInterface $logger,
        BynderFactory $bynder
    ) {
        $this->_productRepository = $productRepository;
        $this->attribute = $attribute;
        $this->action = $action;
        $this->datahelper = $DataHelper;
        $this->collectionFactory = $collectionFactory;
        $this->_byndersycData = $byndersycData;
        $this->_byndersycDataCollection = $byndersycDataCollection;
        $this->_resource = $resource->getConnection();
        $this->metaPropertyCollectionFactory = $metaPropertyCollectionFactory;
        $this->storeManagerInterface = $storeManagerInterface;
        $this->bynder = $bynder;
        $this->_logger = $logger;
    }
    /**
     * Execute
     *
     * @return boolean
     */
    public function execute()
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/cron.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info("DamConsultants Bynder Add  Cron");
        /*
        $productCollection = $this->attribute->getCollection();
        $productColl = $this->collectionFactory->create()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter(
                [
                    ['attribute' => 'bynder_auto_replace', 'null' => true]
                ]
            )
            ->load();

        $product_sku_limit = $this->datahelper->getProductSkuLimitConfig();
        if (!empty($product_sku_limit)) {
            $productColl->getSelect()->limit($product_sku_limit);
        } else {
            $productColl->getSelect()->limit(50);
        }

        $bynder = [];
        $bynder_attribute = ['bynder_multi_img', 'bynder_document'];
        $collection_data_value = [];
        $collection_data_slug_val = [];
        $property_id = null;

        $collection = $this->metaPropertyCollectionFactory->create()->getData();
        if (count($collection) >= 1) {
            foreach ($collection as $key => $collection_value) {
                $collection_data_value[] = [
                    'id' => $collection_value['id'],
                    'property_name' => $collection_value['property_name'],
                    'property_id' => $collection_value['property_id'],
                    'magento_attribute' => $collection_value['magento_attribute'],
                    'attribute_id' => $collection_value['attribute_id'],
                    'bynder_property_slug' => $collection_value['bynder_property_slug'],
                    'system_slug' => $collection_value['system_slug'],
                    'system_name' => $collection_value['system_name']
                ];
                $collection_data_slug_val[$collection_value['system_slug']] = [
                    'bynder_property_slug' => $collection_value['bynder_property_slug'],
                ];
            }
        } else {
            $logger->info('Please Select The Metaproperty First.....');
        }
        $productSku_array = [];
        foreach ($productCollection as $products) {
            $bynder[] = $products->getAttributeCode();
        }
        if (array_intersect($bynder_attribute, $bynder)) {
            foreach ($productColl as $item) {
                $productSku_array[] = $item->getSku();
            }
            $logger->info("Product_SKU Start");
            $logger->info($productSku_array);
            $logger->info("Product SKU End");
            if (count($productSku_array) > 0) {
                foreach ($productSku_array as $sku) {
                    $get_data=$this->datahelper->getImageSyncWithProperties($sku, $property_id, $collection_data_value);
                    $respon_array = json_decode($get_data, true);
                    if ($respon_array['status'] == 1) {
                        $convert_array = json_decode($respon_array['data'], true);
                        $this->getDataItem($convert_array, $collection_data_slug_val);
                    }
                }
            } else {
                $logger->info('No Data Found For SKU.');
            }
        }
        return $this;
        */
        return true;
    }
    /**
     * Get Data Item
     *
     * @param array $convert_array
     * @param array $collection_data_slug_val
     */
    public function getDataItem($convert_array, $collection_data_slug_val)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/cron.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info("getDataItem funcation called");
        $data_arr = [];
        $data_val_arr = [];
        $temp_arr = [];
        if ($convert_array['status'] != 0) {
            foreach ($convert_array['data'] as $data_value) {
                $image_data = $data_value['thumbnails'];
                $bynder_image_role = $image_data['magento_role_options'];
                $bynder_alt_text = $image_data['img_alt_text'];
                $sku_slug_name = "property_" . $collection_data_slug_val['sku']['bynder_property_slug'];
                $data_sku = $data_value[$sku_slug_name];

                if ($data_value['type'] == "image") {
                    array_push($data_arr, $data_sku[0]);
                    $data_p = [
                        "sku" => $data_sku[0],
                        'image_alt_text' => $bynder_alt_text,
                        "url" => $image_data["image_link"],
                        "type" => $data_value['type'],
                        'magento_image_role' => $bynder_image_role
                    ];
                    array_push($data_val_arr, $data_p);
                } else {
                    if ($data_value['type'] == 'video') {
                        $video_link = $image_data["image_link"] . '@@' . $image_data["webimage"];
                        array_push($data_arr, $data_sku[0]);
                        $data_p = [
                            "sku" => $data_sku[0],
                            "url" => $video_link,
                            "type" => $data_value['type'],
                            'magento_image_role' => $bynder_image_role
                        ];
                        array_push($data_val_arr, $data_p);
                    } else {
                        $doc_name = $data_value["name"];
                        $doc_name_with_space = preg_replace("/[^a-zA-Z]+/", "-", $doc_name);
                        $doc_link = $image_data["image_link"] . '@@' . $doc_name_with_space;
                        array_push($data_arr, $data_sku[0]);
                        $data_p = [
                            "sku" => $data_sku[0],
                            "url" => $doc_link,
                            "type" => $data_value['type'],
                            'magento_image_role' => $bynder_image_role
                        ];
                        array_push($data_val_arr, $data_p);
                    }
                }
            }
        } else {
            $logger->info('No Data Found For API Side.');
        }
        if (count($data_arr) > 0) {
            $this->getProcessItem($data_arr, $data_val_arr, $temp_arr);
        } else {
            $logger->info('No Data Found.');
        }
    }
    /**
     * Get Process Item
     *
     * @param array $data_arr
     * @param array $data_val_arr
     * @param array $temp_arr
     */
    public function getProcessItem($data_arr, $data_val_arr, $temp_arr)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/cron.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info("getProcessItem funcation called");
        if (count($data_arr) > 0) {
            foreach ($data_arr as $key => $temp) {
                $temp_arr[$temp][$data_val_arr[$key]["type"]]["url"][] = $data_val_arr[$key]["url"];
                $img_details_role[$temp][] = $data_val_arr[$key]["magento_image_role"];
                $image_alt_text[$temp][] = $data_val_arr[$key]["image_alt_text"];
            }
            foreach ($temp_arr as $product_sku => $image_value) {

                foreach ($image_value as $kk => $vv) {
                    $img_json = implode(" \n", $vv["url"]);
                    $item_type = $kk;
                    $this->getImageUpdate(
                        $img_json,
                        $product_sku,
                        $item_type,
                        $img_details_role[$product_sku],
                        $image_alt_text[$product_sku]
                    );
                }
            }
        } else {
            $logger->info('No Data Found For Data Array.');
        }
    }
    /**
     * Update Item
     *
     * @return $this
     * @param string $img_json
     * @param string $product_sku
     * @param string $item_type
     * @param string $img_details_role
     * @param string $image_alt_text
     */
    public function getImageUpdate($img_json, $product_sku, $item_type, $img_details_role, $image_alt_text)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/cron.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info("Inner Funcation Called");
        $table_Name = $this->_resource->getTableName("bynder_cron_data");

        try {
            $storeId = $this->storeManagerInterface->getStore()->getId();
            $_product = $this->_productRepository->get($product_sku);
            $product_ids = $_product->getId();
            $image_value = $_product->getBynderMultiImg();
            $doc_value = $_product->getBynderDocument();
            if ($item_type == "image") {
                $this->getImage(
                    $img_json,
                    $image_value,
                    $product_ids,
                    $storeId,
                    $table_Name,
                    $product_sku,
                    $img_details_role,
                    $image_alt_text
                );
            } elseif ($item_type == "document") {
                $this->getDocument($img_json, $doc_value, $product_ids, $storeId, $table_Name, $product_sku);
            } else {
                $this->getVideo($img_json, $image_value, $product_ids, $storeId, $table_Name, $product_sku);
            }
        } catch (\Exception $e) {
            $logger->info($e->getMessage());
        }
    }
    /**
     * Get Image
     *
     * @param string $img_json
     * @param string $image_value
     * @param string $product_ids
     * @param string $storeId
     * @param string $table_Name
     * @param string $product_sku
     * @param string $img_details_role
     * @param string $image_alt_text
     */
    public function getImage(
        $img_json,
        $image_value,
        $product_ids,
        $storeId,
        $table_Name,
        $product_sku,
        $img_details_role,
        $image_alt_text
    ) {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/cron.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info("GetImage Funcation Called");
        $model = $this->_byndersycData->create();
        $media_array = [];
        $updateAttributes = [];
        $pervious_bynder_image = [];
        $check_data_magetno_side = $this->_byndersycDataCollection->create()
            ->addFieldToFilter('sku', $product_sku)
            ->addFieldToFilter('added_on_cron_compactview', '1');
        $data_collection = $check_data_magetno_side->getData();
        $byndeimageconfig = $this->datahelper->byndeimageconfig();
        $img_roles = explode(",", $byndeimageconfig);
        if (!empty($image_value)) {

            $old_image_array = explode(" ", $image_value);

            $trimmed_array = array_map('trim', $old_image_array);
            $trimmed_array_filter = array_filter($trimmed_array);

            $logger->info("old_image_array");
            $logger->info($trimmed_array_filter);
            $logger->info("old_image_array");

            $new_image_array = explode(" \n", $img_json);
            $trimmed_new_array = array_map('trim', $new_image_array);

            $logger->info("new_image_array");
            $logger->info($trimmed_new_array);
            $logger->info("new_image_array");
            $old_value_array = json_decode($image_value, true);
            $old_item_url = [];
            $old_video_value = [];
            if (!empty($old_value_array)) {
                foreach ($old_value_array as $old_value) {
                    $old_item_url[] = $old_value['item_url'];
                    if ($old_value['item_type'] == 'VIDEO') {
                        $old_video_value[] = $old_value;
                    }
                }
            }
            $image_detail = [];

            foreach ($new_image_array as $vv => $value) {
                $item_url = explode("?", $value);
                $image_detail[] = [
                    "item_url" => $item_url[0],
                    "image_alt_text" =>$image_alt_text[$vv],
                    "image_role" => $img_details_role[$vv],
                    "item_type" => 'IMAGE',
                    "thum_url" => $item_url[0]
                ];

                if (count($old_value_array) > 0) {
                    foreach ($old_value_array as $kv => $img) {
                        if ($img['item_type'] == "IMAGE") {
                            if (count($img["image_role"]) > 0 && count($img_details_role[$vv]) > 0) {
                                $result_val = array_diff($img["image_role"], $img_details_role[$vv]);
                                $old_value_array[$kv]["image_role"] = $result_val;
                            }
                        }
                    }
                }

                $total_new_value = count($image_detail);
                if ($total_new_value > 1) {

                    foreach ($image_detail as $nn => $n_img) {
                        if ($n_img['item_type'] == "IMAGE" && $nn != ($total_new_value - 1)) {
                            if (count($n_img["image_role"]) > 0 && count($img_details_role[$vv]) > 0) {
                                $result_val = array_diff($n_img["image_role"], $img_details_role[$vv]);
                                $image_detail[$nn]["image_role"] = $result_val;
                            }
                        }
                    }
                }

                $url_explode = explode("https://", $value);
                $url_filter = array_filter($url_explode);
                foreach ($url_filter as $media_value) {
                    $media_explode = explode("/", $media_value);
                    $image_media_id[] = $media_explode[3];
                }
            }
            $image_merge = array_merge($old_video_value, $image_detail);
            $logger->info("image_merge");
            $logger->info($image_merge);
            $logger->info("image_merge");

            foreach ($data_collection as $data_collection_value) {
                $media_array[] = $data_collection_value['media_id'];
                $pervious_bynder_image[] = $data_collection_value['bynder_data'];
                if (in_array($data_collection_value['bynder_data'], $image_merge)) {
                    unset($image_merge[array_search($data_collection_value['bynder_data'], $image_merge)]);
                }
            }

            $new_image_value = json_encode($image_merge);

            $updateAttributes['bynder_multi_img'] = $new_image_value;
            $this->action->updateAttributes([$product_ids], $updateAttributes, $storeId);
            foreach ($image_merge as $img) {

                $type[] = $img['item_type'];
            }
            $flag = $this->getFlag($type);
            $this->action->updateAttributes([$product_ids], ['bynder_isMain' => $flag], $storeId);
            $new_value_array = [];
            foreach ($new_image_array as $value) {
                $item_url = explode("?", $value);
                $new_value_array[] = $item_url[0];
            }
            $diff_image_new = array_diff($new_value_array, $pervious_bynder_image);

            foreach ($image_media_id as $bynder_media_id) {
                if (in_array($bynder_media_id, $media_array)) {
                    $logger->info("Update Recored");
                    $remove_for_magento = ['remove_for_magento' => '1'];
                    $where = ['sku=?' => $product_sku, 'media_id=?' => $bynder_media_id];
                    $this->_resource->update($table_Name, $remove_for_magento, $where);
                } else {
                    $remove_for_magento = ['remove_for_magento' => '0'];
                    $where = ['sku=?' => $product_sku, 'media_id=?' => $bynder_media_id];
                    $this->_resource->update($table_Name, $remove_for_magento, $where);
                }
            }
            foreach ($diff_image_new as $new_image_data_value) {
                $item_url = explode("?", $new_image_data_value);
                $image_url_explode = explode("https://", $new_image_data_value);
                $image_url_filter = array_filter($image_url_explode);
                foreach ($image_url_filter as $image_media_value) {
                    $image_media_explode = explode("/", $image_media_value);
                    $data_value_1 = [
                        'sku' => $product_sku,
                        'bynder_data' => $item_url[0],
                        'bynder_data_type' => '1',
                        'media_id' => $image_media_explode[3],
                        'remove_for_magento' => '1',
                        'added_on_cron_compactview' => '1',
                        'added_date' => time()
                    ];
                    $model->setData($data_value_1);
                    $model->save();
                }
            }
        } else {
            $logger->info("Empty image_value");
            $logger->info($image_value);
            $logger->info("Empty image_value");
        }
    }
    /**
     * Get Document
     *
     * @param string $img_json
     * @param string $doc_value
     * @param string $product_ids
     * @param string $storeId
     * @param string $table_Name
     * @param string $product_sku
     */
    public function getDocument($img_json, $doc_value, $product_ids, $storeId, $table_Name, $product_sku)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/cron.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info("GetDocument Funcation Called");
        $model = $this->_byndersycData->create();
        $updateAttributes = [];
        $check_data_magetno_side = $this->_byndersycDataCollection->create()
            ->addFieldToFilter('sku', $product_sku)
            ->addFieldToFilter('added_on_cron_compactview', '1');
        $data_collection = $check_data_magetno_side->getData();
        $old_doc_array = explode(" ", $doc_value);
        $trimmed_doc_array = array_map('trim', $old_doc_array);
        $trimmed_doc_array_filter = array_filter($trimmed_doc_array);

        $new_doc_array = explode(" \n", $img_json);
        $trimmed_new_doc_array = array_map('trim', $new_doc_array);
        $diff_doc_array = array_diff($trimmed_new_doc_array, $trimmed_doc_array_filter);
        $old_value_array = json_decode($doc_value, true);
        $old_item_url = [];
        if (!empty($old_value_array)) {
            foreach ($old_value_array as $value) {
                $old_item_url[] = $value['item_url'];
            }
        }
        $doc_detail = [];
        foreach ($new_doc_array as $new_doc_array_value) {
            $item_url = explode("?", $new_doc_array_value);
            if (!in_array($item_url[0], $old_item_url)) {
                $doc_detail[] = [
                    "item_url" => $item_url[0],
                    "item_type" => 'DOCUMENT',
                ];
            }
            $new_doc_url_explode = explode("https://", $new_doc_array_value);
            $new_doc_url_filter = array_filter($new_doc_url_explode);
            foreach ($new_doc_url_filter as $new_doc_media_value) {
                $new_doc_media_explode = explode("/", $new_doc_media_value);
                $new_doc_media_id[] = $new_doc_media_explode[2];
            }
        }
        $doc_array_merge = array_merge($old_value_array, $doc_detail);

        foreach ($data_collection as $data_collection_value) {
            $old_doc_media_array[] = $data_collection_value['media_id'];
            $pervious_bynder_doc[] = $data_collection_value['bynder_data'];
            if (in_array($data_collection_value['bynder_data'], $doc_array_merge)) {
                unset($doc_array_merge[array_search($data_collection_value['bynder_data'], $doc_array_merge)]);
            }
            $update_doc_data_value1 = [
                'sku' => $product_sku,
                'remove_for_magento' => '2',
                'added_date' => time()
            ];
            $where = ['bynder_data=?' => $data_collection_value['bynder_data']];
            $this->_resource->update($table_Name, $update_doc_data_value1, $where);
        }

        $merge_new_doc_value = json_encode($doc_array_merge, true);

        $updateAttributes['bynder_document'] = $merge_new_doc_value;
        $this->action->updateAttributes([$product_ids], $updateAttributes, $storeId);
        $new_item_array = [];
        foreach ($new_doc_array as $new_doc_array_value) {
            $item_url = explode("?", $new_doc_array_value);
            $new_item_array[] = $item_url[0];
        }
        $diff_doc_new_value = array_diff($new_item_array, $pervious_bynder_doc);
        $logger->info("diff_doc_new_value");
        $logger->info($diff_doc_new_value);
        $logger->info("diff_doc_new_value");

        foreach ($new_doc_media_id as $doc_bynder_media_id) {
            if (in_array($doc_bynder_media_id, $old_doc_media_array)) {
                $logger->info("Update Recored");
                $remove_for_magento = ['remove_for_magento' => '1'];
                $where = ['sku=?' => $product_sku, 'media_id=?' => $doc_bynder_media_id];
                $this->_resource->update($table_Name, $remove_for_magento, $where);
            } else {
                $remove_for_magento = ['remove_for_magento' => '0'];
                $where = ['sku=?' => $product_sku, 'media_id=?' => $doc_bynder_media_id];
                $this->_resource->update($table_Name, $remove_for_magento, $where);
            }
        }
        foreach ($diff_doc_new_value as $new_doc_data_value) {
            $item_url = explode("?", $new_doc_data_value);
            $diff_doc_url_explode = explode("https://", $new_doc_data_value);
            $diff_doc_url_filter = array_filter($diff_doc_url_explode);
            foreach ($diff_doc_url_filter as $doc_media_value) {
                $doc_media_explode = explode("/", $doc_media_value);
                if (!in_array($doc_media_explode[2], $old_doc_media_array)) {
                    $doc_data_value = [
                        'sku' => $product_sku,
                        'bynder_data' => $item_url[0],
                        'bynder_data_type' => '2',
                        'media_id' => $doc_media_explode[2],
                        'remove_for_magento' => '1',
                        'added_on_cron_compactview' => '1',
                        'added_date' => time()
                    ];
                    $model->setData($doc_data_value);
                    $model->save();
                } else {
                    $update_doc_data_value = [
                        'sku' => $product_sku,
                        'bynder_data' => $item_url[0],
                        'remove_for_magento' => '1',
                        'added_date' => time()
                    ];
                    $where = ['media_id=?' => $doc_media_explode[2]];
                    $this->_resource->update($table_Name, $update_doc_data_value, $where);
                }
            }
        }
    }
    /**
     * Get Video
     *
     * @param string $img_json
     * @param string $image_value
     * @param string $product_ids
     * @param string $storeId
     * @param string $table_Name
     * @param string $product_sku
     */
    public function getVideo($img_json, $image_value, $product_ids, $storeId, $table_Name, $product_sku)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/cron.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info("GetVideo Funcation Called");
        $model = $this->_byndersycData->create();
        $updateAttributes = [];
        $check_data_magetno_side = $this->_byndersycDataCollection->create()
            ->addFieldToFilter('sku', $product_sku)
            ->addFieldToFilter('added_on_cron_compactview', '1');
        $data_collection = $check_data_magetno_side->getData();
        $new_video_array = explode(" \n", $img_json);
        $old_value_array = json_decode($image_value, true);
        $old_image_value = [];
        if (!empty($old_value_array)) {
            foreach ($old_value_array as $value) {
                if ($value['item_type'] == 'IMAGE') {
                    $old_image_value[] = $value;
                }
            }
        }
        $video_detail = [];
        $video = [];
        foreach ($new_video_array as $new_video_array_value) {
            $item_url = explode("?", $new_video_array_value);
            $thum_url = explode("@@", $new_video_array_value);
            $video[] = $item_url[0];
            $video_detail[] = [
                "item_url" => $item_url[0],
                "image_role" => null,
                "item_type" => 'VIDEO',
                "thum_url" => $thum_url[1]
            ];
            $new_video_url_explode = explode("https://", $new_video_array_value);
            $new_video_url_filter = array_filter($new_video_url_explode);
            foreach ($new_video_url_filter as $new_video_media_value) {
                $new_video_media_explode = explode("/", $new_video_media_value);
                $new_video_media_id[] = $new_video_media_explode[2];
            }
        }
        $logger->info("video_detail");
        $logger->info($video_detail);
        $logger->info("video_detail");
        $video_array_merge = array_merge($old_image_value, $video_detail);

        $logger->info("video_array_merge");
        $logger->info($video_array_merge);
        $logger->info("video_array_merge");

        foreach ($data_collection as $key => $data_collection_value) {
            $old_video_media_array[] = $data_collection_value['media_id'];
            $pervious_bynder_video[] = $data_collection_value['bynder_data'];
            if (in_array($data_collection_value['bynder_data'], $video)) {
                unset($video[array_search($data_collection_value['bynder_data'], $video)]);
                $update_video_data_value = [
                    'sku' => $product_sku,
                    'remove_for_magento' => '2',
                    'added_date' => time()
                ];
                $where = ['bynder_data=?' => $data_collection_value['bynder_data']];
                $this->_resource->update($table_Name, $update_video_data_value, $where);
            }
        }

        $merge_new_video_value = json_encode($video_array_merge, true);

        $logger->info("merge_new_video_value");
        $logger->info($merge_new_video_value);
        $logger->info("merge_new_video_value");
        $updateAttributes['bynder_multi_img'] = $merge_new_video_value;
        $this->action->updateAttributes([$product_ids], $updateAttributes, $storeId);
        foreach ($video_array_merge as $img) {

            $type[] = $img['item_type'];
        }
        $flag = $this->getFlag($type);
        $new_item_array = [];
        $this->action->updateAttributes([$product_ids], ['bynder_isMain' => $flag], $storeId);
        foreach ($new_video_array as $new_video_array_value) {
            $item_url = explode("?", $new_video_array_value);
            $new_item_array[] = $item_url[0];
        }

        $diff_video_new_value = array_diff($new_item_array, $pervious_bynder_video);

        foreach ($new_video_media_id as $video_bynder_media_id) {
            if (in_array($video_bynder_media_id, $old_video_media_array)) {
                $logger->info("Update Recored");
                $remove_for_magento = ['remove_for_magento' => '1'];
                $where = ['sku=?' => $product_sku, 'media_id=?' => $video_bynder_media_id];
                $this->_resource->update($table_Name, $remove_for_magento, $where);
            } else {
                $remove_for_magento = ['remove_for_magento' => '0'];
                $where = ['sku=?' => $product_sku, 'media_id=?' => $video_bynder_media_id];
                $this->_resource->update($table_Name, $remove_for_magento, $where);
            }
        }
        foreach ($diff_video_new_value as $new_video_data_value) {
            $diff_video_url_explode = explode("https://", $new_video_data_value);
            $diff_video_url_filter = array_filter($diff_video_url_explode);
            foreach ($diff_video_url_filter as $video_media_value) {
                $video_media_explode = explode("/", $video_media_value);

                $logger->info("video media explode");
                $logger->info($video_media_explode);
                $logger->info("video media explode");

                if (!in_array($video_media_explode[2], $old_video_media_array)) {
                    $video_data_value = [
                        'sku' => $product_sku,
                        'bynder_data' => $new_video_data_value,
                        'bynder_data_type' => '3',
                        'media_id' => $video_media_explode[3],
                        'remove_for_magento' => '1',
                        'added_on_cron_compactview' => '1',
                        'added_date' => time()
                    ];
                    $model->setData($video_data_value);
                    $model->save();
                } else {
                    $update_video_data_value = [
                        'sku' => $product_sku,
                        'bynder_data' => $new_video_data_value,
                        'remove_for_magento' => '1',
                        'added_date' => time()
                    ];
                    $where = ['media_id=?' => $video_media_explode[2]];
                    $this->_resource->update($table_Name, $update_video_data_value, $where);
                }
            }
        }
    }
    /**
     * Get Flag
     *
     * @param array $type
     */
    public function getFlag($type)
    {
        if (in_array("IMAGE", $type) && in_array("VIDEO", $type)) {
            $flag = 1;
        } elseif (in_array("IMAGE", $type)) {
            $flag = 2;
        } elseif (in_array("VIDEO", $type)) {
            $flag = 3;
        }
        return $flag;
    }
}
