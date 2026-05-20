<?php

namespace DamConsultants\Akima\Model\ResourceModel\Collection;

class ApiBynderMediaTableCollection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    
    /**
     * BynderConfigSyncDataCollection
     *
     * @return $this
     */
    protected function _construct()
    {
        $this->_init(
            \DamConsultants\Akima\Model\ApiBynderMediaTable::class,
            \DamConsultants\Akima\Model\ResourceModel\ApiBynderMediaTable::class
        );
    }
}
