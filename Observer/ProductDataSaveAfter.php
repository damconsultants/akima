<?php

namespace DamConsultants\Akima\Observer;

use Magento\Framework\Event\ObserverInterface;
use DamConsultants\Akima\Model\ResourceModel\Collection\MetaPropertyCollectionFactory;
use DamConsultants\Akima\Model\ResourceModel\Collection\BynderMediaTableCollectionFactory;
use DamConsultants\Akima\Model\ResourceModel\Collection\BynderTempDataCollectionFactory;
use DamConsultants\Akima\Model\ResourceModel\Collection\BynderTempDocDataCollectionFactory;

class ProductDataSaveAfter implements ObserverInterface
{
    /**
     * @var $cookieManager
     */
    protected $cookieManager;
    /**
     * @var $cookieManager
     */
    protected $cookieMetadataFactory;
    /**
     * @var $cookieManager
     */
    protected $productActionObject;
    /**
     * @var $cookieManager
     */
    protected $_byndersycData;
    /**
     * @var $cookieManager
     */
    protected $datahelper;
    /**
     * @var $cookieManager
     */
    protected $bynderMediaTable;
    /**
     * @var $cookieManager
     */
    protected $bynderMediaTableCollectionFactory;
    /**
     * @var $cookieManager
     */
    protected $metaPropertyCollectionFactory;
    /**
     * @var $cookieManager
     */
    protected $_collection;
    /**
     * @var $cookieManager
     */
    protected $bynderTempData;
    /**
     * @var $cookieManager
     */
    protected $bynderTempDataCollectionFactory;
    /**
     * @var $cookieManager
     */
    protected $bynderTempDocData;
    /**
     * @var $cookieManager
     */
    protected $bynderTempDocDataCollectionFactory;
    /**
     * @var $cookieManager
     */
    protected $_resource;
    /**
     * @var $cookieManager
     */
    protected $storeManagerInterface;
    /**
     * @var $cookieManager
     */
    protected $messageManager;
    /**
     * @var $cookieManager
     */
    protected $resultRedirectFactory;

    /**
     * Product save after
     * @param \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager
     * @param \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory
     * @param \Magento\Catalog\Model\Product\Action $productActionObject
     * @param \DamConsultants\Akima\Model\BynderSycDataFactory $byndersycData
     * @param \DamConsultants\Akima\Model\ResourceModel\Collection\BynderSycDataCollectionFactory $collection
     * @param \DamConsultants\Akima\Model\BynderMediaTableFactory $bynderMediaTable
     * @param BynderMediaTableCollectionFactory $bynderMediaTableCollectionFactory
     * @param \DamConsultants\Akima\Model\BynderTempDataFactory $bynderTempData
     * @param BynderTempDataCollectionFactory $bynderTempDataCollectionFactory
     * @param \DamConsultants\Akima\Model\BynderTempDocDataFactory $bynderTempDocData
     * @param BynderTempDocDataCollectionFactory $bynderTempDocDataCollectionFactory
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \DamConsultants\Akima\Helper\Data $DataHelper
     * @param MetaPropertyCollectionFactory $metaPropertyCollectionFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Backend\Model\View\Result\Redirect $resultRedirect
     */

    public function __construct(
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory,
        \Magento\Catalog\Model\Product\Action $productActionObject,
        \DamConsultants\Akima\Model\BynderSycDataFactory $byndersycData,
        \DamConsultants\Akima\Model\ResourceModel\Collection\BynderSycDataCollectionFactory $collection,
        \DamConsultants\Akima\Model\BynderMediaTableFactory $bynderMediaTable,
        BynderMediaTableCollectionFactory $bynderMediaTableCollectionFactory,
        \DamConsultants\Akima\Model\BynderTempDataFactory $bynderTempData,
        BynderTempDataCollectionFactory $bynderTempDataCollectionFactory,
        \DamConsultants\Akima\Model\BynderTempDocDataFactory $bynderTempDocData,
        BynderTempDocDataCollectionFactory $bynderTempDocDataCollectionFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        \DamConsultants\Akima\Helper\Data $DataHelper,
        MetaPropertyCollectionFactory $metaPropertyCollectionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Backend\Model\View\Result\Redirect $resultRedirect
    ) {
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->productActionObject = $productActionObject;
        $this->_byndersycData = $byndersycData;
        $this->datahelper = $DataHelper;
        $this->bynderMediaTable = $bynderMediaTable;
        $this->bynderMediaTableCollectionFactory = $bynderMediaTableCollectionFactory;
        $this->metaPropertyCollectionFactory = $metaPropertyCollectionFactory;
        $this->_collection = $collection;
        $this->bynderTempData = $bynderTempData;
        $this->bynderTempDataCollectionFactory = $bynderTempDataCollectionFactory;
        $this->bynderTempDocData = $bynderTempDocData;
        $this->bynderTempDocDataCollectionFactory = $bynderTempDocDataCollectionFactory;
        $this->_resource = $resource;
        $this->storeManagerInterface = $storeManagerInterface;
        $this->messageManager = $messageManager;
        $this->resultRedirectFactory = $resultRedirect;
    }
    /**
     * Execute
     *
     * @return $this
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $bdomain_chk_config = str_replace(
            "https://",
            "",
            $this->datahelper->getBynderDom()
        );
        $product = $observer->getProduct();
        $productId = $observer->getProduct()->getId();
        $product_sku_key = $product->getData('sku');

        $bynder_multi_img = $product->getData('bynder_multi_img');

        /**Doing new code and new requirements for theines */

        $bynder_document = $product->getData('bynder_document');
        $storeId = $this->storeManagerInterface->getStore()->getId();
        //$document = $this->cookieManager->getCookie('bynder_doc');
        $model = $this->_byndersycData->create();
        $collection = $this->_collection->create()->addFieldToFilter('sku', $product_sku_key);
        $delete_collection = $this->_collection->create()->addFieldToFilter('remove_for_magento', '0');
        $all_meta_properties = $metaProperty_collection = $this->metaPropertyCollectionFactory->create()->getData();
        $collection_data_value = [];
        $collection_data_slug_val = [];
        $image_coockie_id = $this->cookieManager->getCookie('image_coockie_id');
        $doc_coockie_id = $this->cookieManager->getCookie('doc_coockie_id');
        if ($image_coockie_id != 0) {
            $bynderTempdata = $this->bynderTempDataCollectionFactory->create()
            ->addFieldToFilter('id', $image_coockie_id)->load();
            if (isset($bynderTempdata)) {
                foreach ($bynderTempdata as $record) {
                    $image = $record['value'];
                }
            }
        } else {
            $image = $bynder_multi_img;
        }
        $new_bynder_array = $image;
        $old_bynder_array = $bynder_multi_img;
        $image_details[] = [
            "old" => $bynder_multi_img,
            "new" => $image
        ];
        if ($doc_coockie_id != 0) {
            $bynderTempdocdata = $this->bynderTempDocDataCollectionFactory->create()
            ->addFieldToFilter('id', $doc_coockie_id)->load();
            if (isset($bynderTempdocdata)) {
                foreach ($bynderTempdocdata as $recorddoc) {
                    $document = $recorddoc['value'];
                }
            }
        }
        if (count($metaProperty_collection) >= 1) {
            foreach ($metaProperty_collection as $key => $collection_value) {
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
                    'property_id' => $collection_value['property_id']
                ];
            }
        }
		try{
			if (isset($collection_data_slug_val["sku"]["property_id"])) {
				$metaProperty_Collections = $collection_data_slug_val["sku"]["property_id"];

				/******************************         Below section for delete and update role     ******************************************* */
				$all_new_urls = [];
				$all_new_array_media_ids = [];
				$all_deleted_items = [];
				$all_deleted_new_items = [];
				if ($new_bynder_array != "") {
					$newBynderArray = json_decode($new_bynder_array, true);
					if (is_array($newBynderArray)) {
						if (count($newBynderArray) > 0) {
							foreach ($newBynderArray as $new_key => $new_val) {
								$all_new_urls[] = $new_val['item_url'];
								$all_new_array_media_ids[] = $new_val['bynder_md_id'];
							}
						}
					}
				}

				if ($old_bynder_array != "") {
					$oldBynderArray = json_decode($old_bynder_array, true);
					if (isset($oldBynderArray)) {
						foreach ($oldBynderArray as $old_key => $old_val) {
							$old_url_link = $old_val['item_url'];
							$old_media_id = $old_val['bynder_md_id'];
							$change_metapropeties_id = $remove_type = "";
							$change_roles = "";
							$deleted_sku_value = "";
							if (!in_array($old_url_link, $all_new_urls)) {
								/* need to delete either role or roles and sku */
								if (!in_array($old_media_id, $all_new_array_media_ids)) {
									$remove_type = "sku";
									$change_metapropeties_id = $collection_data_slug_val["sku"]["property_id"];
									$deleted_sku_value = $product_sku_key;
									$all_deleted_new_items[] = [
										"media_id" => $old_media_id,
										"remove_type" => $remove_type,
										"main_Properties_id" => $change_metapropeties_id,
										"deleted_sku_value" => $deleted_sku_value,
										"deleted_role_value" => $change_roles,
										"all_magento_roll_delete" => 1
									];
								}
							}
						}
					}
				}

				if (count($all_deleted_new_items) > 0) {
					$bynder_auth = [
						"bynderDomain" => $bdomain_chk_config,
						"token" => $this->datahelper->getPermanenToken(),
						"changes_details" => json_encode($all_deleted_new_items),
						"collection_data_value" => $collection_data_slug_val
					];
                    /** this code for delete from magento to bynder */
					/* $this->datahelper->removeSkuOrRoleDAM($bynder_auth);*/
				}

				/******************************         Above section for delete and update role     ******************************************* */

				/******************************Document Section******************************************************************************** */
				if (isset($document)) {
					$doc_json = json_decode($document, true);
					$old_doc_url = [];
					if (!empty($bynder_document)) {
						$old_doc = json_decode($bynder_document, true);

						if (!empty($old_doc)) {
							foreach ($old_doc as $d_old) {
								$old_doc_url[] = $d_old['item_url'];
							}

						}
					}
					/*********************************************When URL Already have in DataBase Then Update Data ********************************** */
					if (!empty($collection)) {
						$docs = [];
						if (!empty($doc_json)) {
							foreach ($doc_json as $doc_s) {
								$docs[] = $doc_s['item_url'];
							}
						}
						$old_doc_collection = [];
						foreach ($collection as $doc_col) {
							$old_doc_collection[] = $doc_col['bynder_data'];
							if ($doc_col['bynder_data_type'] == '2') {
								if (!in_array($doc_col['bynder_data'], $docs)) {
									$data = ["remove_for_magento" => "0"];
									$where = ['id = ?' => $doc_col['id']];
								} else {
									$data = ["remove_for_magento" => "1"];
									$where = ['id = ?' => $doc_col['id']];
								}
								$connection->update($tableName, $data, $where);
							}
						}
						/************When Delete Compactview Side then also Delete Sku Bynder Side ********************** */
						foreach ($delete_collection as $delete) {
							if (!empty($metaProperty_Collections)) {
								if ($delete['sku'] == $product_sku_key) {
									$this->datahelper->getDataRemoveForMagento(
										$product_sku_key,
										$delete['media_id'],
										$metaProperty_Collections
									);
								}
							}

						}
						/********************************************************************************************* */
					}
					/******************************************Insert Data from DataBase Side****************************** */
					if (!empty($doc_json)) {
						foreach ($doc_json as $doc) {
							if (!in_array($doc['item_url'], $old_doc_url)) {
								$media_doc_explode = explode("/", $doc['item_url']);
								/*********When add Compactview side then also sku add Bynder Side ******************* */
								if (!empty($metaProperty_Collections)) {
									$this->datahelper->getAddedCompactviewSkuFromBynder(
										$product_sku_key,
										$media_doc_explode[4],
										$metaProperty_Collections
									);
								} else {
									$this->messageManager->addError(
										'Bynder Item Not Save First Select The Metaproperty.....'
									);
									$this->cookieManager->deleteCookie('bynder_doc');
									$publicCookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata();
									$publicCookieMetadata->setDurationOneYear();
									$publicCookieMetadata->setPath('/');
									$publicCookieMetadata->setHttpOnly(false);

									$this->cookieManager->setPublicCookie(
										'bynder_doc',
										null,
										$publicCookieMetadata
									);
									return $this->resultRedirectFactory->setPath('*/*/');
								}

								if (!in_array($doc['item_url'], $old_doc_collection)) {
									$data_doc_value = [
										'sku' => $product_sku_key,
										'bynder_data' => $doc['item_url'],
										'bynder_data_type' => '2',
										'media_id' => $media_doc_explode[4],
										'remove_for_magento' => '1',
										'added_on_cron_compactview' => '2',
										'added_date' => time()
									];
									$model->setData($data_doc_value);
									$model->save();
								}
							}

						}
					}
					$this->productActionObject->updateAttributes([$productId], ['bynder_document' => $document], $storeId);
					$this->bynderTempDocData->create()->load($doc_coockie_id)->delete();
					$publicCookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata();
					$publicCookieMetadata->setDurationOneYear();
					$publicCookieMetadata->setPath('/');
					$publicCookieMetadata->setHttpOnly(false);

					$this->cookieManager->setPublicCookie(
						'doc_coockie_id',
						0,
						$publicCookieMetadata
					);
				}
				/******************************************************************************************************************** */
				/***************************Video and Image Section ***************************************************************** */
				$video = "";
				$flag = 0;
            
                if (isset($image)) {
                    $image_json = json_decode($image, true);
                    $old_url = [];
                    if (!empty($bynder_multi_img)) {
                        $old_img = json_decode($bynder_multi_img, true);

                        if (!empty($old_img)) {
                            foreach ($old_img as $old) {
                                $old_url[] = $old['item_url'];
                            }

                        }
                    }
                    /*********************************************When URL Already have in DataBase Then Update Data ********************************** */
                    if (!empty($collection)) {
                        $imgse = [];
                        if (!empty($image_json)) {
                            foreach ($image_json as $imgs) {
                                $imgse[] = $imgs['item_url'];
                            }
                        }
                        $old_collection = [];
                        $sku = [];
                        foreach ($collection as $col) {
                            $old_collection[] = $col['bynder_data'];
                            $sku[] = $col['sku'];
                            if ($col['bynder_data_type'] != '2') {
                                if (!in_array($col['bynder_data'], $imgse)) {
                                    $data = ["remove_for_magento" => "0"];
                                    $where = ['id = ?' => $col['id']];
                                } else {
                                    $data = ["remove_for_magento" => "1"];
                                    $where = ['id = ?' => $col['id']];
                                }
                                $connection->update($tableName, $data, $where);
                            }
                        }
                        /************When Delete Compactview Side then also Delete Sku Bynder Side ********************** */
                        foreach ($delete_collection as $delete) {
                            if (!empty($metaProperty_Collections)) {
                                if ($delete['sku'] == $product_sku_key) {
                                    $this->datahelper->getDataRemoveForMagento(
                                        $product_sku_key,
                                        $delete['media_id'],
                                        $metaProperty_Collections
                                    );
                                }
                            }
                        }
                        /********************************************************************************************* */
                    }

                    $type = [];
                    /******************************************Insert Data from DataBase Side****************************** */
                    if (!empty($image_json)) {
                        foreach ($image_json as $img) {
                            if (!in_array($img['item_url'], $old_url)) {
                                $media_image_explode = explode("/", $img['item_url']);
                                /*********When add Compactview side then also sku add Bynder Side ******************* */
                                if (!empty($metaProperty_Collections)) {
                                    $this->datahelper->getAddedCompactviewSkuFromBynder(
                                        $product_sku_key,
                                        $media_image_explode[5],
                                        $metaProperty_Collections
                                    );
                                } else {
                                    $this->messageManager->addError(
                                        'Bynder Item Not Save First Select The Metaproperty.....'
                                    );
                                    $this->cookieManager->deleteCookie('bynder_image');
                                    $publicCookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata();
                                    $publicCookieMetadata->setDurationOneYear();
                                    $publicCookieMetadata->setPath('/');
                                    $publicCookieMetadata->setHttpOnly(false);

                                    $this->cookieManager->setPublicCookie(
                                        'bynder_image',
                                        null,
                                        $publicCookieMetadata
                                    );
                                    return $this->resultRedirectFactory->setPath('*/*/');
                                }
                                if (!in_array($img['item_url'], $old_collection)) {
                                    $data_image_data = [
                                        'sku' => $product_sku_key,
                                        'bynder_data' => $img['item_url'],
                                        'bynder_data_type' => ($img['item_type'] == "IMAGE") ? '1' : '3',
                                        'media_id' => $media_image_explode[5],
                                        'remove_for_magento' => '1',
                                        'added_on_cron_compactview' => '2',
                                        'added_date' => time()
                                    ];
                                    $model->setData($data_image_data);
                                    $model->save();
                                }

                            }
                            $type[] = $img['item_type'];
                        }
                        /*  IMAGE & VIDEO == 1
                        IMAGE == 2
                        VIDEO == 3 */
                        if (in_array("IMAGE", $type) && in_array("VIDEO", $type)) {
                            $flag = 1;
                        } elseif (in_array("IMAGE", $type)) {
                            $flag = 2;
                        } elseif (in_array("VIDEO", $type)) {
                            $flag = 3;
                        }
                    }

                    /* sync alt text and image role to Bynder */
                    $m_id = [];
                    if (!empty($image)) {
                        $new_changed_bynder_img_attribute = json_decode($image, true);
                        if (!empty($all_meta_properties)) {
                            $this->datahelper->getUpdateBynderImageRoleAndAltText(
                                $product_sku_key,
                                $all_meta_properties,
                                $image
                            );
                        }
                        foreach ($new_changed_bynder_img_attribute as $img) {
                            $m_id[] = $img['bynder_md_id'];
                            $this->getDeleteMedaiDataTable($product_sku_key, $img['bynder_md_id']);
                        }
                        $this->getInsertMedaiDataTable($product_sku_key, $m_id);
                        $this->productActionObject->updateAttributes(
                            [$productId],
                            ['bynder_isMain' => $flag],
                            $storeId
                        );
                        $this->productActionObject->updateAttributes(
                            [$productId],
                            ['bynder_multi_img' => $image],
                            $storeId
                        );
                        if ($product->getBynderVideos()) {
                            $this->productActionObject->updateAttributes(
                                [$productId],
                                ['bynder_videos' => $video],
                                $storeId
                            );
                        }
                        $this->bynderTempData->create()->load($image_coockie_id)->delete();
                        $publicCookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata();
                        $publicCookieMetadata->setDurationOneYear();
                        $publicCookieMetadata->setPath('/');
                        $publicCookieMetadata->setHttpOnly(false);
                        $this->cookieManager->setPublicCookie(
                            'image_coockie_id',
                            0,
                            $publicCookieMetadata
                        );
                    }
                } else {
                    $this->productActionObject->updateAttributes([$productId], ['bynder_isMain' => ""], $storeId);
                    $this->productActionObject->updateAttributes(
                        [$productId],
                        ['bynder_multi_img' => $image],
                        $storeId
                    );
                    $this->productActionObject->updateAttributes([$productId], ['bynder_cron_sync' => ""], $storeId);
                    $this->productActionObject->updateAttributes([$productId], ['bynder_auto_replace' => ""], $storeId);
                    $this->bynderTempData->create()->load($image_coockie_id)->delete();
                    $publicCookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata();
                    $publicCookieMetadata->setDurationOneYear();
                    $publicCookieMetadata->setPath('/');
                    $publicCookieMetadata->setHttpOnly(false);
                    $this->cookieManager->setPublicCookie(
                        'image_coockie_id',
                        0,
                        $publicCookieMetadata
                    );
                }
			}
		} catch (\Exception $e) {
			$this->productActionObject->updateAttributes([$productId], ['bynder_isMain' => ""], $storeId);
			$this->productActionObject->updateAttributes([$productId], ['bynder_multi_img' => $image], $storeId);
			$this->productActionObject->updateAttributes([$productId], ['bynder_cron_sync' => ""], $storeId);
			$this->productActionObject->updateAttributes([$productId], ['bynder_auto_replace' => ""], $storeId);
			$this->bynderTempData->create()->load($image_coockie_id)->delete();
			$publicCookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata();
			$publicCookieMetadata->setDurationOneYear();
			$publicCookieMetadata->setPath('/');
			$publicCookieMetadata->setHttpOnly(false);
			$this->cookieManager->setPublicCookie(
				'image_coockie_id',
				0,
				$publicCookieMetadata
			);
		}
    }
    /**
     * Is Json
     *
     * @param string $sku
     * @param string $m_id
     * @return $this
     */
    public function getInsertMedaiDataTable($sku, $m_id)
    {
        $model = $this->bynderMediaTable->create();
        $modelcollection = $this->bynderMediaTableCollectionFactory->create()
        ->addFieldToFilter('sku', ['eq' => [$sku]])->load();
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
        $model = $this->bynderMediaTableCollectionFactory->create()
        ->addFieldToFilter('sku', ['eq' => [$sku]])->load();
        foreach ($model as $mdata) {
            if ($mdata['media_id'] != $media_id) {
                $this->bynderMediaTable->create()->load($mdata['id'])->delete();

            }
        }
    }
}
