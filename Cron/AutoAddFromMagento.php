<?php

namespace DamConsultants\Akima\Cron;

use Exception;
use Psr\Log\LoggerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Model\Product\Action;
use DamConsultants\Akima\Model\BynderFactory;
use DamConsultants\Akima\Model\ResourceModel\Collection\MetaPropertyCollectionFactory;
use DamConsultants\Akima\Model\ResourceModel\Collection\BynderMediaTableCollectionFactory;

class AutoAddFromMagento
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ProductRepository
     */
    protected $_productRepository;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var \DamConsultants\Akima\Helper\Data
     */
    protected $datahelper;

    /**
     * @var Action
     */
    protected $action;

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
     * @var StoreManagerInterface
     */
    protected $storeManagerInterface;

    /**
     * @var BynderFactory
     */
    protected $bynder;

    /**
     * @var \DamConsultants\Akima\Model\BynderSycDataFactory
     */
    protected $_byndersycData;

    /**
     * @var \DamConsultants\Akima\Model\BynderAutoReplaceDataFactory
     */
    protected $_bynderAutoReplaceData;

    /**
     * AutoAddFromMagento constructor.
     *
     * @param LoggerInterface $logger
     * @param ProductRepository $productRepository
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory
     * @param StoreManagerInterface $storeManagerInterface
     * @param \DamConsultants\Akima\Helper\Data $DataHelper
     * @param \DamConsultants\Akima\Model\BynderMediaTableFactory $bynderMediaTable
     * @param \DamConsultants\Akima\Model\BynderAutoReplaceDataFactory $bynderAutoReplaceData
     * @param BynderMediaTableCollectionFactory $bynderMediaTableCollectionFactory
     * @param Action $action
     * @param MetaPropertyCollectionFactory $metaPropertyCollectionFactory
     * @param \DamConsultants\Akima\Model\BynderSycDataFactory $byndersycData
     * @param BynderFactory $bynder
     */
    public function __construct(
        LoggerInterface $logger,
        ProductRepository $productRepository,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory,
        StoreManagerInterface $storeManagerInterface,
        \DamConsultants\Akima\Helper\Data $DataHelper,
        \DamConsultants\Akima\Model\BynderMediaTableFactory $bynderMediaTable,
        \DamConsultants\Akima\Model\BynderAutoReplaceDataFactory $bynderAutoReplaceData,
        BynderMediaTableCollectionFactory $bynderMediaTableCollectionFactory,
        Action $action,
        MetaPropertyCollectionFactory $metaPropertyCollectionFactory,
        \DamConsultants\Akima\Model\BynderSycDataFactory $byndersycData,
        BynderFactory $bynder
    ) {
        $this->logger                            = $logger;
        $this->_productRepository                = $productRepository;
        $this->collectionFactory                 = $collectionFactory;
        $this->datahelper                        = $DataHelper;
        $this->action                            = $action;
        $this->_byndersycData                    = $byndersycData;
        $this->_bynderAutoReplaceData            = $bynderAutoReplaceData;
        $this->metaPropertyCollectionFactory     = $metaPropertyCollectionFactory;
        $this->bynderMediaTable                  = $bynderMediaTable;
        $this->bynderMediaTableCollectionFactory = $bynderMediaTableCollectionFactory;
        $this->storeManagerInterface             = $storeManagerInterface;
        $this->bynder                            = $bynder;
    }

    /**
     * Cron entry point: auto-add Bynder image data to products that have a Bynder
     * multi-image value but have not yet been auto-replaced.
     *
     * @return bool
     */
    public function execute()
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/AutoAddFromMagento.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info("Auto Add Image Value");

        $enable = $this->datahelper->getAutoCronEnable();
        if (!$enable) {
            return false;
        }

        $product_collection  = $this->collectionFactory->create();
        $product_sku_limit   = (int) $this->datahelper->getProductSkuLimitConfig();
        $limit               = $product_sku_limit > 0 ? $product_sku_limit : 50;
        $product_collection->getSelect()->limit($limit);

        $product_collection->addAttributeToSelect('*')
            ->addAttributeToFilter([['attribute' => 'bynder_multi_img', 'notnull' => true]])
            ->addAttributeToFilter([['attribute' => 'bynder_auto_replace', 'null' => true]])
            ->load();

        $property_id       = null;
        $collection        = $this->metaPropertyCollectionFactory->create()->getData();
        $meta_properties   = $this->getMetaPropertiesCollection($collection);
        $collection_value  = $meta_properties['collection_data_value'];
        $collection_slug_val = $meta_properties['collection_data_slug_val'];

        $productSku_array = [];
        foreach ($product_collection->getData() as $product) {
            $productSku_array[] = $product['sku'];
        }

        $logger->info("sku -> " . json_encode($productSku_array));

        if (count($productSku_array) > 0) {
            foreach ($productSku_array as $sku) {
                if ($sku === "") {
                    continue;
                }
                $bd_sku   = $this->datahelper->replacetoSpecialString($sku);
                $get_data = $this->datahelper->getImageSyncWithProperties($bd_sku, $property_id, $collection_value);

                if (!empty($get_data) && $this->getIsJSON($get_data)) {
                    $respon_array = json_decode($get_data, true);
                    if ($respon_array['status'] == 1) {
                        $convert_array = json_decode($respon_array['data'], true);
                        if ($convert_array['status'] == 1) {
                            try {
                                $this->getDataItem("image", $convert_array, $collection_slug_val, $sku);
                            } catch (Exception $e) {
                                $this->getInsertDataTable([
                                    "sku"       => $sku,
                                    "message"   => $e->getMessage(),
                                    'media_id'  => "",
                                    "data_type" => ""
                                ]);
                            }
                        } else {
                            $this->getInsertDataTable([
                                "sku"       => $sku,
                                "message"   => $convert_array['data'],
                                'media_id'  => "",
                                "data_type" => ""
                            ]);
                        }
                    } else {
                        $this->getInsertDataTable([
                            "sku"       => $sku,
                            "message"   => 'Please Select The Metaproperty First.....',
                            'media_id'  => "",
                            "data_type" => ""
                        ]);
                    }
                } else {
                    $this->getInsertDataTable([
                        "sku"       => $sku,
                        "message"   => "Something problem in DAM side please contact to developer.",
                        'media_id'  => "",
                        "data_type" => ""
                    ]);
                }
            }
        } else {
            $product_collection = $this->collectionFactory->create()
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('visibility', \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
                ->addAttributeToFilter('status', \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
                ->addAttributeToFilter([['attribute' => 'bynder_auto_replace', 'notnull' => true]])
                ->load();

            $id = [];
            foreach ($product_collection as $product) {
                $id[] = $product->getId();
            }
            $storeId = $this->storeManagerInterface->getStore()->getId();
            $this->action->updateAttributes($id, ['bynder_auto_replace' => ""], $storeId);
            $logger->info("bynder_auto_replace null ");
        }

        return true;
    }

    /**
     * Build structured arrays from the raw meta-property collection.
     *
     * @param array $collection
     * @return array{collection_data_value: array, collection_data_slug_val: array}
     */
    public function getMetaPropertiesCollection($collection)
    {
        $collection_data_value    = [];
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

        return [
            "collection_data_value"    => $collection_data_value,
            "collection_data_slug_val" => $collection_data_slug_val
        ];
    }

    /**
     * Return the current store ID.
     *
     * @return int
     */
    public function getMyStoreId()
    {
        return $this->storeManagerInterface->getStore()->getId();
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
     * Insert a Bynder auto-replace log record.
     *
     * @param array $insert_data  Keys: sku, message, media_id, data_type
     * @return void
     */
    public function getInsertDataTable($insert_data)
    {
        $model = $this->_bynderAutoReplaceData->create();
        $model->setData([
            'sku'              => $insert_data['sku'],
            'bynder_data'      => $insert_data['message'],
            'media_id'         => $insert_data['media_id'],
            'bynder_data_type' => $insert_data['data_type']
        ]);
        $model->save();
    }

    /**
     * Insert new Bynder media IDs into the media table and flag the product for CDN deletion.
     *
     * @param string $sku
     * @param array  $media_id    Array of Bynder media IDs to insert
     * @param int    $product_ids
     * @param int    $storeId
     * @return void
     */
    public function getInsertMedaiDataTable($sku, $media_id, $product_ids, $storeId)
    {
        $model           = $this->bynderMediaTable->create();
        $modelcollection = $this->bynderMediaTableCollectionFactory->create();
        $modelcollection->addFieldToFilter('sku', ['eq' => [$sku]])->load();

        $table_m_id = [];
        foreach ($modelcollection as $mdata) {
            $table_m_id[] = $mdata['media_id'];
        }

        $media_diff = array_diff($media_id, $table_m_id);
        foreach ($media_diff as $new_data) {
            $model->setData([
                'sku'      => $sku,
                'media_id' => trim($new_data),
                'status'   => "1",
            ]);
            $model->save();
        }

        $this->action->updateAttributes([$product_ids], ['bynder_delete_cron' => 1], $storeId);
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
        $model = $this->bynderMediaTableCollectionFactory->create()
            ->addFieldToFilter('sku', ['eq' => [$sku]])
            ->load();

        foreach ($model as $mdata) {
            if ($mdata['media_id'] != $media_id) {
                $this->bynderMediaTable->create()->load($mdata['id'])->delete();
            }
        }
    }

    /**
     * Parse the Bynder API response and build structured data arrays for images.
     *
     * @param string $select_attribute   Media type filter (e.g. 'image')
     * @param array  $convert_array      Decoded Bynder API response data
     * @param array  $collection_data_slug_val  Slug-to-property mapping
     * @param string $current_sku        The product SKU being processed
     * @return void
     */
    public function getDataItem($select_attribute, $convert_array, $collection_data_slug_val, $current_sku)
    {
        $data_arr    = [];
        $data_val_arr = [];

        if ($convert_array['status'] != 0) {
            foreach ($convert_array['data'] as $data_value) {
                if ($select_attribute != $data_value['type']) {
                    continue;
                }

                $bynder_media_id  = $data_value['id'];
                $image_data       = $data_value['thumbnails'];
                $bynder_image_role = $image_data['magento_role_options'];
                $data_sku         = [$current_sku];

                $images_urls_list       = [];
                $new_magento_role_list  = [];
                $new_bynder_alt_text    = [];
                $new_bynder_mediaid_text = [];
                $is_order               = [];

                if (count($bynder_image_role) > 0) {
                    foreach ($bynder_image_role as $m_bynder_role) {
                        $original_m_bynder_role      = $m_bynder_role;
                        $original_m_bynder_role_slug = ($m_bynder_role === "Base") ? "Base image" : $m_bynder_role;

                        if (isset($data_value["thumbnails"][$original_m_bynder_role_slug])) {
                            $images_urls_list[] = $data_value["thumbnails"][$original_m_bynder_role_slug] . "\n";
                        } else {
                            $images_urls_list[] = $data_value["thumbnails"]["Product"] . "\n";
                        }

                        $new_magento_role_list[] = $original_m_bynder_role . "\n";

                        $alt_text_vl = $data_value["thumbnails"]["img_alt_text"];
                        if (is_array($alt_text_vl)) {
                            $alt_text_vl = implode(" ", $alt_text_vl);
                        }
                        $new_bynder_alt_text[] = (strlen($alt_text_vl) > 0) ? $alt_text_vl . "\n" : "###\n";

                        $new_bynder_mediaid_text[] = $bynder_media_id . "\n";
                        $magento_order_slug = "property_" . $collection_data_slug_val['image_order']['bynder_property_slug'];
                        if (isset($data_value[$magento_order_slug])) {
                            foreach ($data_value[$magento_order_slug] as $property_Magento_Media_Order) {
                                $is_order[] = $property_Magento_Media_Order . "\n";
                            }
                        }
                    }
                } else {
                    $new_magento_role_list[] = "###\n";

                    $alt_text_vl = $data_value["thumbnails"]["img_alt_text"];
                    if (is_array($alt_text_vl)) {
                        $alt_text_vl = implode(" ", $alt_text_vl);
                    }
                    $new_bynder_alt_text[] = !empty($alt_text_vl) ? $alt_text_vl . "\n" : "###\n";

                    $new_bynder_mediaid_text[] = $bynder_media_id . "\n";
                    $magento_order_slug = "property_" . $collection_data_slug_val['image_order']['bynder_property_slug'];
                    if (isset($data_value[$magento_order_slug])) {
                        foreach ($data_value[$magento_order_slug] as $property_Magento_Media_Order) {
                            $is_order[] = $property_Magento_Media_Order . "\n";
                        }
                    }
                }

                if (count($images_urls_list) == 0) {
                    $images_urls_list[] = isset($image_data["Product"])
                        ? $image_data["Product"] . "\n"
                        : "no image\n";
                }

                if ($data_value['type'] == "image") {
                    $data_arr[]    = $data_sku[0];
                    $data_val_arr[] = [
                        "sku"                 => $data_sku[0],
                        "url"                 => $images_urls_list,
                        "magento_image_role"  => $new_magento_role_list,
                        "type"                => $data_value['type'],
                        "image_alt_text"      => $new_bynder_alt_text,
                        "bynder_media_id_new" => $new_bynder_mediaid_text,
                        'is_order'            => $is_order
                    ];
                } elseif ($select_attribute == 'video1') {
                    $video_link  = $image_data["image_link"] . '@@' . $image_data["webimage"];
                    $data_arr[]  = $data_sku[0];
                    $data_val_arr[] = ["sku" => $data_sku[0], "url" => $video_link, 'is_order' => $is_order];
                } elseif ($select_attribute == 'document1') {
                    $doc_name            = $data_value["name"];
                    $doc_name_with_space = preg_replace("/[^a-zA-Z]+/", "-", $doc_name);
                    $doc_link            = $image_data["image_link"] . '@@' . $doc_name_with_space;
                    $data_arr[]          = $data_sku[0];
                    $data_val_arr[]      = ["sku" => $data_sku[0], "url" => $doc_link, 'is_order' => $is_order];
                }
            }
        }

        if (count($data_arr) > 0) {
            $this->getProcessItem($data_arr, $data_val_arr);
        }
    }

    /**
     * Group data by SKU and forward each SKU's aggregated data to getUpdateImage().
     *
     * @param array $data_arr
     * @param array $data_val_arr
     * @return void
     */
    public function getProcessItem($data_arr, $data_val_arr)
    {
        $image_value_details_role = [];
        $temp_arr                 = [];
        $byn_is_order             = [];
        $image_alt_text           = [];
        $bynder_media_id_new      = [];

        foreach ($data_arr as $key => $skus) {
            $temp_arr[$skus][]                  = implode("", $data_val_arr[$key]["url"]);
            $image_value_details_role[$skus][]  = implode("", $data_val_arr[$key]["magento_image_role"]);
            $image_alt_text[$skus][]            = implode("", $data_val_arr[$key]["image_alt_text"]);
            $bynder_media_id_new[$skus][]       = implode("", $data_val_arr[$key]["bynder_media_id_new"]);
            $byn_is_order[$skus][]              = implode("", $data_val_arr[$key]["is_order"]);
        }

        foreach ($temp_arr as $product_sku_key => $image_value) {
            $this->getUpdateImage(
                implode("", $image_value),
                $product_sku_key,
                implode("", $image_value_details_role[$product_sku_key]),
                implode("", $image_alt_text[$product_sku_key]),
                implode("", $bynder_media_id_new[$product_sku_key]),
                implode("", $byn_is_order[$product_sku_key])
            );
        }
    }

    /**
     * Apply synced Bynder media data to the Magento product attributes.
     *
     * @param string $img_json           Newline-delimited image URLs
     * @param string $product_sku_key    Product SKU
     * @param string $mg_img_role_option Newline-delimited Magento image roles
     * @param string $img_alt_text       Newline-delimited alt-text values
     * @param string $bynder_media_ids   Newline-delimited Bynder media IDs
     * @param string $byd_media_is_order Newline-delimited sort-order values
     * @return void
     */
    public function getUpdateImage(
        $img_json,
        $product_sku_key,
        $mg_img_role_option,
        $img_alt_text,
        $bynder_media_ids,
        $byd_media_is_order
    ) {
        $diff_image_detail = [];
        $image_detail      = [];
        $select_attribute  = "image";

        try {
            $storeId      = $this->storeManagerInterface->getStore()->getId();
            $_product     = $this->_productRepository->get($product_sku_key);
            $product_ids  = $_product->getId();
            $image_value  = $_product->getBynderMultiImg();
            $doc_value    = $_product->getBynderDocument();
            $auto_replace = $_product->getBynderAutoReplace();

            $bynder_media_id = explode("\n", $bynder_media_ids);
            $isOrder         = explode("\n", $byd_media_is_order);

            if ($select_attribute == "image") {
                if (!empty($image_value) && $auto_replace == null) {
                    $new_image_array              = explode("\n", $img_json);
                    $new_alttext_array            = explode("\n", $img_alt_text);
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
                        if (trim($new_image_value) == "" || $new_image_value == "no image") {
                            continue;
                        }

                        $item_url = explode("?", $new_image_value);

                        $img_altText_val = "";
                        if (isset($new_alttext_array[$vv])
                            && $new_alttext_array[$vv] != "###"
                            && strlen(trim($new_alttext_array[$vv])) > 0
                        ) {
                            $img_altText_val = $new_alttext_array[$vv];
                        }

                        $curt_img_role = [];
                        if ($new_magento_role_option_array[$vv] != "###") {
                            $curt_img_role = [$new_magento_role_option_array[$vv]];
                        }

                        $is_order       = isset($isOrder[$vv]) ? $isOrder[$vv] : "";
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

                        if (!in_array($item_url[0], $all_item_url)) {
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
                            $this->getInsertDataTable([
                                'sku'       => $product_sku_key,
                                'message'   => $new_image_value,
                                'media_id'  => $bynder_media_id[$vv],
                                'data_type' => '1'
                            ]);

                            if (is_array($item_old_value) && count($item_old_value) > 0) {
                                foreach ($item_old_value as $kv => $img) {
                                    if ($img['item_type'] == "IMAGE"
                                        && $new_magento_role_option_array[$vv] != "###"
                                    ) {
                                        $new_mg_role_array = (array) $new_magento_role_option_array[$vv];
                                        if (count($img["image_role"]) > 0 && count($new_mg_role_array) > 0) {
                                            $item_old_value[$kv]["image_role"] = array_diff(
                                                $img["image_role"],
                                                $new_mg_role_array
                                            );
                                        }
                                    }
                                }
                            }

                            $total_new_value = count($image_detail);
                            if ($total_new_value > 1) {
                                foreach ($image_detail as $nn => $n_img) {
                                    if ($n_img['item_type'] == "IMAGE"
                                        && $nn != ($total_new_value - 1)
                                        && $new_magento_role_option_array[$vv] != "###"
                                    ) {
                                        $new_mg_role_array = (array) $new_magento_role_option_array[$vv];
                                        if (count($n_img["image_role"]) > 0 && count($new_mg_role_array) > 0) {
                                            $image_detail[$nn]["image_role"] = array_diff(
                                                $n_img["image_role"],
                                                $new_mg_role_array
                                            );
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

                    $flag = $this->resolveMediaFlag($type);

                    $this->action->updateAttributes([$product_ids], [
                        'bynder_multi_img'   => json_encode($array_merge),
                        'bynder_isMain'      => $flag,
                        'bynder_auto_replace' => 1,
                        'use_bynder_cdn'     => 1
                    ], $storeId);
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
                            $is_order       = isset($isOrder[$vv]) ? $isOrder[$vv] : "";
                            $video_detail[] = [
                                "item_url"     => $item_url[0],
                                "image_role"   => null,
                                "item_type"    => 'VIDEO',
                                "thum_url"     => $thum_url[1],
                                "bynder_md_id" => $bynder_media_id[$vv],
                                "is_order"     => $is_order
                            ];
                            $this->getInsertDataTable([
                                'sku'       => $product_sku_key,
                                'message'   => $item_url[0],
                                'media_id'  => $bynder_media_id[$vv],
                                'data_type' => '3'
                            ]);
                        }
                    }

                    $array_merge = $old_value_array ?? [];
                    $flag        = 0;

                    if (!empty($old_value_array)) {
                        $array_merge = array_merge($old_value_array, $video_detail);
                        $type        = array_column($array_merge, 'item_type');
                        $flag        = $this->resolveMediaFlag($type);
                    }

                    $this->action->updateAttributes([$product_ids], [
                        'bynder_multi_img'   => json_encode($array_merge),
                        'bynder_isMain'      => $flag,
                        'bynder_auto_replace' => 1,
                        'use_bynder_cdn'     => 1
                    ], $storeId);
                } else {
                    $new_video_array = explode(" \n", $img_json);
                    $video_detail    = [];

                    foreach ($new_video_array as $vv => $video_value) {
                        $item_url = explode("?", $video_value);
                        $thum_url = explode("@@", $video_value);
                        $is_order = isset($isOrder[$vv]) ? $isOrder[$vv] : "";

                        $media_video_explode = explode("/", $item_url[0]);
                        $video_detail[]      = [
                            "item_url"     => $item_url[0],
                            "image_role"   => null,
                            "item_type"    => 'VIDEO',
                            "thum_url"     => $thum_url[1],
                            "bynder_md_id" => $bynder_media_id,
                            "is_order"     => $is_order
                        ];
                        $this->getInsertDataTable([
                            'sku'       => $product_sku_key,
                            'message'   => $item_url[0],
                            'media_id'  => $media_video_explode[5],
                            'data_type' => '3'
                        ]);
                    }

                    $type = array_column($video_detail, 'item_type');
                    $flag = $this->resolveMediaFlag($type);

                    $this->action->updateAttributes([$product_ids], [
                        'bynder_multi_img'   => json_encode($video_detail),
                        'bynder_isMain'      => $flag,
                        'bynder_auto_replace' => 1,
                        'use_bynder_cdn'     => 1
                    ], $storeId);
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
                        $this->getInsertDataTable([
                            'sku'       => $product_sku_key,
                            'message'   => $item_url[0],
                            'media_id'  => $bynder_media_id[$vv],
                            'data_type' => '2'
                        ]);
                    }

                    $this->action->updateAttributes(
                        [$product_ids],
                        ['bynder_document' => json_encode($doc_detail), 'bynder_cron_sync' => 1],
                        $storeId
                    );
                }
            }
        } catch (Exception $e) {
            $this->getInsertDataTable([
                "sku"       => $product_sku_key,
                "message"   => $e->getMessage(),
                'media_id'  => "",
                "data_type" => ""
            ]);
        }
    }

    /**
     * Derive the bynder_isMain flag from the list of item types present in the array.
     *
     * - 0 : none
     * - 1 : IMAGE + VIDEO
     * - 2 : IMAGE only
     * - 3 : VIDEO only
     *
     * @param array $types  Array of 'IMAGE' / 'VIDEO' strings
     * @return int
     */
    private function resolveMediaFlag(array $types): int
    {
        $hasImage = in_array("IMAGE", $types);
        $hasVideo = in_array("VIDEO", $types);

        if ($hasImage && $hasVideo) {
            return 1;
        }
        if ($hasImage) {
            return 2;
        }
        if ($hasVideo) {
            return 3;
        }
        return 0;
    }

    /**
     * Update the bynder_cron_sync attribute to 2 for the given SKU.
     *
     * @param string $sku
     * @return void
     */
    public function updateBynderCronSync($sku)
    {
        $storeId     = $this->getMyStoreId();
        $_product    = $this->_productRepository->get($sku);
        $product_ids = $_product->getId();
        $this->action->updateAttributes([$product_ids], ['bynder_cron_sync' => 2], $storeId);
    }
}
