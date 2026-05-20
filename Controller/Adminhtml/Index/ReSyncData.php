<?php
namespace DamConsultants\Akima\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

class ReSyncData extends Action
{
    /**
     * @var $BynderConfigSyncDataFactory
     */
    public $bynderSycDataFactory;
    /**
     * @var $_productRepository
     */
    protected $_productRepository;
    /**
     * @var $action
     */
    protected $action;
    /**
     * @var $storeManagerInterface
     */
    protected $storeManagerInterface;
    /**
     * Closed constructor.
     *
     * @param Context $context
     * @param \DamConsultants\Akima\Model\BynderSycDataFactory $BynderSycDataFactory
     * @param \Magento\Catalog\Model\Product\Action $action
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     */
    public function __construct(
        Context $context,
        \DamConsultants\Akima\Model\BynderSycDataFactory $BynderSycDataFactory,
        \Magento\Catalog\Model\Product\Action $action,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
    ) {
        $this->bynderSycDataFactory = $BynderSycDataFactory;
        $this->_productRepository = $productRepository;
        $this->action = $action;
        $this->storeManagerInterface = $storeManagerInterface;
        parent::__construct($context);
    }
    /**
     * Execute
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = $this->getRequest()->getParam('id');
        $storeId = $this->storeManagerInterface->getStore()->getId();
        try {
            $syncModel = $this->bynderSycDataFactory->create();
            $syncModel->load($id);
            $sku = $syncModel->getSku();
            $updated_values = [
                'bynder_cron_sync' => null
            ];
            $_product = $this->_productRepository->get($sku);
            $product_ids = $_product->getId();

            $this->action->updateAttributes(
                [$product_ids],
                $updated_values,
                $storeId
            );
            $syncModel->setLable('2');
            $syncModel->save();
            $this->messageManager->addSuccessMessage(__('SKU ('. $sku.') will re-sync again.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
        return $resultRedirect->setPath('bynder/index/grid');
    }
    /**
     * Is Allowed
     */
    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('DamConsultants_Akima::resync');
    }
}
