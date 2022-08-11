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
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\Store;
use Magento\Quote\Model\Quote\Item as QuoteItem;

/**
 * Class AddToCart
 * @package MageCloud\EnhancedEcommerce\Model\EventResolver
 */
class RemoveFromCart extends AbstractEventResolver implements EventResolverInterface
{
    /**
     * Indicates the event for which the enhanced e-commerce tag in GTM will be activated
     */
    const EVENT_TYPE = 'remove_from_cart';

    /**
     * @param QuoteItem $quoteItem
     * @param Store|null $store
     * @return array
     * @throws LocalizedException
     * @throws \Zend_Db_Statement_Exception
     */
    private function initItems(QuoteItem $quoteItem, Store $store = null): array
    {
        $productId = $quoteItem->getProductId();
        if (!$productId) {
            return [];
        }
        if (!$product = $this->initProduct($productId)) {
            return [];
        }

        $items = [
            'item_name' => (string)$product->getName(),
            'item_id' => $product->getData($this->helperData->getProductIdentifier($store)),
            'price' => $this->getQuoteItemPrice($quoteItem),
            'quantity' => (int)$quoteItem->getQty() ?? 1
        ];
        if ($options = $this->getQuoteItemOptions($quoteItem)) {
            $items['item_variant'] = $options;
        }
        $brandAttribute = $this->getItemBrandAttribute($store);
        if ($brandAttribute && ($brandAttributeValue = $product->getAttributeText($brandAttribute))) {
            $items['item_brand'] = $brandAttributeValue;
        }

        $items = array_merge($items, $this->buildCategoriesData($product, $store));

        return [$items];
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
        if (!$quoteItem) {
            return;
        }
        if (null === $store) {
            $store = $quoteItem->getStore();
        }

        $this->_data = [
            self::DATA_LAYER_EVENT_KEY => $eventArguments['event_type'] ?? self::EVENT_TYPE,
            self::DATA_LAYER_ECOMMERCE_KEY => []
        ];
        $items = $this->initItems($quoteItem, $store);
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
