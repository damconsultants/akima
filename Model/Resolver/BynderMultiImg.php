<?php
namespace DamConsultants\Akima\Model\Resolver;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class BynderMultiImg implements ResolverInterface
{
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        ?array $value = null,
        ?array $args = null
    ) {
        /** @var ProductInterface $product */
        $product = $value['model'] ?? null;

        if (!$product) {
            return null;
        }

        return $product->getData('bynder_multi_img');
    }
}
