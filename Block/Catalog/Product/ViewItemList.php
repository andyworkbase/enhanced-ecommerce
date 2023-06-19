<?php
/**
 * @author andy
 * @email andyworkbase@gmail.com
 * @team MageCloud
 * @package MageCloud_EnhancedEcommerce
 */
declare(strict_types=1);

namespace MageCloud\EnhancedEcommerce\Block\Catalog\Product;

use Magento\Catalog\Block\Product\ProductList\Toolbar as ToolbarBlock;
use Magento\Catalog\Model\Product\ProductList\Toolbar as ToolbarModel;
use MageCloud\EnhancedEcommerce\ViewModel\DataLayerViewModel;
use Magento\Eav\Model\Entity\Collection\AbstractCollection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Catalog\Block\Product\ListProduct;

/**
 * Class ViewItemList
 * @package MageCloud\EnhancedEcommerce\Block\Catalog\Product
 */
class ViewItemList extends ListProduct
{
    /**
     * Default category product collection limit per page.
     * Uses in case if standard toolbar block in the current layout is not provided.
     */
    const DEFAULT_PAGE_SIZE = 9;

    /**
     * @param AbstractCollection $productCollection
     * @return AbstractCollection
     */
    private function prepareCollection(AbstractCollection $productCollection)
    {
        /** @var ToolbarBlock $toolbar */
        $toolbar = $this->getToolbarBlock();
        $limit = $toolbar ? $toolbar->getLimit() : self::DEFAULT_PAGE_SIZE;

        $productCollection->setCurPage($this->_request->getParam(ToolbarModel::PAGE_PARM_NAME, 1))
            ->setPageSize($limit);

        return $productCollection;
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    public function toHtml()
    {
        $collection = null;
        /** @var \Magento\Catalog\Block\Product\ListProduct $categoryProductListBlock */
        $categoryProductListBlock = $this->_layout->getBlock($this->getData('product_list_block_name'));
        if ($categoryProductListBlock) {
            $categoryProductListBlock->toHtml();
            $collection = $categoryProductListBlock->getLoadedProductCollection();
        }

        /** @var DataLayerViewModel $viewModel */
        $viewModel = $this->getData('viewModel');
        $eventArguments = [
            'store' => $this->_storeManager->getStore(),
            'collection' => $collection ? $this->prepareCollection($collection) : null,
            'item_list_name' => $this->getData('itemListName')
        ];
        return $viewModel->execute($eventArguments);
    }
}
