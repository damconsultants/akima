<?php

namespace DamConsultants\Akima\Model\ResourceModel\Collection;

class BynderMediaTableCollection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    
    /**
     * BynderConfigSyncDataCollection
     *
     * @return $this
     */
    protected function _construct()
    {
        $this->_init(
            \DamConsultants\Akima\Model\BynderMediaTable::class,
            \DamConsultants\Akima\Model\ResourceModel\BynderMediaTable::class
        );
    }
}
