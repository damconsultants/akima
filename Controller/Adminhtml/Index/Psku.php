<?php

namespace DamConsultants\Akima\Controller\Adminhtml\Index;

use DamConsultants\Akima\Model\ResourceModel\Collection\MetaPropertyCollectionFactory;
use DamConsultants\Akima\Model\ResourceModel\Collection\BynderMediaTableCollectionFactory;
use Exception;

class Psku extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Magento\Catalog\Model\Product\Action
     */
    protected $productAction;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManagerInterface;

    /**
     * @var MetaPropertyCollectionFactory
     */
    protected $metaPropertyCollectionFactory;

    /**
     * @var \DamConsultants\Akima\Model\BynderMediaTableFactory
     */
    protected $bynderMediaTable;

    /**
     * @var BynderMediaTableCollectionFactory
     */
    protected $bynderMediaTableCollectionFactory;

    /**
     * @var \DamConsultants\Akima\Helper\Data
     */
    protected $datahelper;

    /**
     * @var \DamConsultants\Akima\Model\BynderConfigSyncDataFactory
     */
    protected $_byndersycData;

    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $_productRepository;

    /**
     * @var \Magento\Catalog\Model\Product
     */
    protected $product;

    /**
     * Psku constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Catalog\Model\Product\Action $action
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     * @param \DamConsultants\Akima\Model\BynderConfigSyncDataFactory $byndersycData
     * @param \DamConsultants\Akima\Model\BynderMediaTableFactory $bynderMediaTable
     * @param BynderMediaTableCollectionFactory $bynderMediaTableCollectionFactory
     * @param \Magento\Catalog\Model\Product $product
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     * @param MetaPropertyCollectionFactory $metaPropertyCollectionFactory
     * @param \DamConsultants\Akima\Helper\Data $DataHelper
     * @param \Magento\Framework\Controller\Result\JsonFactory $jsonFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Catalog\Model\Product\Action $action,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \DamConsultants\Akima\Model\BynderConfigSyncDataFactory $byndersycData,
        \DamConsultants\Akima\Model\BynderMediaTableFactory $bynderMediaTable,
        BynderMediaTableCollectionFactory $bynderMediaTableCollectionFactory,
        \Magento\Catalog\Model\Product $product,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        MetaPropertyCollectionFactory $metaPropertyCollectionFactory,
        \DamConsultants\Akima\Helper\Data $DataHelper,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $jsonFactory;
        $this->productAction = $action;
        $this->storeManagerInterface = $storeManagerInterface;
        $this->metaPropertyCollectionFactory = $metaPropertyCollectionFactory;
        $this->bynderMediaTable = $bynderMediaTable;
        $this->bynderMediaTableCollectionFactory = $bynderMediaTableCollectionFactory;
        $this->datahelper = $DataHelper;
        $this->_byndersycData = $byndersycData;
        $this->_productRepository = $productRepository;
        $this->product = $product;
    }

    /**
     * Execute action — handles AJAX requests to sync product SKU data from Bynder.
     *
     * @return \Magento\Framework\Controller\Result\Json|string
     */
    public function execute()
    {
        if (!$this->getRequest()->isAjax()) {
            $this->_forward('noroute');
            return '';
        }

        $property_id = null;
        $product_sku = $this->getRequest()->getParam('product_sku');
        $select_attribute = $this->getRequest()->getParam('select_attribute');
        $result = $this->resultJsonFactory->create();

        $collection = $this->metaPropertyCollectionFactory->create()->getData();
        $meta_properties = $this->getMetaPropertiesCollection($collection);

        $collection_value = $meta_properties['collection_data_value'];
        $collection_slug_val = $meta_properties['collection_data_slug_val'];

        if (strlen($product_sku) > 0) {
            $productSku = explode(",", trim($product_sku));
            if (count($productSku) > 0) {
                foreach ($productSku as $sku) {
                    if ($sku != "") {
                        $bd_sku = $this->datahelper->replacetoSpecialString($sku);
                        $get_data = $this->datahelper->getImageSyncWithProperties(
                            $bd_sku,
                            $property_id,
                            $collection_value
                        );
                        $getIsJson = $this->getIsJSON($get_data);
                        if (!empty($get_data) && $getIsJson) {
                            $respon_array = json_decode($get_data, true);
                            if ($respon_array['status'] == 1) {
                                $convert_array = json_decode($respon_array['data'], true);
                                if ($convert_array['status'] == 1) {
                                    $current_sku = $sku;
                                    try {
                                        $this->getDataItem(
                                            $select_attribute,
                                            $convert_array,
                                            $collection_slug_val,
                                            $current_sku
                                        );
                                    } catch (Exception $e) {
                                        $insert_data = [
                                            "sku"       => $sku,
                                            "message"   => $e->getMessage(),
                                            "data_type" => "",
                                            "lable"     => "0"
                                        ];
                                        $this->getInsertDataTable($insert_data);
                                    }
                                } else {
                                    $insert_data = [
                                        "sku"       => $sku,
                                        "message"   => $convert_array['data'],
                                        "data_type" => "",
                                        "lable"     => "0"
                                    ];
                                    $this->getInsertDataTable($insert_data);
                                }
                            } else {
                                $insert_data = [
                                    "sku"       => $sku,
                                    "message"   => 'Please Select The Metaproperty First.....',
                                    "data_type" => "",
                                    "lable"     => "0"
                                ];
                                $this->getInsertDataTable($insert_data);
                                $result_data = $result->setData(
                                    ['status' => 0, 'message' => 'Please check Bynder Synchronization. Action Log.....']
                                );
                                return $result_data;
                            }
                        } else {
                            $result_data = $result->setData(
                                [
                                    'status'  => 0,
                                    'message' => 'Something went wrong from API side, Please contact to support team!'
                                ]
                            );
                            return $result_data;
                        }
                    }
                }
            }
            $result_data = $result->setData([
                'status'  => 1,
                'message' => 'Data Sync Successfully.Please check Bynder Synchronization Log.!'
            ]);
            return $result_data;
        } else {
            $result_data = $result->setData(['status' => 0, 'message' => 'Please enter atleast one SKU.']);
            return $result_data;
        }
    }

    /**
     * Build structured arrays from the raw meta-property collection.
     *
     * @param array $collection
     * @return array{collection_data_value: array, collection_data_slug_val: array}
     */
    public function getMetaPropertiesCollection($collection)
    {
        $collection_data_value = [];
        $collection_data_slug_val = [];
        if (count($collection) >= 1) {
            foreach ($collection as $collection_value) {
                $collection_data_value[] = [
                    'id'                   => $collection_value['id'],
                    'property_name'        => $collection_value['property_name'],
                    'property_id'          => $collection_value['property_id'],
                    'magento_attribute'    => $collection_value['magento_attribute'],
                    'attribute_id'         => $collection_value['attribute_id'],
                    'bynder_property_slug' => $collection_value['bynder_property_slug'],
                    'system_slug'          => $collection_value['system_slug'],
                    'system_name'          => $collection_value['system_name']
                ];
                $collection_data_slug_val[$collection_value['system_slug']] = [
                    'bynder_property_slug' => $collection_value['bynder_property_slug'],
                ];
            }
        }
        $response_array = [
            "collection_data_value"    => $collection_data_value,
            "collection_data_slug_val" => $collection_data_slug_val
        ];
        return $response_array;
    }

    /**
     * Check whether a string is valid JSON.
     *
     * @param string $string
     * @return bool
     */
    public function getIsJSON($string)
    {
        return (json_decode($string) !== null);
    }

    /**
     * Insert a sync-status record into the Bynder config sync data table.
     *
     * @param array $insert_data Keys: sku, message, data_type, lable
     * @return void
     */
    public function getInsertDataTable($insert_data)
    {
        $model = $this->_byndersycData->create();
        $data_image_data = [
            'sku'              => $insert_data['sku'],
            'bynder_sync_data' => $insert_data['message'],
            'bynder_data_type' => $insert_data['data_type'],
            'lable'            => $insert_data['lable']
        ];
        $model->setData($data_image_data);
        $model->save();
    }

    /**
     * Insert new Bynder media IDs into the media table and flag the product for CDN deletion.
     *
     * @param string $sku
     * @param array  $m_id       Array of Bynder media IDs to insert
     * @param int    $product_ids
     * @param int    $storeId
     * @return void
     */
    public function getInsertMedaiDataTable($sku, $m_id, $product_ids, $storeId)
    {
        $model = $this->bynderMediaTable->create();
        $modelcollection = $this->bynderMediaTableCollectionFactory->create();
        $modelcollection->addFieldToFilter('sku', ['eq' => [$sku]])->load();

        $table_m_id = [];
        foreach ($modelcollection as $mdata) {
            $table_m_id[] = $mdata['media_id'];
        }

        $media_diff = array_diff($m_id, $table_m_id);
        foreach ($media_diff as $new_data) {
            $new_m_id = trim($new_data);
            $data_image_data = [
                'sku'      => $sku,
                'media_id' => $new_m_id,
                'status'   => "1",
            ];
            $model->setData($data_image_data);
            $model->save();
        }

        $updated_values = ['bynder_delete_cron' => 1];
        $this->productAction->updateAttributes(
            [$product_ids],
            $updated_values,
            $storeId
        );
    }

    /**
     * Remove stale Bynder media entries from the media table for a given SKU.
     *
     * @param string $sku
     * @param string $media_id  The current/valid media ID to keep
     * @return void
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
     * Parse the Bynder API response and build structured data arrays per media type.
     *
     * @param string $select_attribute   Media type filter: 'image', 'video', or other (document)
     * @param array  $convert_array      Decoded Bynder API response data
     * @param array  $collection_data_slug_val  Slug-to-property mapping
     * @param string $current_sku        The product SKU being processed
     * @return \Magento\Framework\Controller\Result\Json|void
     */
    public function getDataItem($select_attribute, $convert_array, $collection_data_slug_val, $current_sku)
    {
        $data_arr = [];
        $data_val_arr = [];
        $result = $this->resultJsonFactory->create();

        if ($convert_array['status'] == 1) {
            foreach ($convert_array['data'] as $data_value) {
                $is_order = [];
                if ($select_attribute == $data_value['type']) {
                    $bynder_media_id    = $data_value['id'];
                    $image_data         = $data_value['thumbnails'];
                    $bynder_image_role  = $image_data['magento_role_options'];
                    $sku_slug_name      = "property_" . $collection_data_slug_val['sku']['bynder_property_slug'];
                    $data_sku[0]        = $current_sku;

                    $images_urls_list       = [];
                    $new_magento_role_list  = [];
                    $new_bynder_alt_text    = [];
                    $new_bynder_mediaid_text = [];

                    if (count($bynder_image_role) > 0) {
                        foreach ($bynder_image_role as $m_bynder_role) {
                            $original_m_bynder_role = $m_bynder_role;
                            $original_m_bynder_role_slug = ($m_bynder_role == "Base") ? "Base image" : $m_bynder_role;

                            if (isset($data_value["thumbnails"][$original_m_bynder_role_slug])) {
                                $images_urls_list[]    = $data_value["thumbnails"][$original_m_bynder_role_slug] . "\n";
                                $new_magento_role_list[] = $original_m_bynder_role . "\n";

                                $alt_text_vl = $data_value["thumbnails"]["img_alt_text"];
                                if (is_array($alt_text_vl)) {
                                    $alt_text_vl = implode(" ", $alt_text_vl);
                                }
                                $new_bynder_alt_text[] = (strlen($alt_text_vl) > 0) ? $alt_text_vl . "\n" : "###\n";
                            } else {
                                // Fallback: use "Product" derivative, then "webimage"
                                if (isset($data_value["thumbnails"]["Product"])) {
                                    $images_urls_list[] = $data_value["thumbnails"]["Product"] . "\n";
                                } else {
                                    $images_urls_list[] = $data_value["thumbnails"]["webimage"] . "\n";
                                }
                                $new_magento_role_list[] = $original_m_bynder_role . "\n";

                                $alt_text_vl = $data_value["thumbnails"]["img_alt_text"];
                                if (is_array($alt_text_vl)) {
                                    $alt_text_vl = implode(" ", $alt_text_vl);
                                }
                                $new_bynder_alt_text[] = (strlen($alt_text_vl) > 0) ? $alt_text_vl . "\n" : "###\n";
                            }

                            $new_bynder_mediaid_text[] = $bynder_media_id . "\n";
                            if (isset($collection_data_slug_val['image_order']['bynder_property_slug'])) {
                                $magento_order_slug = "property_" . $collection_data_slug_val['image_order']['bynder_property_slug'];
                                if (isset($data_value[$magento_order_slug])) {
                                    foreach ($data_value[$magento_order_slug] as $property_Magento_Media_Order) {
                                        $is_order[] = $property_Magento_Media_Order . "\n";
                                    }
                                }
                            }
                        }
                    } else {
                        $new_magento_role_list[] = "###\n";
                        $alt_text_vl = $data_value["thumbnails"]["img_alt_text"];
                        if (!empty($alt_text_vl)) {
                            $new_bynder_alt_text[] = $alt_text_vl . "\n";
                        } else {
                            $new_bynder_alt_text[] = "###\n";
                        }
                        $new_bynder_mediaid_text[] = $bynder_media_id . "\n";
                        if (isset($collection_data_slug_val['image_order']['bynder_property_slug'])) {
                            $magento_order_slug = "property_" . $collection_data_slug_val['image_order']['bynder_property_slug'];
                            if (isset($data_value[$magento_order_slug])) {
                                foreach ($data_value[$magento_order_slug] as $property_Magento_Media_Order) {
                                    $is_order[] = $property_Magento_Media_Order . "\n";
                                }
                            }
                        }
                    }

                    if (count($images_urls_list) == 0) {
                        if (isset($image_data["Product"])) {
                            $images_urls_list[] = $image_data["Product"] . "\n";
                        } else {
                            $images_urls_list[] = "no image\n";
                        }
                    }

                    if ($data_value['type'] == "image") {
                        array_push($data_arr, $data_sku[0]);
                        $data_p = [
                            "sku"                 => $data_sku[0],
                            "url"                 => $images_urls_list,
                            'magento_image_role'  => $new_magento_role_list,
                            'image_alt_text'      => $new_bynder_alt_text,
                            'bynder_media_id_new' => $new_bynder_mediaid_text,
                            'is_order'            => $is_order
                        ];
                        array_push($data_val_arr, $data_p);
                    } else {
                        if ($select_attribute == 'video') {
                            $video_link = $image_data["image_link"] . '@@' . $image_data["webimage"];
                            array_push($data_arr, $data_sku[0]);
                            $data_p = ["sku" => $data_sku[0], "url" => $video_link, 'is_order' => $is_order];
                            array_push($data_val_arr, $data_p);
                        } else {
                            $doc_name = $data_value["name"];
                            $doc_name_with_space = preg_replace("/[^a-zA-Z]+/", "-", $doc_name);
                            $doc_link = $image_data["image_link"] . '@@' . $doc_name_with_space;
                            array_push($data_arr, $data_sku[0]);
                            $data_p = ["sku" => $data_sku[0], "url" => $doc_link, 'is_order' => $is_order];
                            array_push($data_val_arr, $data_p);
                        }
                    }
                }
            }
        }

        if (count($data_arr) > 0) {
            $this->getProcessItem($data_arr, $data_val_arr);
        } else {
            $result_data = $result->setData(['status' => 0, 'message' => 'No Data Found...']);
            return $result_data;
        }
    }

    /**
     * Group data by SKU and forward each SKU's aggregated data to getUpdateImage().
     *
     * @param array $data_arr      Flat list of SKUs (with duplicates per image)
     * @param array $data_val_arr  Corresponding list of image/media data arrays
     * @return void
     */
    public function getProcessItem($data_arr, $data_val_arr)
    {
        $image_value_details_role = [];
        $temp_arr      = [];
        $byn_is_order  = [];
        $image_alt_text = [];
        $byn_md_id_new  = [];

        foreach ($data_arr as $key => $skus) {
            $temp_arr[$skus][]              = implode("", $data_val_arr[$key]["url"]);
            $image_value_details_role[$skus][] = implode("", $data_val_arr[$key]["magento_image_role"]);
            $image_alt_text[$skus][]        = implode("", $data_val_arr[$key]["image_alt_text"]);
            $byn_md_id_new[$skus][]         = implode("", $data_val_arr[$key]["bynder_media_id_new"]);
            $byn_is_order[$skus][]          = implode("", $data_val_arr[$key]["is_order"]);
        }

        foreach ($temp_arr as $product_sku_key => $image_value) {
            $img_json             = implode("", $image_value);
            $mg_role              = implode("", $image_value_details_role[$product_sku_key]);
            $image_alt_text_value = implode("", $image_alt_text[$product_sku_key]);
            $byd_media_id_value   = implode("", $byn_md_id_new[$product_sku_key]);
            $byd_media_is_order   = implode("", $byn_is_order[$product_sku_key]);

            $this->getUpdateImage(
                $img_json,
                $product_sku_key,
                $mg_role,
                $image_alt_text_value,
                $byd_media_id_value,
                $byd_media_is_order
            );
        }
    }

    /**
     * Apply the synced Bynder media data to the Magento product attributes.
     *
     * @param string $img_json           Newline-delimited image URLs
     * @param string $product_sku_key    Product SKU
     * @param string $mg_img_role_option Newline-delimited Magento image roles
     * @param string $img_alt_text       Newline-delimited alt text values
     * @param string $bynder_media_ids   Newline-delimited Bynder media IDs
     * @param string $byd_media_is_order Newline-delimited sort-order values
     * @return \Magento\Framework\Controller\Result\Json|void
     */
    public function getUpdateImage(
        $img_json,
        $product_sku_key,
        $mg_img_role_option,
        $img_alt_text,
        $bynder_media_ids,
        $byd_media_is_order
    ) {
        $result          = $this->resultJsonFactory->create();
        $select_attribute = $this->getRequest()->getParam('select_attribute');
        $image_detail    = [];
        $diff_image_detail = [];

        try {
            $storeId     = $this->storeManagerInterface->getStore()->getId();
            $_product    = $this->_productRepository->get($product_sku_key);
            $product_ids = $_product->getId();
            $image_value = $_product->getBynderMultiImg();
            $doc_value   = $_product->getBynderDocument();

            $bynder_media_id = explode("\n", $bynder_media_ids);
            $isOrder         = explode("\n", $byd_media_is_order);

            if ($select_attribute == "image") {
                if (!empty($image_value)) {
                    $new_image_array             = explode("\n", $img_json);
                    $bynder_media_id             = explode("\n", $bynder_media_ids);
                    $new_alttext_array           = explode("\n", $img_alt_text);
                    $new_magento_role_option_array = explode("\n", $mg_img_role_option);

                    $all_item_url    = [];
                    $item_old_value  = json_decode($image_value, true);
                    $old_video_value = [];

                    if (is_array($item_old_value) && count($item_old_value) > 0) {
                        foreach ($item_old_value as $img) {
                            $all_item_url[] = $img['thum_url'];
                            if ($img['item_type'] == "VIDEO") {
                                $old_video_value[] = $img;
                            }
                        }
                    }

                    foreach ($new_image_array as $vv => $new_image_value) {
                        if (trim($new_image_value) != "" && $new_image_value != "no image") {
                            $item_url = explode("?", $new_image_value);

                            $img_altText_val = "";
                            if (isset($new_alttext_array[$vv])) {
                                if ($new_alttext_array[$vv] != "###" && strlen(trim($new_alttext_array[$vv])) > 0) {
                                    $img_altText_val = $new_alttext_array[$vv];
                                }
                            }

                            $curt_img_role = [];
                            if ($new_magento_role_option_array[$vv] != "###") {
                                $curt_img_role = [$new_magento_role_option_array[$vv]];
                            }

                            $is_order = isset($isOrder[$vv]) ? $isOrder[$vv] : "";

                            $image_detail[] = [
                                "item_url"     => $new_image_value,
                                "alt_text"     => $img_altText_val,
                                "image_role"   => $curt_img_role,
                                "item_type"    => 'IMAGE',
                                "thum_url"     => $item_url[0],
                                "bynder_md_id" => $bynder_media_id[$vv],
                                "is_import"    => 0,
                                "is_order"     => $is_order
                            ];

                            $total_new_value = count($image_detail);
                            if ($total_new_value > 1) {
                                foreach ($image_detail as $nn => $n_img) {
                                    if ($n_img['item_type'] == "IMAGE" && $nn != ($total_new_value - 1)) {
                                        $new_mg_role_array = (array)$new_magento_role_option_array[$vv];
                                        if (count($n_img["image_role"]) > 0 && count($new_mg_role_array) > 0) {
                                            $result_val = array_diff($n_img["image_role"], $new_mg_role_array);
                                            $image_detail[$nn]["image_role"] = $result_val;
                                        }
                                    }
                                }
                            }

                            if (!in_array($item_url[0], $all_item_url)) {
                                $is_order = isset($isOrder[$vv]) ? $isOrder[$vv] : "";

                                $diff_image_detail[] = [
                                    "item_url"     => $new_image_value,
                                    "alt_text"     => $img_altText_val,
                                    "image_role"   => $curt_img_role,
                                    "item_type"    => 'IMAGE',
                                    "thum_url"     => $item_url[0],
                                    "bynder_md_id" => $bynder_media_id[$vv],
                                    "is_import"    => 0,
                                    "is_order"     => $is_order
                                ];

                                if (is_array($item_old_value) && count($item_old_value) > 0) {
                                    foreach ($item_old_value as $kv => $img) {
                                        if ($img['item_type'] == "IMAGE") {
                                            if ($new_magento_role_option_array[$vv] != "###") {
                                                $new_mg_role_array = (array)$new_magento_role_option_array[$vv];
                                                if (count($img["image_role"]) > 0 && count($new_mg_role_array) > 0) {
                                                    $result_val = array_diff($img["image_role"], $new_mg_role_array);
                                                    $item_old_value[$kv]["image_role"] = $result_val;
                                                }
                                            }
                                        }
                                    }
                                }

                                $total_new_value = count($diff_image_detail);
                                if ($total_new_value > 1) {
                                    foreach ($diff_image_detail as $nn => $n_img) {
                                        if ($n_img['item_type'] == "IMAGE" && $nn != ($total_new_value - 1)) {
                                            if ($new_magento_role_option_array[$vv] != "###") {
                                                $new_mg_role_array = (array)$new_magento_role_option_array[$vv];
                                                if (count($n_img["image_role"]) > 0 && count($new_mg_role_array) > 0) {
                                                    $result_val = array_diff($n_img["image_role"], $new_mg_role_array);
                                                    $diff_image_detail[$nn]["image_role"] = $result_val;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }

                    $d_media_id = [];
                    if (count($diff_image_detail) > 0) {
                        foreach ($diff_image_detail as $d_img) {
                            $d_media_id[] = $d_img['bynder_md_id'];
                        }
                        $this->getInsertMedaiDataTable($product_sku_key, $d_media_id, $product_ids, $storeId);
                    }

                    $array_merge = array_merge($old_video_value, $image_detail);
                    $media_id    = [];
                    $image       = [];
                    $type        = [];
                    foreach ($array_merge as $img) {
                        $type[] = $img['item_type'];
                        if ($img['item_type'] == 'IMAGE') {
                            $image[]    = $img['item_url'];
                            $media_id[] = $img['bynder_md_id'];
                        }
                        $this->getDeleteMedaiDataTable($product_sku_key, $img['bynder_md_id']);
                    }
                    $this->getInsertMedaiDataTable($product_sku_key, $media_id, $product_ids, $storeId);

                    $image_value_array = implode(',', $image);
                    $flag = 0;
                    if (in_array("IMAGE", $type) && in_array("VIDEO", $type)) {
                        $flag = 1;
                    } elseif (in_array("IMAGE", $type)) {
                        $flag = 2;
                    } elseif (in_array("VIDEO", $type)) {
                        $flag = 3;
                    }

                    $new_value_array = json_encode($image_detail, true);
                    $data_image_data = [
                        'sku'       => $product_sku_key,
                        'message'   => $image_value_array,
                        'data_type' => '1',
                        "lable"     => "1"
                    ];
                    $this->getInsertDataTable($data_image_data);
                    $updated_values = [
                        'bynder_multi_img' => $new_value_array,
                        'bynder_isMain'    => $flag,
                        'use_bynder_cdn'   => 1
                    ];
                    $this->productAction->updateAttributes([$product_ids], $updated_values, $storeId);
                } else {
                    // Product has no existing bynder_multi_img — treat as a fresh import
                    $new_image_array             = explode("\n", $img_json);
                    $new_alttext_array           = explode("\n", $img_alt_text);
                    $new_magento_role_option_array = explode("\n", $mg_img_role_option);

                    foreach ($new_image_array as $vv => $image_value) {
                        if (trim($image_value) != "" && $image_value != "no image") {
                            $item_url = explode("?", $image_value);

                            $img_altText_val = "";
                            if (isset($new_alttext_array[$vv])) {
                                if ($new_alttext_array[$vv] != "###" && strlen(trim($new_alttext_array[$vv])) > 0) {
                                    $img_altText_val = $new_alttext_array[$vv];
                                }
                            }

                            $curt_img_role = [];
                            if ($new_magento_role_option_array[$vv] != "###") {
                                $curt_img_role = [$new_magento_role_option_array[$vv]];
                            }

                            $is_order = isset($isOrder[$vv]) ? $isOrder[$vv] : "";
                            $image_detail[] = [
                                "item_url"     => $image_value,
                                "alt_text"     => $img_altText_val,
                                "image_role"   => $curt_img_role,
                                "item_type"    => 'IMAGE',
                                "thum_url"     => $item_url[0],
                                "bynder_md_id" => $bynder_media_id[$vv],
                                "is_import"    => 0,
                                "is_order"     => $is_order
                            ];

                            $total_new_value = count($image_detail);
                            if ($total_new_value > 1) {
                                foreach ($image_detail as $nn => $n_img) {
                                    if ($n_img['item_type'] == "IMAGE" && $nn != ($total_new_value - 1)) {
                                        $new_mg_role_array = (array)$new_magento_role_option_array[$vv];
                                        if (count($n_img["image_role"]) > 0 && count($new_mg_role_array) > 0) {
                                            $result_val = array_diff($n_img["image_role"], $new_mg_role_array);
                                            $image_detail[$nn]["image_role"] = $result_val;
                                        }
                                    }
                                }
                            }
                        }
                    }

                    $m_id  = [];
                    $image = [];
                    $type  = [];
                    foreach ($image_detail as $img) {
                        $type[]  = $img['item_type'];
                        $image[] = $img['item_url'];
                        $m_id[]  = $img['bynder_md_id'];
                        $this->getDeleteMedaiDataTable($product_sku_key, $img['bynder_md_id']);
                    }
                    $this->getInsertMedaiDataTable($product_sku_key, $m_id, $product_ids, $storeId);

                    $image_value_array = implode(',', $image);
                    $flag = 0;
                    if (in_array("IMAGE", $type) && in_array("VIDEO", $type)) {
                        $flag = 1;
                    } elseif (in_array("IMAGE", $type)) {
                        $flag = 2;
                    } elseif (in_array("VIDEO", $type)) {
                        $flag = 3;
                    }

                    $data_image_data = [
                        'sku'       => $product_sku_key,
                        'message'   => $image_value_array,
                        'data_type' => '1',
                        "lable"     => "1"
                    ];
                    $this->getInsertDataTable($data_image_data);
                    $new_value_array = json_encode($image_detail, true);
                    $updated_values  = [
                        'bynder_multi_img' => $new_value_array,
                        'bynder_isMain'    => $flag,
                        'use_bynder_cdn'   => 1
                    ];
                    $this->productAction->updateAttributes([$product_ids], $updated_values, $storeId);
                }
            } elseif ($select_attribute == "video") {
                if (!empty($image_value)) {
                    $new_video_array = explode(" \n", $img_json);
                    $old_value_array = json_decode($image_value, true);
                    $old_item_url    = [];
                    if (!empty($old_value_array)) {
                        foreach ($old_value_array as $value) {
                            $old_item_url[] = $value['item_url'];
                        }
                    }
                    $video_detail = [];
                    foreach ($new_video_array as $vv => $video_value) {
                        $item_url = explode("?", $video_value);
                        $thum_url = explode("@@", $video_value);
                        if (!in_array($item_url[0], $old_item_url)) {
                            $is_order = isset($isOrder[$vv]) ? $isOrder[$vv] : "";
                            $video_detail[] = [
                                "item_url"     => $item_url[0],
                                "image_role"   => null,
                                "item_type"    => 'VIDEO',
                                "thum_url"     => $thum_url[1],
                                "bynder_md_id" => $bynder_media_id[$vv],
                                "is_order"     => $is_order
                            ];
                        }
                    }

                    $array_merge = $old_value_array;
                    $flag = 0;
                    if (!empty($old_value_array)) {
                        $array_merge = array_merge($old_value_array, $video_detail);
                        $type = [];
                        foreach ($array_merge as $img) {
                            $type[] = $img['item_type'];
                        }
                        if (in_array("IMAGE", $type) && in_array("VIDEO", $type)) {
                            $flag = 1;
                        } elseif (in_array("IMAGE", $type)) {
                            $flag = 2;
                        } elseif (in_array("VIDEO", $type)) {
                            $flag = 3;
                        }
                    }

                    $new_value_array = json_encode($array_merge, true);
                    $data_video_data = [
                        'sku'       => $product_sku_key,
                        'message'   => $new_value_array,
                        'data_type' => '3',
                        "lable"     => "1"
                    ];
                    $this->getInsertDataTable($data_video_data);
                    $updated_values = [
                        'bynder_multi_img' => $new_value_array,
                        'bynder_isMain'    => $flag,
                        'use_bynder_cdn'   => 1
                    ];
                    $this->productAction->updateAttributes([$product_ids], $updated_values, $storeId);
                } else {
                    $new_video_array = explode(" \n", $img_json);
                    $video_detail    = [];
                    foreach ($new_video_array as $vv => $video_value) {
                        $item_url = explode("?", $video_value);
                        $thum_url = explode("@@", $video_value);
                        $is_order = isset($isOrder[$vv]) ? $isOrder[$vv] : "";
                        $video_detail[] = [
                            "item_url"     => $item_url[0],
                            "image_role"   => null,
                            "item_type"    => 'VIDEO',
                            "thum_url"     => $thum_url[1],
                            "bynder_md_id" => $bynder_media_id,
                            "is_order"     => $is_order
                        ];
                    }

                    $type = [];
                    foreach ($video_detail as $img) {
                        $type[] = $img['item_type'];
                    }
                    $flag = 0;
                    if (in_array("IMAGE", $type) && in_array("VIDEO", $type)) {
                        $flag = 1;
                    } elseif (in_array("IMAGE", $type)) {
                        $flag = 2;
                    } elseif (in_array("VIDEO", $type)) {
                        $flag = 3;
                    }

                    $new_value_array = json_encode($video_detail, true);
                    $data_video_data = [
                        'sku'       => $product_sku_key,
                        'message'   => $new_value_array,
                        'data_type' => '3',
                        "lable"     => "1"
                    ];
                    $this->getInsertDataTable($data_video_data);
                    $updated_values = [
                        'bynder_multi_img' => $new_value_array,
                        'bynder_isMain'    => $flag,
                        'use_bynder_cdn'   => 1
                    ];
                    $this->productAction->updateAttributes([$product_ids], $updated_values, $storeId);
                }
            } else {
                // Document sync
                if (empty($doc_value)) {
                    $new_doc_array = explode(" \n", $img_json);
                    $doc_detail    = [];
                    foreach ($new_doc_array as $vv => $doc_item) {
                        $item_url = explode("?", $doc_item);
                        $is_order = isset($isOrder[$vv]) ? $isOrder[$vv] : "";
                        $doc_detail[] = [
                            "item_url"     => $item_url[0],
                            "item_type"    => 'DOCUMENT',
                            "bynder_md_id" => $bynder_media_id[$vv],
                            "is_order"     => $is_order
                        ];
                    }
                    $new_value_array = json_encode($doc_detail, true);
                    $data_doc_value  = [
                        'sku'       => $product_sku_key,
                        'message'   => $new_value_array,
                        'data_type' => '2',
                        "lable"     => "1"
                    ];
                    $this->getInsertDataTable($data_doc_value);
                    $this->productAction->updateAttributes(
                        [$product_ids],
                        ['bynder_document' => $new_value_array],
                        $storeId
                    );
                } else {
                    $b_id           = [];
                    $all_item_url   = [];
                    $item_old_value = json_decode($doc_value, true);

                    if (is_array($item_old_value) && count($item_old_value) > 0) {
                        foreach ($item_old_value as $doc) {
                            if ($doc['item_type'] == 'DOCUMENT') {
                                $all_item_url[] = $doc['item_url'];
                                $b_id[]         = $doc['bynder_md_id'];
                            }
                        }
                    }

                    $new_doc_array = explode("\n", $img_json);
                    $doc_detail    = [];
                    foreach ($new_doc_array as $vv => $doc_item) {
                        if (!empty($doc_item)) {
                            if (!in_array($bynder_media_id[$vv], $b_id)) {
                                $item_url = explode("?", $doc_item);
                                $is_order = isset($isOrder[$vv]) ? $isOrder[$vv] : "";
                                $doc_detail[] = [
                                    "item_url"     => $item_url[0],
                                    "item_type"    => 'DOCUMENT',
                                    "bynder_md_id" => $bynder_media_id[$vv],
                                    "is_order"     => $is_order
                                ];
                            }
                        }
                    }

                    $new_value_array = json_encode($doc_detail, true);
                    $array_merg      = array_merge($item_old_value, $doc_detail);
                    $doc_urls        = [];
                    foreach ($array_merg as $doc) {
                        $doc_urls[] = $doc['item_url'];
                    }
                    $doc_value_array = implode(',', $doc_urls);
                    $data_doc_value  = [
                        'sku'       => $product_sku_key,
                        'message'   => $doc_value_array,
                        'data_type' => '2',
                        "lable"     => "1"
                    ];
                    $this->getInsertDataTable($data_doc_value);
                    $this->productAction->updateAttributes(
                        [$product_ids],
                        ['bynder_document' => $new_value_array],
                        $storeId
                    );
                }
            }
        } catch (\Exception $e) {
            return $result->setData(['message' => $e->getMessage()]);
        }
    }
}
