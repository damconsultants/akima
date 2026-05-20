<?php

namespace DamConsultants\Akima\Model\ResourceModel\Collection;

class BynderTempDocDataCollection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    
    /**
     * BynderConfigSyncDataCollection
     *
     * @return $this
     */
    protected function _construct()
    {
        $this->_init(
            \DamConsultants\Akima\Model\BynderTempDocData::class,
            \DamConsultants\Akima\Model\ResourceModel\BynderTempDocData::class
        );
    }
}
