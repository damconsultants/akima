<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace DamConsultants\Akima\Block\Adminhtml\Product\Helper\Form\Gallery;

use Magento\Backend\Block\DataProviders\ImageUploadConfig as ImageUploadConfigDataProvider;
use Magento\MediaStorage\Helper\File\Storage\Database;
use DamConsultants\Akima\Helper\Data;

class Content extends \Magento\Catalog\Block\Adminhtml\Product\Helper\Form\Gallery\Content
{
    /**
     * @var string
     */
    protected $_template = 'Magento_Catalog::catalog/product/helper/gallery.phtml';

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var Data
     */
    protected $b_datahelper;

    /**
     * Constructor
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Catalog\Model\Product\Media\Config $mediaConfig
     * @param Data $bynderData
     * @param \Magento\Framework\App\RequestInterface $httpRequest
     * @param ImageUploadConfigDataProvider|null $imageUploadConfigDataProvider
     * @param Database|null $fileStorageDatabase
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Catalog\Model\Product\Media\Config $mediaConfig,
        Data $bynderData,
        \Magento\Framework\App\RequestInterface $httpRequest,
        ?ImageUploadConfigDataProvider $imageUploadConfigDataProvider = null,
        ?Database $fileStorageDatabase = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $jsonEncoder,
            $mediaConfig,
            $data,
            $imageUploadConfigDataProvider,
            $fileStorageDatabase
        );

        $this->request = $httpRequest;
        $this->b_datahelper = $bynderData;
    }

    /**
     * Check Bynder
     *
     * @return array
     */
    public function getcheckbynder()
    {
        $checkBynder = $this->b_datahelper->getCheckBynder();

        return json_decode($checkBynder, true);
    }

    /**
     * Get HTTP host
     *
     * @return string|null
     */
    public function getHttpData()
    {
        return $this->request->getServer('HTTP_HOST');
    }

    /**
     * Get entity ID
     *
     * @return mixed
     */
    public function getEntityId()
    {
        return $this->getRequest()->getParam('id');
    }
}