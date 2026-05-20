<?php

namespace DamConsultants\Akima\Model\ResourceModel\Collection;

class BynderAutoReplaceDataCollection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    
    /**
     * BynderConfigSyncDataCollection
     *
     * @return $this
     */
    protected function _construct()
    {
        $this->_init(
            \DamConsultants\Akima\Model\BynderAutoReplaceData::class,
            \DamConsultants\Akima\Model\ResourceModel\BynderAutoReplaceData::class
        );
    }
}
