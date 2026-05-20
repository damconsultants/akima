<?php

namespace DamConsultants\Akima\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var string
     */
    public $by_redirecturl;

    /**
     * @var string
     */
    public $bynderDomain = "";

    /**
     * @var string
     */
    public $permanent_token = "";

    /**
     * @var \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory
     */
    protected $cookieMetadataFactory;

    /**
     * @var \Magento\Framework\Stdlib\CookieManagerInterface
     */
    protected $cookieManager;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productrepository;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    protected $_curl;

    /**
     * @var \Magento\ConfigurableProduct\Block\Adminhtml\Product\Steps\Bulk
     */
    protected $_bulk;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    public const BYNDER_DOMAIN      = 'bynderconfig/bynder_credential/bynderdomain';
    public const PERMANENT_TOKEN    = 'bynderconfig/bynder_credential/permanent_token';
    public const LICENCE_TOKEN      = 'bynderconfig/bynder_credential/licenses_key';
    public const RADIO_BUTTON       = 'byndeimageconfig/bynder_image/selectimage';
    public const PRODUCT_SKU_LIMIT  = 'cronimageconfig/set_limit_product_sku/product_sku_limt';
    public const FETCH_CRON         = 'cronimageconfig/configurable_cron/fetch_enable';
    public const AUTO_CRON          = 'cronimageconfig/auto_add_bynder/auto_enable';
    public const API_CALLED         = 'https://developer.thedamconsultants.com/';
    public const DELETE_CRON        = 'cronimageconfig/delete_cron_bynder/delete_enable';

    /**
     * Data Helper constructor.
     *
     * @param \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory
     * @param \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productrepository
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\ConfigurableProduct\Block\Adminhtml\Product\Steps\Bulk $bulk
     */
    public function __construct(
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Catalog\Api\ProductRepositoryInterface $productrepository,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Registry $registry,
        \Magento\ConfigurableProduct\Block\Adminhtml\Product\Steps\Bulk $bulk
    ) {
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->cookieManager         = $cookieManager;
        $this->productrepository     = $productrepository;
        $this->filesystem            = $filesystem;
        $this->_scopeConfig          = $context->getScopeConfig();
        $this->_storeManager         = $storeManager;
        $this->_curl                 = $curl;
        $this->_bulk                 = $bulk;
        $this->_registry             = $registry;
        parent::__construct($context);
    }

    // -------------------------------------------------------------------------
    // Internal helper
    // -------------------------------------------------------------------------

    /**
     * Execute a JSON POST request against the DAM Consultants API and return the body.
     *
     * Centralises the repetitive cURL setup that was previously copy-pasted into
     * every public API method.
     *
     * @param string $endpoint  Path segment after self::API_CALLED
     * @param array  $fields    Associative array of POST fields
     * @return string           Raw response body
     */
    private function makeApiPost(string $endpoint, array $fields): string
    {
        $url     = self::API_CALLED . $endpoint;
        $payload = json_encode($fields);

        $this->_curl->setOption(CURLOPT_URL, $url);
        $this->_curl->setOption(CURLOPT_RETURNTRANSFER, true);
        $this->_curl->setOption(CURLOPT_TIMEOUT, 0);
        $this->_curl->setOption(CURLOPT_ENCODING, '');
        $this->_curl->setOption(CURLOPT_MAXREDIRS, 10);
        $this->_curl->setOption(CURLOPT_FOLLOWLOCATION, true);
        $this->_curl->setOption(CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        $this->_curl->setOption(CURLOPT_POSTFIELDS, $payload);
        $this->_curl->addHeader('Content-Type', 'application/json');
        $this->_curl->post($url, $payload);

        return (string) $this->_curl->getBody();
    }

    // -------------------------------------------------------------------------
    // Credential / config helpers
    // -------------------------------------------------------------------------

    /**
     * Return the media attributes for bulk configurable product steps.
     *
     * @return mixed
     */
    public function getBulkImageRoll()
    {
        return $this->_bulk->getMediaAttributes();
    }

    /**
     * Return the registered product object for the given registry key.
     *
     * @param string $currentProduct
     * @return mixed
     */
    public function getProduct($currentProduct)
    {
        return $this->_registry->registry($currentProduct);
    }

    /**
     * Load and return a product by its ID via the product repository.
     *
     * @param int $productId
     * @return \Magento\Catalog\Api\Data\ProductInterface
     */
    public function getProductById($productId)
    {
        return $this->productrepository->getById($productId);
    }

    /**
     * Return whether the Bynder fetch cron is enabled.
     *
     * @return mixed
     */
    public function getFetchCronEnable()
    {
        return $this->getConfig(self::FETCH_CRON);
    }

    /**
     * Return the store configuration value at the given path.
     *
     * @param string $path
     * @return string
     */
    public function getDeleteCron($path)
    {
        return (string) $this->getStoreConfig($path);
    }

    /**
     * Return whether the Bynder delete cron is enabled.
     *
     * @return mixed
     */
    public function getDeleteCronEnable()
    {
        return $this->getConfig(self::DELETE_CRON);
    }

    /**
     * Return whether the Bynder auto-add cron is enabled.
     *
     * @return mixed
     */
    public function getAutoCronEnable()
    {
        return $this->getConfig(self::AUTO_CRON);
    }

    /**
     * Return a scoped store configuration value.
     *
     * @param string   $storePath
     * @param int|null $storeId
     * @return mixed
     */
    public function getStoreConfig($storePath, $storeId = null)
    {
        return $this->_scopeConfig->getValue($storePath, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Return the configured Bynder domain.
     *
     * @return string
     */
    public function getBynderDomain()
    {
        return (string) $this->getStoreConfig(self::BYNDER_DOMAIN);
    }

    /**
     * Return the configured permanent token.
     *
     * @return string
     */
    public function getPermanentToken()
    {
        return (string) $this->getStoreConfig(self::PERMANENT_TOKEN);
    }

    /**
     * Return the configured licence token.
     *
     * @return string
     */
    public function getLicenceToken()
    {
        return (string) $this->getStoreConfig(self::LICENCE_TOKEN);
    }

    /**
     * Return the configured Bynder image selection radio value.
     *
     * @return string
     */
    public function byndeimageconfig()
    {
        return (string) $this->getStoreConfig(self::RADIO_BUTTON);
    }

    /**
     * Return the configured product SKU cron limit.
     *
     * @return string
     */
    public function getProductSkuLimitConfig()
    {
        return (string) $this->getStoreConfig(self::PRODUCT_SKU_LIMIT);
    }

    /**
     * Return the Bynder domain via scope config (alias used by cron/observer classes).
     *
     * @return string
     */
    public function getBynderDom()
    {
        return (string) $this->getConfig(self::BYNDER_DOMAIN);
    }

    /**
     * Return the permanent token via scope config (alias used by cron/observer classes).
     *
     * @return string
     */
    public function getPermanenToken()
    {
        return (string) $this->getConfig(self::PERMANENT_TOKEN);
    }

    /**
     * Load and validate the Bynder credentials into public properties.
     *
     * @return int|string  1 on success; error message string on failure
     */
    public function getLoadCredential()
    {
        $this->bynderDomain    = $this->getBynderDom();
        $this->permanent_token = $this->getPermanenToken();
        $this->by_redirecturl  = $this->getRedirecturl();

        if (!empty($this->bynderDomain) && !empty($this->permanent_token) && !empty($this->by_redirecturl)) {
            return 1;
        }

        return "Bynder authentication failed | Please check your credential";
    }

    /**
     * Return the Bynder redirect URL constructed from the store base URL.
     *
     * @return string
     */
    public function getRedirecturl()
    {
        return (string) $this->getbaseurl() . "bynder/redirecturl";
    }

    /**
     * Return the current store base URL.
     *
     * @return string
     */
    public function getbaseurl()
    {
        return $this->_storeManager->getStore()->getBaseUrl();
    }

    /**
     * Return a store-scoped config value by path.
     *
     * @param string $path
     * @return mixed
     */
    public function getConfig($path)
    {
        return $this->_scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    // -------------------------------------------------------------------------
    // API call methods — all delegate to makeApiPost()
    // -------------------------------------------------------------------------

    /**
     * Check the Bynder licence status against the DAM Consultants API.
     *
     * @return string Raw API response body
     */
    public function getCheckBynder()
    {
        return $this->makeApiPost('macfarlane-check-bynder-license', [
            'base_url'      => $this->getbaseurl(),
            'licence_token' => $this->getLicenceToken()
        ]);
    }

    /**
     * Fetch derivatives / thumbnails for the given Bynder media IDs.
     *
     * @param array $bynder_auth
     * @return string Raw API response body
     */
    public function getDerivativesImage($bynder_auth)
    {
        return $this->makeApiPost('macfarlane-magento-derivatives', [
            'bynder_domain'                => $bynder_auth['bynderDomain'],
            'redirectUri'                  => $bynder_auth['redirectUri'],
            'permanent_token'              => $bynder_auth['token'],
            'databaseId'                   => $bynder_auth['og_media_ids'],
            'daatasetType'                 => $bynder_auth['dataset_types'],
            'base_url'                     => $this->getbaseurl(),
            'licence_token'                => $this->getLicenceToken(),
            'bynder_metaproperty_collection' => $bynder_auth['collection_data_value']
        ]);
    }

    /**
     * Retrieve the licence key for the current store's domain.
     *
     * @return string Raw API response body
     */
    public function getLicenceKey()
    {
        return $this->makeApiPost('macfarlane-get-license-key', [
            'domain_name' => $this->getbaseurl()
        ]);
    }

    /**
     * Notify the DAM Consultants API of changed image metadata for a product URL.
     *
     * @param string $product_url
     * @param string $url_data
     * @return string Raw API response body
     */
    public function getBynderChangemetadataAssets($product_url, $url_data)
    {
        return $this->makeApiPost('macfarlane-change-metadata-magento', [
            'domain_name'      => $this->getbaseurl(),
            'bynder_domain'    => $this->getBynderDom(),
            'permanent_token'  => $this->getPermanenToken(),
            'licence_token'    => $this->getLicenceToken(),
            'product_url'      => $product_url,
            'bynder_multi_img' => $url_data
        ]);
    }

    /**
     * Notify the DAM Consultants API of changed document metadata for a product URL.
     *
     * @param string $product_url
     * @param string $url_data
     * @return string Raw API response body
     */
    public function getBynderChangemetadataAssetsDoc($product_url, $url_data)
    {
        return $this->makeApiPost('macfarlane-change-metadata-magento-doc', [
            'domain_name'      => $this->getbaseurl(),
            'bynder_domain'    => $this->getBynderDom(),
            'permanent_token'  => $this->getPermanenToken(),
            'licence_token'    => $this->getLicenceToken(),
            'product_url'      => $product_url,
            'bynder_multi_img' => $url_data
        ]);
    }

    /**
     * Notify the DAM Consultants API of changed video metadata for a product URL.
     *
     * @param string $product_url
     * @param string $url_data
     * @return string Raw API response body
     */
    public function getBynderChangemetadataAssetsVideo($product_url, $url_data)
    {
        return $this->makeApiPost('macfarlane-change-metadata-magento-video', [
            'domain_name'      => $this->getbaseurl(),
            'bynder_domain'    => $this->getBynderDom(),
            'permanent_token'  => $this->getPermanenToken(),
            'licence_token'    => $this->getLicenceToken(),
            'product_url'      => $product_url,
            'bynder_multi_img' => $url_data
        ]);
    }

    /**
     * Notify the DAM Consultants API of changed metadata for a CMS page URL.
     *
     * @param string $CMSPageURL
     * @param string $url_data
     * @return string Raw API response body
     */
    public function getBynderDataCmsPage($CMSPageURL, $url_data)
    {
        return $this->makeApiPost('macfarlane-change-metadata-magento-cms-page', [
            'domain_name'      => $this->getbaseurl(),
            'bynder_domain'    => $this->getBynderDom(),
            'permanent_token'  => $this->getPermanenToken(),
            'licence_token'    => $this->getLicenceToken(),
            'cmspage_url'      => $CMSPageURL,
            'bynder_multi_img' => $url_data
        ]);
    }

    /**
     * Fetch the list of Bynder meta-properties available for the configured domain.
     *
     * @return string Raw API response body
     */
    public function getBynderMetaProperites()
    {
        return $this->makeApiPost('macfarlane-get-bynder-meta-properites', [
            'domain_name'     => $this->getbaseurl(),
            'bynder_domain'   => $this->getBynderDom(),
            'permanent_token' => $this->getPermanenToken(),
            'licence_token'   => $this->getLicenceToken()
        ]);
    }

    /**
     * Fetch Bynder image/media sync data for a given SKU.
     *
     * @param string $sku_id
     * @param string|null $property_id
     * @param array  $collection_data_value
     * @return string Raw API response body
     */
    public function getImageSyncWithProperties($sku_id, $property_id, $collection_data_value)
    {
        return $this->makeApiPost('macfarlane-bynder-skudetails-new', [
            'domain_name'                    => $this->getbaseurl(),
            'bynder_domain'                  => $this->getBynderDom(),
            'permanent_token'                => $this->getPermanenToken(),
            'licence_token'                  => $this->getLicenceToken(),
            'sku_id'                         => $sku_id,
            'property_id'                    => $property_id,
            'bynder_metaproperty_collection' => $collection_data_value
        ]);
    }

    /**
     * Request removal of a Bynder asset from the Magento data set.
     *
     * @param string $sku_id
     * @param string $media_Id
     * @param string $metaProperty_id
     * @return string Raw API response body
     */
    public function getDataRemoveForMagento($sku_id, $media_Id, $metaProperty_id)
    {
        return $this->makeApiPost('macfarlane-sku-data-remove-for-magento', [
            'domain_name'     => $this->getbaseurl(),
            'bynder_domain'   => $this->getBynderDom(),
            'permanent_token' => $this->getPermanenToken(),
            'licence_token'   => $this->getLicenceToken(),
            'sku_id'          => $sku_id,
            'media_id'        => $media_Id,
            'property_id'     => $metaProperty_id
        ]);
    }

    /**
     * Fetch compact-view SKU additions from Bynder.
     *
     * @param string $sku_id
     * @param string $media_Id
     * @param string $metaProperty_id
     * @return string Raw API response body
     */
    public function getAddedCompactviewSkuFromBynder($sku_id, $media_Id, $metaProperty_id)
    {
        return $this->makeApiPost('macfarlane-added-compactview-sku-from-bynder', [
            'domain_name'     => $this->getbaseurl(),
            'bynder_domain'   => $this->getBynderDom(),
            'permanent_token' => $this->getPermanenToken(),
            'licence_token'   => $this->getLicenceToken(),
            'sku_id'          => $sku_id,
            'media_id'        => $media_Id,
            'property_id'     => $metaProperty_id
        ]);
    }

    /**
     * Push updated image role and alt-text data to Bynder.
     *
     * @param string $product_sku_key
     * @param array  $metaProperty_Collections
     * @param array  $image
     * @return string Raw API response body
     */
    public function getUpdateBynderImageRoleAndAltText($product_sku_key, $metaProperty_Collections, $image)
    {
        return $this->makeApiPost('macfarlane-update-bynderImageRole-and-altText', [
            'domain_name'            => $this->getbaseurl(),
            'bynder_domain'          => $this->getBynderDom(),
            'permanent_token'        => $this->getPermanenToken(),
            'licence_token'          => $this->getLicenceToken(),
            'sku_id'                 => $product_sku_key,
            'metaProperty_Collections' => $metaProperty_Collections,
            'bynder_changes_details' => $image
        ]);
    }

    /**
     * Sync the full asset details for a product to the DAM.
     *
     * @param array $bynder_auth
     * @return string Raw API response body
     */
    public function changeBynderAssetsDetails($bynder_auth)
    {
        return $this->makeApiPost('macfarlane-sync-assets-details', [
            'domain_name'                    => $this->getbaseurl(),
            'bynder_domain'                  => $bynder_auth['bynderDomain'],
            'permanent_token'                => $bynder_auth['token'],
            'new_value_obj'                  => $bynder_auth['new_value_obj'],
            'base_url'                       => $this->getbaseurl(),
            'licence_token'                  => $this->getLicenceToken(),
            'bynder_metaproperty_collection' => $bynder_auth['collection_data_value']
        ]);
    }

    /**
     * Sync asset details via the popup (compact view) endpoint.
     *
     * @param array $bynder_auth
     * @return string Raw API response body
     */
    public function changePopupBynderAssetsDetails($bynder_auth)
    {
        $baseUrl = $this->getbaseurl();
        return $this->makeApiPost('sync-macfarlane-popup-assets-details', [
            'domain_name'                    => $baseUrl,
            'bynder_domain'                  => $bynder_auth['bynderDomain'],
            'permanent_token'                => $bynder_auth['token'],
            'new_value_obj'                  => $bynder_auth['new_value_obj'],
            'base_url'                       => $baseUrl,
            'licence_token'                  => $this->getLicenceToken(),
            'bynder_metaproperty_collection' => $bynder_auth['collection_data_value']
        ]);
    }

    /**
     * Remove a SKU or role association in the DAM.
     *
     * @param array $bynder_auth
     * @return string Raw API response body
     */
    public function removeSkuOrRoleDAM($bynder_auth)
    {
        $baseUrl = $this->getbaseurl();
        return $this->makeApiPost('macfarlane-remove-sku-role-from-dam', [
            'domain_name'                    => $baseUrl,
            'bynder_domain'                  => $bynder_auth['bynderDomain'],
            'permanent_token'                => $bynder_auth['token'],
            'new_value_obj'                  => $bynder_auth['changes_details'],
            'base_url'                       => $baseUrl,
            'licence_token'                  => $this->getLicenceToken(),
            'bynder_metaproperty_collection' => $bynder_auth['collection_data_value']
        ]);
    }

    /**
     * Fetch assets deleted from the DAM since the last cron run.
     *
     * @param array $bynder_auth  Must contain key 'last_cron_time'
     * @return string Raw API response body
     */
    public function getCheckBynderSideDeleteData($bynder_auth)
    {
        $baseUrl = $this->getbaseurl();
        return $this->makeApiPost('macfarlane-remove-assets-deleted-data-from-dam', [
            'domain_name'     => $baseUrl,
            'bynder_domain'   => $this->getBynderDom(),
            'permanent_token' => $this->getPermanenToken(),
            'licence_token'   => $this->getLicenceToken(),
            'base_url'        => $baseUrl,
            'last_cron_time'  => $bynder_auth['last_cron_time']
        ]);
    }

    /**
     * Sanitize a raw product SKU for use as a Bynder search term.
     *
     * Converts UTF-8 to ISO-8859-1, then replaces every character
     * that is not alphanumeric or a hyphen with an underscore.
     *
     * @param string $og_sku
     * @return string
     */
    public function replacetoSpecialString($og_sku)
    {
        $utf_og_sku = iconv('UTF-8', 'ISO-8859-1', $og_sku);
        $new_string = preg_replace('/[^a-zA-Z0-9-]/', '_', $utf_og_sku);
        return $new_string;
    }
}
