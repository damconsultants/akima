<?php

namespace DamConsultants\Akima\Model\ResourceModel\Collection;

class BynderSycDataCollection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    
    /**
     * BynderSycDataCollection
     *
     * @return $this
     */
    protected function _construct()
    {
        $this->_init(
            \DamConsultants\Akima\Model\BynderSycData::class,
            \DamConsultants\Akima\Model\ResourceModel\BynderSycData::class
        );
    }
}
