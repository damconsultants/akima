<?php
namespace DamConsultants\Akima\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

class DeleteSyncData extends Action
{
    /**
     * @var $BynderConfigSyncDataFactory
     */
    public $bynderConfigSyncDataFactory;
    /**
     * Closed constructor.
     *
     * @param Context $context
     * @param DamConsultants\Akima\Model\BynderConfigSyncDataFactory $BynderConfigSyncDataFactory
     */
    public function __construct(
        Context $context,
        \DamConsultants\Akima\Model\BynderConfigSyncDataFactory $BynderConfigSyncDataFactory
    ) {
        $this->bynderConfigSyncDataFactory = $BynderConfigSyncDataFactory;
        parent::__construct($context);
    }
    /**
     * Execute
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = $this->getRequest()->getParam('id');
        try {
            $syncModel = $this->bynderConfigSyncDataFactory->create();
            $syncModel->load($id);
            $syncModel->delete();
            $this->messageManager->addSuccessMessage(__('You deleted the sync data.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
        return $resultRedirect->setPath('bynder/index/sync');
    }
    /**
     * Is Allowed
     */
    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('DamConsultants_Akima::delete');
    }
}
