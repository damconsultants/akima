<?php

namespace DamConsultants\Akima\Cron;

use Exception;
use \Psr\Log\LoggerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Model\Product\Action;
use DamConsultants\Akima\Model\BynderFactory;
use DamConsultants\Akima\Model\ResourceModel\Collection\MetaPropertyCollectionFactory;
use DamConsultants\Akima\Model\ResourceModel\Collection\BynderMediaTableCollectionFactory;

class FeatchNullDataToMagento
{
    /**
     * @var $logger
     */
    protected $logger;
    /**
     * @var $_productRepository
     */
    protected $_productRepository;
    /**
     * @var $collectionFactory
     */
    protected $collectionFactory;
    /**
     * @var $datahelper
     */
    protected $datahelper;
    /**
     * @var $action
     */
    protected $action;
    /**
     * @var $_byndersycData
     */
    protected $_byndersycData;
    /**
     * @var $metaPropertyCollectionFactory
     */
    protected $metaPropertyCollectionFactory;
    /**
     * @var $bynderMediaTable
     */
    protected $bynderMediaTable;
    /**
     * @var $bynderMediaTableCollectionFactory
     */
    protected $bynderMediaTableCollectionFactory;
    /**
     * @var $storeManagerInterface
     */
    protected $storeManagerInterface;
    /**
     * @var $bynder
     */
    protected $bynder;

    /**
     * Featch Null Data To Magento
     * @param LoggerInterface $logger
     * @param ProductRepository $productRepository
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory
     * @param StoreManagerInterface $storeManagerInterface
     * @param \DamConsultants\Akima\Helper\Data $DataHelper
     * @param \DamConsultants\Akima\Model\BynderSycDataFactory $byndersycData
     * @param \DamConsultants\Akima\Model\BynderMediaTableFactory $bynderMediaTable
     * @param BynderMediaTableCollectionFactory $bynderMediaTableCollectionFactory
     * @param Action $action
     * @param MetaPropertyCollectionFactory $metaPropertyCollectionFactory
     * @param BynderFactory $bynder
     */
    public function __construct(
        LoggerInterface $logger,
        ProductRepository $productRepository,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory,
        StoreManagerInterface $storeManagerInterface,
        \DamConsultants\Akima\Helper\Data $DataHelper,
        \DamConsultants\Akima\Model\BynderSycDataFactory $byndersycData,
        \DamConsultants\Akima\Model\BynderMediaTableFactory $bynderMediaTable,
        BynderMediaTableCollectionFactory $bynderMediaTableCollectionFactory,
        Action $action,
        MetaPropertyCollectionFactory $metaPropertyCollectionFactory,
        BynderFactory $bynder
    ) {

        $this->logger = $logger;
        $this->_productRepository = $productRepository;
        $this->collectionFactory = $collectionFactory;
        $this->datahelper = $DataHelper;
        $this->action = $action;
        $this->_byndersycData = $byndersycData;
        $this->metaPropertyCollectionFactory = $metaPropertyCollectionFactory;
        $this->bynderMediaTable = $bynderMediaTable;
        $this->bynderMediaTableCollectionFactory = $bynderMediaTableCollectionFactory;
        $this->storeManagerInterface = $storeManagerInterface;
        $this->bynder = $bynder;
    }
    /**
     * Execute
     *
     * @return boolean
     */
    public function execute()
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/FeatchNullDataToMagento.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info("FeatchNullDataToMagento");
        $enable = $this->datahelper->getFetchCronEnable();
        if (!$enable) {
            return false;
        }
        $logger->info("yes");
        $product_collection = $this->collectionFactory->create();

        $product_sku_limit = (int)$this->datahelper->getProductSkuLimitConfig();
        if (!empty($product_sku_limit)) {
            $product_collection->getSelect()->limit($product_sku_limit);
        } else {
            $product_collection->getSelect()->limit(50);
        }

        $product_collection->addAttributeToSelect('*')
            ->addAttributeToFilter(
                [
                    ['attribute' => 'bynder_multi_img', 'null' => true]
                ]
            )
            ->addAttributeToFilter(
                [
                    ['attribute' => 'bynder_cron_sync', 'null' => true]
                ]
            )
            ->load();

        $property_id = null;
        $collection = $this->metaPropertyCollectionFactory->create()->getData();
        $meta_properties = $this->getMetaPropertiesCollection($collection);

        $collection_value = $meta_properties['collection_data_value'];
        $collection_slug_val = $meta_properties['collection_data_slug_val'];

        $productSku_array = [];
        foreach ($product_collection->getData() as $product) {
            $productSku_array[] = $product['sku'];
        }
        if (count($productSku_array) > 0) {
            foreach ($productSku_array as $sku) {
                if ($sku != "") {
                    $bd_sku = $this->datahelper->replacetoSpecialString($sku);
                    $get_data = $this->datahelper->getImageSyncWithProperties($bd_sku, $property_id, $collection_value);
                    if (!empty($get_data) && $this->getIsJSON($get_data)) {
                        $respon_array = json_decode($get_data, true);
                        if ($respon_array['status'] == 1) {
                            $convert_array = json_decode($respon_array['data'], true);
                            if ($convert_array['status'] == 1) {
                                $current_sku = $sku;
                                try {
                                    $this->getDataItem("image", $convert_array, $collection_slug_val, $current_sku);
                                } catch (Exception $e) {
                                    $insert_data = [
                                        "sku" => $sku,
                                        "message" => $e->getMessage(),
                                        "data_type" => "",
                                        'media_id' => "",
                                        'remove_for_magento' => '',
                                        'added_on_cron_compactview' => '',
                                        "lable" => "0"
                                    ];
                                    $this->getInsertDataTable($insert_data);

                                    $this->updateBynderCronSync($sku);
                                }

                            } else {
                                $insert_data = [
                                    "sku" => $sku,
                                    "message" => $convert_array['data'],
                                    "data_type" => "",
                                    'media_id' => "",
                                    'remove_for_magento' => '',
                                    'added_on_cron_compactview' => '',
                                    "lable" => "0"
                                ];
                                $this->getInsertDataTable($insert_data);

                                $this->updateBynderCronSync($sku);
                            }
                        } else {
                            $insert_data = [
                                "sku" => $sku,
                                "message" => 'Please Select The Metaproperty First.....',
                                "data_type" => "",
                                'media_id' => "",
                                'remove_for_magento' => '',
                                'added_on_cron_compactview' => '',
                                "lable" => "0"
                            ];
                            $this->getInsertDataTable($insert_data);
                        }
                    } else {
                        $insert_data = [
                            "sku" => $sku,
                            "message" => "Something problem in DAM side please contact to developer.",
                            "data_type" => "",
                            'media_id' => "",
                            'remove_for_magento' => '',
                            'added_on_cron_compactview' => '',
                            "lable" => "0"
                        ];
                        $this->getInsertDataTable($insert_data);
                    }
                }
            }
        }
        return true;
    }

    /**
     * Get Meta Properties Collection
     *
     * @param array $collection
     * @return array $response_array
     */
    public function getMetaPropertiesCollection($collection)
    {
        $collection_data_value = [];
        $collection_data_slug_val = [];
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
        }
        $response_array = [
            "collection_data_value" => $collection_data_value,
            "collection_data_slug_val" => $collection_data_slug_val
        ];
        return $response_array;
    }

    /**
     * Is int
     *
     * @return $this
     */
    public function getMyStoreId()
    {
        $storeId = $this->storeManagerInterface->getStore()->getId();
        return $storeId;
    }

    /**
     * Is Json
     *
     * @param string $string
     * @return $this
     */
    public function getIsJSON($string)
    {
        return ((json_decode($string)) === null) ? false : true;
    }
    /**
     * Is Json
     *
     * @param array $insert_data
     * @return $this
     */
    public function getInsertDataTable($insert_data)
    {
        $model = $this->_byndersycData->create();
        $data_image_data = [
            'sku' => $insert_data['sku'],
            'bynder_data' =>$insert_data['message'],
            'bynder_data_type' => $insert_data['data_type'],
            'media_id' => $insert_data['media_id'],
            'remove_for_magento' => $insert_data['remove_for_magento'],
            'added_on_cron_compactview' => $insert_data['added_on_cron_compactview'],
            'lable' => $insert_data['lable']
        ];

        $model->setData($data_image_data);
        $model->save();
    }
    /**
     * Is Json
     *
     * @param mixed $sku
     * @param mixed $m_id
     * @param mixed $product_ids
     * @param string $storeId
     * @return $this
     */
    public function getInsertMedaiDataTable($sku, $m_id, $product_ids, $storeId)
    {
        $model = $this->bynderMediaTable->create();
        $modelcollection = $this->bynderMediaTableCollectionFactory->create();
        $modelcollection->addFieldToFilter('sku', ['eq' => [$sku]])->load();
        $table_m_id = [];
        if (!empty($modelcollection)) {
            foreach ($modelcollection as $mdata) {
                $table_m_id[] = $mdata['media_id'];
            }
        }
        $media_diff = array_diff($m_id, $table_m_id);
        foreach ($media_diff as $new_data) {
            $data_image_data = [
                'sku' => $sku,
                'media_id' => trim($new_data),
                'status' => "1",
            ];
            $model->setData($data_image_data);
            $model->save();
        }
        $updated_values = [
            'bynder_delete_cron' => 1
        ];
        $this->action->updateAttributes(
            [$product_ids],
            $updated_values,
            $storeId
        );
    }
    /**
     * Is Json
     *
     * @param string $sku
     * @param string $media_id
     * @return $this
     */
    public function getDeleteMedaiDataTable($sku, $media_id)
    {
        $model = $this->bynderMediaTableCollectionFactory->create();
        $model->addFieldToFilter('sku', ['eq' => [$sku]])->load();
        foreach ($model as $mdata) {
            if ($mdata['media_id'] != $media_id) {
                $this->bynderMediaTable->create()->load($mdata['id'])->delete();

            }
        }
    }
    /**
     * Get Data Item
     *
     * @param array $select_attribute
     * @param array $convert_array
     * @param array $collection_data_slug_val
     * @param string $current_sku
     */
    public function getDataItem($select_attribute, $convert_array, $collection_data_slug_val, $current_sku)
    {
        $data_arr = [];
        $data_val_arr = [];
        if ($convert_array['status'] == 1) {
            $new_magento_role = true;
            foreach ($convert_array['data'] as $data_value) {
                $is_order = [];
                if ($select_attribute == $data_value['type']) {
                    $bynder_media_id = $data_value['id'];
                    $image_data      = $data_value['thumbnails'];
                    $sku_slug_name = "property_" .
                    $collection_data_slug_val['sku']['bynder_property_slug'];
                    $data_sku[0] = $current_sku;
                    $images_urls_list        = [];
                    $new_bynder_alt_text     = [];
                    $new_bynder_mediaid_text = [];
                    if ($new_magento_role) {
                        $new_magento_role_list = ['Base', 'Small', 'Thumbnail', 'Swatch'];
                        $new_magento_role = false;
                    } else {
                        $new_magento_role_list = ["###"];
                    }
                    if (isset($image_data["Product"])) {
                        $images_urls_list[] = $image_data["Product"] . "\n";
                    } elseif (isset($image_data["webimage"])) {
                        $images_urls_list[] = $image_data["webimage"] . "\n";
                    } else {
                        $images_urls_list[] = "no image\n";
                    }
                    $alt_text_vl = '';
                    if (isset($image_data["img_alt_text"])) {
                        $alt_text_vl = $image_data["img_alt_text"];

                        if (is_array($alt_text_vl)) {
                            $alt_text_vl = implode(" ", $alt_text_vl);
                        }
                    }
                    if (!empty($alt_text_vl)) {
                        $new_bynder_alt_text[] = $alt_text_vl . "\n";
                    } else {
                        $new_bynder_alt_text[] = "###\n";
                    }
                    $new_bynder_mediaid_text[] = $bynder_media_id . "\n";
                    if (isset($collection_data_slug_val['image_order']['bynder_property_slug'])) {
                        $magento_order_slug = "property_" .
                            $collection_data_slug_val['image_order']['bynder_property_slug'];
                        if (isset($data_value[$magento_order_slug])) {
                            foreach ($data_value[$magento_order_slug] as $property_Magento_Media_Order) {
                                $is_order[] = $property_Magento_Media_Order . "\n";
                            }
                        }
                    }
                    if ($data_value['type'] == "image") {
                        array_push($data_arr, $data_sku[0]);
                        $data_p = [
                            "sku"                 => $data_sku[0],
                            "url"                 => $images_urls_list,
                            "magento_image_role"  => $new_magento_role_list,
                            "image_alt_text"      => $new_bynder_alt_text,
                            "bynder_media_id_new" => $new_bynder_mediaid_text,
                            "type"                => "image",
                            "is_order"            => $is_order
                        ];
                        array_push($data_val_arr, $data_p);
                    } else {
                        if ($select_attribute == 'video') {
                            $video_link = $image_data["image_link"] .
                                '@@' .
                                $image_data["webimage"];
                            array_push($data_arr, $data_sku[0]);
                            $data_p = [
                                "sku"      => $data_sku[0],
                                "url"      => $video_link,
                                "type"     => "video",
                                "is_order" => $is_order
                            ];
                            array_push($data_val_arr, $data_p);
                        } else {
                            $doc_name = $data_value["name"];
                            $doc_name_with_space = preg_replace(
                                "/[^a-zA-Z]+/",
                                "-",
                                $doc_name
                            );
                            $doc_link = $image_data["image_link"] .
                                '@@' .
                                $doc_name_with_space;
                            array_push($data_arr, $data_sku[0]);
                            $data_p = [
                                "sku"      => $data_sku[0],
                                "url"      => $doc_link,
                                "type"     => "document",
                                "is_order" => $is_order
                            ];
                            array_push($data_val_arr, $data_p);
                        }
                    }
                }
            }
        }
        if (count($data_arr) > 0) {
            $this->getProcessItem($data_arr, $data_val_arr);
        }
    }
    /**
     * Get Process Item
     *
     * @param array $data_arr
     * @param array $data_val_arr
     */
    public function getProcessItem($data_arr, $data_val_arr)
    {
        /* $writer = new \Laminas\Log\Writer\Stream(BP . '/var/log/FeatchNullDataToMagento.log');
        $logger = new \Laminas\Log\Logger();
        $logger->addWriter($writer);
        $logger->info("getProcessItem funcation called"); */

        $image_value_details_role = [];
        $temp_arr = [];
        $byn_is_order = [];
        $types = [];
        foreach ($data_arr as $key => $skus) {
            $temp_arr[$skus][] = implode("", $data_val_arr[$key]["url"]);
            $image_value_details_role[$skus][] = $data_val_arr[$key]["magento_image_role"];
            $image_alt_text[$skus][] = implode("", $data_val_arr[$key]["image_alt_text"]);
            $bynder_media_id_new[$skus][] = implode("", $data_val_arr[$key]["bynder_media_id_new"]);
            $types[] = $data_val_arr[$key]['type'];
            $byn_is_order[$skus][] = implode("", $data_val_arr[$key]["is_order"]);
        }
        $types = array_unique($types);
        foreach ($temp_arr as $product_sku_key => $image_value) {
            $img_json = implode("", $image_value);
            $mg_role              = $image_value_details_role[$product_sku_key];
            $image_alt_text_value = implode("", $image_alt_text[$product_sku_key]);
            $bynder_media_id_value = implode("", $bynder_media_id_new[$product_sku_key]);
            $byd_media_is_order = implode("", $byn_is_order[$product_sku_key]);
            $this->getUpdateImage(
                $img_json,
                $product_sku_key,
                $mg_role,
                $image_alt_text_value,
                $bynder_media_id_value,
                $types,
                $byd_media_is_order
            );
        }
    }

    /**
     * Upate Item
     *
     * @return $this
     * @param string $img_json
     * @param string $product_sku_key
     * @param string $mg_img_role_option
     * @param string $img_alt_text
     * @param array $types
     * @param string $bynder_media_ids
     */
    public function getUpdateImage($img_json, $product_sku_key, $mg_img_role_option, $img_alt_text, $bynder_media_ids, $types, $byd_media_is_order)
    {
        $model = $this->_byndersycData->create();
        $image_detail = [];
        $video_detail = [];
        try {
            $storeId = $this->storeManagerInterface->getStore()->getId();
            $_product = $this->_productRepository->get($product_sku_key);
            $product_ids = $_product->getId();
            $image_value = $_product->getBynderMultiImg();
            $doc_value = $_product->getBynderDocument();
            $bynder_media_id = explode("\n", $bynder_media_ids);
			$isOrder = explode("\n", $byd_media_is_order);
            if (in_array("image", $types) || in_array("video", $types)) { 
                if (empty($image_value)) {
                    $new_image_array = explode("\n", $img_json);
                    $new_alttext_array = explode("\n", $img_alt_text);
                    $new_magento_role_option_array = $mg_img_role_option;
                    foreach ($new_image_array as $vv => $image_value) {
                        if (trim($image_value) != "" && $image_value != "no image") {
                            $img_altText_val = "";
                            if (isset($new_alttext_array[$vv])) {
                                if ($new_alttext_array[$vv] != "###" && strlen(trim($new_alttext_array[$vv])) > 0) {
                                    $img_altText_val = $new_alttext_array[$vv];
                                }
                            }
                            $curt_img_role = [];
                            if ($new_magento_role_option_array[$vv] != "###") {
                                $curt_img_role = $new_magento_role_option_array[$vv];
                            }
                            $find_video = strpos($image_value, "@@");
							$find_doc = strpos($image_value, "??");
                            if (!$find_video && !$find_doc) {
								$is_order = isset($isOrder[$vv]) ? $isOrder[$vv] : "";
                                $image_detail[] = [
                                    "item_url" => $image_value,
                                    "alt_text" => $img_altText_val,
                                    "image_role" => $curt_img_role,
                                    "item_type" => 'IMAGE',
                                    "thum_url" => $image_value,
                                    "bynder_md_id" => $bynder_media_id[$vv],
                                    "is_import" => 0,
									"is_order" => $is_order
                                ];
                                $data_image_data = [
                                    'sku' => $product_sku_key,
                                    'message' => $image_value,
                                    'data_type' => '1',
                                    'media_id' => $bynder_media_id[$vv],
                                    'remove_for_magento' => '1',
                                    'added_on_cron_compactview' => '1',
                                    'lable' => 1
                                ];
                                $this->getInsertDataTable($data_image_data);
                            } elseif($find_video) {
								$is_order = isset($isOrder[$vv]) ? $isOrder[$vv] : "";
								$item_url = explode("@@", $image_value);
								$thum_url = explode("@@", $image_value);
                                $media_video_explode = explode("/", $item_url[0]);
            
                                $video_detail[] = [
                                    "item_url" => $item_url[0],
                                    "image_role" => null,
                                    "item_type" => 'VIDEO',
                                    "thum_url" => $thum_url[1],
                                    "bynder_md_id" => $bynder_media_id[$vv],
									"is_order" => $is_order
                                ];
                                $data_video_data = [
                                    'sku' => $product_sku_key,
                                    'message' => $item_url[0],
                                    'data_type' => '3',
                                    'media_id' => $media_video_explode[$vv],
                                    'remove_for_magento' => '1',
                                    'added_on_cron_compactview' => '1',
                                    'lable' => 1
                                ];
                                $this->getInsertDataTable($data_video_data);
            
                            }
                            $total_new_value = count($image_detail);
                            if ($total_new_value > 1) {
                                foreach ($image_detail as $nn => $n_img) {
                                    if ($n_img['item_type'] == "IMAGE" && $nn != ($total_new_value - 1)) {
                                        if ($new_magento_role_option_array[$vv] != "###") {
                                            $new_mg_role_array = (array)$new_magento_role_option_array[$vv];
                                            if (count($n_img["image_role"]) > 0 && count($new_mg_role_array) > 0) {
                                                $result_val=array_diff($n_img["image_role"], $new_mg_role_array);
                                                $image_detail[$nn]["image_role"] = $result_val;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    $replacementRoles = ["Base", "Small", "Swatch", "Thumbnail"];
					$flags = true;
					foreach ($image_detail as &$item) {
						if (in_array('Base', $item['image_role'])) {
							$flags = false;
						}
					}
                    foreach ($image_detail as &$item) {
                        if ($flags && isset($item['image_role']) && is_array($item['image_role'])) {
                            $containsPlaceholder = in_array("###\n", $item['image_role']);
                            $hasAllReplacementRoles = empty(array_diff($replacementRoles, $item['image_role']));
                            if ($hasAllReplacementRoles) { break; }
                            if ($containsPlaceholder && !$hasAllReplacementRoles) {
                                $item['image_role'] = $replacementRoles;
                            }
                        }
                    }
					foreach ($image_detail as &$items) {
						if (isset($items['image_role']) && is_array($items['image_role'])) {
							// Clean out "###" from image_role
							$items['image_role'] = array_values(array_filter(
								$items['image_role'],
								fn($role) => trim($role) !== '###'
							));
						}
					}
					unset($items);
                    $marge = array_merge($image_detail, $video_detail);
                    $m_id = [];
                    foreach ($marge as $img) {
                        $type[] = $img['item_type'];
                        $m_id[] = $img['bynder_md_id'];
                        $this->getDeleteMedaiDataTable($product_sku_key, $img['bynder_md_id']);
                    }
                    $this->getInsertMedaiDataTable($product_sku_key, $m_id, $product_ids, $storeId);
                    
                    $flag = 0;
                    if (in_array("IMAGE", $type) && in_array("VIDEO", $type)) {
                        $flag = 1;
                    } elseif (in_array("IMAGE", $type)) {
                        $flag = 2;
                    } elseif (in_array("VIDEO", $type)) {
                        $flag = 3;
                    }
                    $new_value_array = json_encode($marge, true);
                    $updated_values = [
                        'bynder_multi_img' => $new_value_array,
                        'bynder_isMain' => $flag,
                        'bynder_cron_sync' => 1,
                        'use_bynder_cdn' => 1
                    ];
                    $this->action->updateAttributes(
                        [$product_ids],
                        $updated_values,
                        $storeId
                    );
                }
            }
            if (in_array("document", $types)) {
                if(empty($doc_value)) {
                    $new_doc_array = explode("\n", $img_json);
                    $doc_detail = [];
                    foreach ($new_doc_array as $vv => $doc_values) {
						$find_doc = strpos($doc_values, "??");
						if($find_doc) {
							$item_url = explode("??", $doc_values);
							$doc_name = explode("??", $doc_values);
							$media_doc_explode = explode("/", $item_url[0]);
							if(isset($doc_name[1]) && isset($bynder_media_id[$vv])){
								$is_order = isset($isOrder[$vv]) ? $isOrder[$vv] : "";
								$doc_detail[] = [
									"item_url" => $item_url[0],
									"item_type" => 'DOCUMENT',
									"doc_name" => $doc_name[1],
									"bynder_md_id" => $bynder_media_id[$vv],
									"is_order" => $is_order
								];
							
								$data_doc_value = [
									'sku' => $product_sku_key,
									'message' => $item_url[0],
									'data_type' => '2',
									'media_id' => $bynder_media_id[$vv],
									'remove_for_magento' => '1',
									'added_on_cron_compactview' => '1',
									'lable' => 1
								];
								$this->getInsertDataTable($data_doc_value);
							}
						}   
                    }
                    $new_value_array = json_encode($doc_detail, true);
                    $this->action->updateAttributes(
                        [$product_ids],
                        ['bynder_document' => $new_value_array,'bynder_cron_sync' => 1],
                        $storeId
                    );
                }
            }
        } catch (Exception $e) {
            $insert_data = [
                "sku" => $product_sku_key,
                "message" => $e->getMessage(),
                "data_type" => "",
                'media_id' => "",
                'remove_for_magento' => '',
                'added_on_cron_compactview' => '',
                "lable" => "0"
            ];
            $this->getInsertDataTable($insert_data);
        }
    }

    /**
     * Update Bynder cron sync status
     *
     * @param string $sku
     */
    public function updateBynderCronSync($sku)
    {
        $updated_values = [
            'bynder_cron_sync' => 2
        ];

        $storeId = $this->getMyStoreId();
        $_product = $this->_productRepository->get($sku);
        $product_ids = $_product->getId();

        $this->action->updateAttributes(
            [$product_ids],
            $updated_values,
            $storeId
        );
    }
}
