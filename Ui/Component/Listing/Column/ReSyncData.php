<?php
namespace DamConsultants\Akima\Ui\Component\Listing\Column;

use Exception;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use DamConsultants\Akima\Model\ResourceModel\Collection\BynderSycDataCollectionFactory;
use Magento\Framework\AuthorizationInterface;

class ReSyncData extends \Magento\Ui\Component\Listing\Columns\Column
{
    /**
     * @var urlBuilder
     */
    public $urlBuilder;
    /**
     * @var _resource
     */
    public $_resource;
    /**
     * @var bynderSycDataFactory
     */
    public $bynderSycDataFactory;
    /**
     * @var _productRepository
     */
    protected $_productRepository;
    /**
     * @var bynderSycDataCollectionFactory
     */
    protected $bynderSycDataCollectionFactory;
	protected $authorization;
    /**
     * Closed constructor.
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     * @param \DamConsultants\Akima\Model\BynderSycDataFactory $BynderSycDataFactory
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param UrlInterface $urlBuilder
     * @param BynderSycDataCollectionFactory $bynderSycDataCollectionFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \DamConsultants\Akima\Model\BynderSycDataFactory $BynderSycDataFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        UrlInterface $urlBuilder,
		AuthorizationInterface $authorization,
        BynderSycDataCollectionFactory $bynderSycDataCollectionFactory,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->_resource = $resource;
        $this->bynderSycDataFactory = $BynderSycDataFactory;
        $this->_productRepository = $productRepository;
		$this->authorization = $authorization;
		$this->bynderSycDataCollectionFactory = $bynderSycDataCollectionFactory;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }
    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $sku = $item["sku"];
                try {
                    $_product = $this->_productRepository->get($sku);
                    $product_bynder_cron_val = $_product->getBynderCronSync();
                    if (isset($item['id']) && $this->authorization->isAllowed('DamConsultants_Akima::resync')) {
                        $viewUrlPath = $this->getData('config/viewUrlPath');
                        $urlEntityParamName = $this->getData('config/urlEntityParamName');
                        if ($item['media_id'] == null && $product_bynder_cron_val != null) {
                            $item[$this->getData('name')] = [
                                'view' => [
                                    'href' => $this->urlBuilder->getUrl(
                                        $viewUrlPath,
                                        [
                                            $urlEntityParamName => $item['id'],
                                        ]
                                    ),
                                    'label' => __('Re-Sync'),
                                    'class' => 'action-primary',
                                ],
                            ];
                        }
                    }
                } catch (Exception $e) {
                    $collection = $this->bynderSycDataCollectionFactory->create()
                    ->addFieldToFilter('sku', ['eq' => $sku])->load();
                    foreach ($collection as $itemToDelete) {
                        $this->bynderSycDataFactory->create()->load($itemToDelete->getId())->delete();
                    }
                }
            }
        }
        return $dataSource;
    }
}
