<?php

namespace DamConsultants\Akima\Model\ResourceModel\Collection;

class MetaPropertyCollection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * MetaPropertyCollection
     *
     * @return $this
     */
    protected function _construct()
    {
        $this->_init(
            \DamConsultants\Akima\Model\MetaProperty::class,
            \DamConsultants\Akima\Model\ResourceModel\MetaProperty::class
        );
    }
}
