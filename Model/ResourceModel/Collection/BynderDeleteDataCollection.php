<?php

namespace DamConsultants\Akima\Model\ResourceModel\Collection;

class BynderDeleteDataCollection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    
    /**
     * BynderConfigSyncDataCollection
     *
     * @return $this
     */
    protected function _construct()
    {
        $this->_init(
            \DamConsultants\Akima\Model\BynderDeleteData::class,
            \DamConsultants\Akima\Model\ResourceModel\BynderDeleteData::class
        );
    }
}
