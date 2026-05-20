<?php

namespace DamConsultants\Akima\Model;

class MetaProperty extends \Magento\Framework\Model\AbstractModel
{
    protected const CACHE_TAG = 'DamConsultants_Akima';

    /**
     * @var $_cacheTag
     */
    protected $_cacheTag = 'DamConsultants_Akima';

    /**
     * @var $_eventPrefix
     */
    protected $_eventPrefix = 'DamConsultants_Akima';

    /**
     * Meta Property
     *
     * @return $this
     */
    protected function _construct()
    {
        $this->_init(\DamConsultants\Akima\Model\ResourceModel\MetaProperty::class);
    }
}
