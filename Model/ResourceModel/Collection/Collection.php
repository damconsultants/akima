<?php

namespace DamConsultants\Akima\Model\ResourceModel\Collection;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Collection
     *
     * @return $this
     */
    protected function _construct()
    {
        $this->_init(
            \DamConsultants\Akima\Model\Bynder::class,
            \DamConsultants\Akima\Model\ResourceModel\Bynder::class
        );
    }
}
