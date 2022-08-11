<?php
/**
 * @author andy
 * @email andyworkbase@gmail.com
 * @team MageCloud
 * @package MageCloud_EnhancedEcommerce
 */
declare(strict_types=1);

namespace MageCloud\EnhancedEcommerce\Model\EventResolver;

use MageCloud\EnhancedEcommerce\Model\Config\Source\AvailableEvents;
use MageCloud\EnhancedEcommerce\Model\EventResolverInterface;
use Magento\Catalog\Model\Category;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\Store;
use Magento\Eav\Model\Entity\Collection\AbstractCollection;

/**
 * Class ViewItemList
 * @package MageCloud\EnhancedEcommerce\Model\EventResolver
 */
class ViewItemList extends AbstractEventResolver implements EventResolverInterface
{
    /**
     * Indicates the event for which the enhanced e-commerce tag in GTM will be activated
     */
    const EVENT_TYPE = 'view_item_list';

    /**
     *  Indicates the list where the products were shown
     */
    const ITEM_LIST_CATEGORY = 'Catalog Category';
    const ITEM_LIST_SEARCH_RESULTS = 'Catalog Search Results';

    /**
     * Default product items qty
     */
    const DEFAULT_ITEM_QTY = 1;

    /**
     * @return Category|null
     */
    private function initCategory()
    {
        return $this->registry->registry('current_category') ?? $this->registry->registry('category');
    }

    /**
     * @param AbstractCollection $productCollection
     * @param Category|null $currentCategory
     * @param Store|null $store
     * @param array $eventArguments
     * @return array
     * @throws LocalizedException
     * @throws \Zend_Db_Statement_Exception
     */
    private function initItems(
        AbstractCollection $productCollection,
        Category $currentCategory = null,
        Store $store = null,
        array $eventArguments = []
    ): array {
        $items = [];
        $brandAttribute = $this->getItemBrandAttribute($store);
        foreach ($productCollection as $key => $product) {
            $items[$key] = [
                'item_name' => (string)$product->getName(),
                'item_id' => $product->getData($this->helperData->getProductIdentifier($store)),
                'price' => $this->getItemPrice($product),
                'item_list_name' => $eventArguments['item_list_name']
                    ?: ($currentCategory ? $currentCategory->getName() : self::ITEM_LIST_CATEGORY),
                'index' => $key,
                'quantity' => self::DEFAULT_ITEM_QTY
            ];
            if ($currentCategory) {
                $items[$key]['item_list_id'] = (int)$currentCategory->getId();
            }
            if ($brandAttribute && ($brandAttributeValue = $product->getAttributeText($brandAttribute))) {
                $items[$key]['item_brand'] = $brandAttributeValue;
            }
            $items[$key] = array_merge($items[$key], $this->buildCategoriesData($product, $store));
        }

        return array_values($items);
    }

    /**
     * @param Store|null $store
     * @param array $eventArguments
     * @return void
     * @throws LocalizedException
     */
    protected function initEventData(Store $store = null, array $eventArguments = []): void
    {
        $category = $this->initCategory();
        $productCollection = $eventArguments['collection'] ?? null;
        if (!$productCollection instanceof AbstractCollection) {
            return;
        }

        $this->_data = [
            self::DATA_LAYER_EVENT_KEY => $eventArguments['event_type'] ?? self::EVENT_TYPE,
            self::DATA_LAYER_ECOMMERCE_KEY => []
        ];
        $items = $this->initItems($productCollection, $category, $store, $eventArguments);
        if (!empty($items)) {
            $this->_data[self::DATA_LAYER_ECOMMERCE_KEY]['items'] = $items;
        }
    }

    /**
     * @inheirtDoc
     */
    public function getEventType(): string
    {
        return self::EVENT_TYPE;
    }

    /**
     * @inheirtDoc
     */
    public function isAvailable(Store $store = null): bool
    {
        $availableEvents = $this->helperData->getAvailableEvents($store);
        return in_array(AvailableEvents::ALL_KEY, $availableEvents)
            || in_array($this->getEventType(), $availableEvents);
    }

    /**
     * @inheirtDoc
     */
    public function resolve(array $eventArguments = [])
    {
        $eventType = $eventArguments['event_type'] ?? '';
        $store = $eventArguments['store'] ?? null;
        if (!$this->isAvailable($store) || !$this->isEnabled($store)) {
            return '';
        }
        // that in case if there are any error during data collect don't break a current processing
        try {
            $this->initEventData($store, $eventArguments);
        } catch (\Exception $e) {
            // omit exception
        }
        return $this->renderEventData($eventType, true);
    }
}
