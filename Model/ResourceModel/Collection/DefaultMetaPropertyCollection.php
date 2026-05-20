<?php

namespace DamConsultants\Akima\Model\ResourceModel\Collection;

class DefaultMetaPropertyCollection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * MetaPropertyCollection
     *
     * @return $this
     */
    protected function _construct()
    {
        $this->_init(
            \DamConsultants\Akima\Model\DefaultMetaProperty::class,
            \DamConsultants\Akima\Model\ResourceModel\DefaultMetaProperty::class
        );
    }
}
