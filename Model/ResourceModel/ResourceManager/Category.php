<?php
/**
 * @author andy
 * @email andyworkbase@gmail.com
 * @team MageCloud
 * @package MageCloud_EnhancedEcommerce
 */
namespace MageCloud\EnhancedEcommerce\Model\ResourceModel\ResourceManager;

use MageCloud\EnhancedEcommerce\Model\ResourceModel\ResourceManager;
use Magento\Catalog\Model\Product;
use Magento\Framework\DB\Select;

/**
 * Class Category
 * @package MageCloud\EnhancedEcommerce\Model\ResourceModel\ResourceManager
 */
class Category extends ResourceManager
{
    /**
     * @var array
     */
    private $productCategoriesMap = [];

    /**
     * @param Product $product
     * @param $storeId
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    public function getProductCategories(Product $product, $storeId = null): array
    {
        $productId = $product->getId();
        if (!isset($this->productCategoriesMap[$productId])) {
            $this->productCategoriesMap[$productId] = [];

            $categoryIds = $product->getCategoryIds();
            if (!empty($categoryIds)) {
                $this->productCategoriesMap[$productId] = $this->getAttributeValues(
                    'name',
                    'catalog_category_entity_varchar',
                    $categoryIds,
                    $storeId
                );
            }
        }
        return $this->productCategoriesMap[$productId];
    }
}