<?php

namespace DamConsultants\Akima\Model\ResourceModel\Collection;

class BynderTempDataCollection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    
    /**
     * BynderConfigSyncDataCollection
     *
     * @return $this
     */
    protected function _construct()
    {
        $this->_init(
            \DamConsultants\Akima\Model\BynderTempData::class,
            \DamConsultants\Akima\Model\ResourceModel\BynderTempData::class
        );
    }
}
