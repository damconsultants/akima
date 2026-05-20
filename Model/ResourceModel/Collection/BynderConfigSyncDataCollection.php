<?php

namespace DamConsultants\Akima\Model\ResourceModel\Collection;

class BynderConfigSyncDataCollection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    
    /**
     * BynderConfigSyncDataCollection
     *
     * @return $this
     */
    protected function _construct()
    {
        $this->_init(
            \DamConsultants\Akima\Model\BynderConfigSyncData::class,
            \DamConsultants\Akima\Model\ResourceModel\BynderConfigSyncData::class
        );
    }
}
