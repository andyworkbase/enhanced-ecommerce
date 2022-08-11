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
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\GroupedProduct\Model\Product\Type\Grouped as GroupedType;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Store\Model\Store;

/**
 * Class AddToCart
 * @package MageCloud\EnhancedEcommerce\Model\EventResolver
 */
class AddToCart extends AbstractEventResolver implements EventResolverInterface
{
    /**
     * Indicates the event for which the enhanced e-commerce tag in GTM will be activated
     */
    const EVENT_TYPE = 'add_to_cart';

    /**
     * @param Product $product
     * @return array
     */
    private function prepareItems(Product $product)
    {
        $requestParams = $this->request->getParams();
        $qty = $requestParams['qty'] ?? 1;

        $items = [];
        $items[] = [
            'qty' => $qty,
            'item' => $product
        ];
        $reiniItems = false;
        $typeId = $product->getTypeId();

        if ($typeId == GroupedType::TYPE_CODE) {
            $superGroup = $requestParams['super_group'];
            $superGroup = is_array($superGroup) ? array_filter($superGroup, 'intval') : [];

            $associatedProducts = $product->getTypeInstance()->getAssociatedProducts($product);
            foreach ($associatedProducts as $associatedProduct) {
                $associatedProductId = (int)$associatedProduct->getId();
                if (isset($superGroup[$associatedProductId]) && ($superGroup[$associatedProductId] > 0)) {
                    if (!$reiniItems) {
                        // re-init items array tu push grouped product(s)
                        $items = [];
                        $reiniItems = true;
                    }
                    if ($associatedProductModel = $this->initProduct($associatedProductId)) {
                        $items[] = [
                            'qty' => (int)$superGroup[$associatedProductId],
                            'item' => $associatedProductModel
                        ];
                    }
                }
            }
        }

        return $items;
    }

    /**
     * @param QuoteItem $quoteItem
     * @param Product $product
     * @param Store|null $store
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    private function initItems(QuoteItem $quoteItem, Product $product, Store $store = null): array
    {
        $items = [];
        foreach ($this->prepareItems($product) as $key => $itemData) {
            /** @var Product $item */
            $item = $itemData['item'] ?? null;
            if (!$item) {
                continue;
            }

            $items[$key] = [
                'item_name' => $item->getName(),
                'item_id' => $item->getData($this->helperData->getProductIdentifier($store)),
                'price' => $this->getQuoteItemPrice($quoteItem, true),
                'quantity' => (int)$itemData['qty'] ?? 1
            ];
            if ($options = $this->getQuoteItemOptions($quoteItem)) {
                $items[$key]['item_variant'] = $options;
            }
            $brandAttribute = $this->getItemBrandAttribute($store);
            if ($brandAttribute && ($brandAttributeValue = $item->getAttributeText($brandAttribute))) {
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
        /** @var QuoteItem $quoteItem */
        $quoteItem = $eventArguments['quote_item'] ?? null;
        /** @var Product $product */
        $product = $eventArguments['product'] ?? null;
        if (!$quoteItem || !$product) {
            return;
        }
        if (null === $store) {
            $store = $quoteItem->getStore();
        }

        $this->_data = [
            self::DATA_LAYER_EVENT_KEY => $eventArguments['event_type'] ?? self::EVENT_TYPE,
            self::DATA_LAYER_ECOMMERCE_KEY => []
        ];
        $items = $this->initItems($quoteItem, $product, $store);
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
        return $this->renderEventData($eventType);
    }
}
