<?php
namespace DamConsultants\Akima\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;
use DamConsultants\Akima\Model\ResourceModel\Collection\BynderAutoReplaceDataCollectionFactory;
use Magento\Framework\AuthorizationInterface;

class MassDeleteCronAutoData extends Action
{
    /**
     * @var collectionFactory.
     *
     */
    public $collectionFactory;
    /**
     * @var filter.
     *
     */
    public $filter;
    /**
     * @var bynderFactory.
     *
     */
    protected $bynderFactory;
	protected $authorization;
    /**
     * Get Sku.
     * @param Context $context
     * @param Filter $filter
     * @param BynderAutoReplaceDataCollectionFactory $collectionFactory
     * @param \DamConsultants\Akima\Model\BynderAutoReplaceDataFactory $bynderFactory
     */
    public function __construct(
        Context $context,
        Filter $filter,
        BynderAutoReplaceDataCollectionFactory $collectionFactory,
		AuthorizationInterface $authorization,
        \DamConsultants\Akima\Model\BynderAutoReplaceDataFactory $bynderFactory
    ) {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->bynderFactory = $bynderFactory;
		$this->authorization = $authorization;
        parent::__construct($context);
    }
	/**
     * Execute
     *
     * @return $this
     */
    public function _isAllowed()
    {
        return $this->authorization->isAllowed('DamConsultants_Akima::autocron_massdelete');
    }
    /**
     * Execute
     *
     * @return $this
     */
    public function execute()
    {
        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());

            $count = 0;
            foreach ($collection as $model) {
                $model = $this->bynderFactory->create()->load($model->getId());
                $model->delete();
                $count++;
            }
            $this->messageManager->addSuccess(__('A total of %1 data(s) have been deleted.', $count));
        } catch (\Exception $e) {
            $this->messageManager->addError(__($e->getMessage()));
        }
        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('bynder/index/replacecrongrid');
    }
}
