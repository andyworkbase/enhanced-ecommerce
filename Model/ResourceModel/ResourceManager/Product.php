<?php
/**
 * @author andy
 * @email andyworkbase@gmail.com
 * @team MageCloud
 * @package MageCloud_EnhancedEcommerce
 */
namespace MageCloud\EnhancedEcommerce\Model\ResourceModel\ResourceManager;

use MageCloud\EnhancedEcommerce\Model\ResourceModel\ResourceManager;
use Magento\Framework\DB\Select;

/**
 * Class Product
 * @package MageCloud\EnhancedEcommerce\Model\ResourceModel\ResourceManager
 */
class Product extends ResourceManager
{
    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return int
     */
    public function getParentId(\Magento\Catalog\Model\Product $product): int
    {
        $connection = $this->getConnection();
        $productId = (int)$product->getId();
        $select = $connection->select()
            ->from($connection->getTableName('catalog_product_relation'), ['parent_id'])
            ->where('child_id = ?', $productId, \Zend_Db::INT_TYPE);
        $parentId = (int)$connection->fetchOne($select);
        return $parentId ?? $productId;
    }
}