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
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Store\Model\Store;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class BeginCheckout
 * @package MageCloud\EnhancedEcommerce\Model\EventResolver
 */
class AddShippingInfo extends AbstractEventResolver implements EventResolverInterface
{
    /**
     * Indicates the event for which the enhanced e-commerce tag in GTM will be activated
     */
    const EVENT_TYPE = 'add_shipping_info';

    /**
     * @param Quote $quote
     * @param Store $store
     * @param $currencyCode
     * @param $coupon
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws \Zend_Db_Statement_Exception
     */
    private function initItems(Quote $quote, Store $store, $currencyCode, $coupon = null): array
    {
        $brandAttribute = $this->getItemBrandAttribute($store);
        $storeName = $store->getFrontendName();
        $items = [];
        foreach ($quote->getAllVisibleItems() as $key => $item) {
            /** @var QuoteItem $item */
            /** @var Product $product */
            $product = $item->getProduct();

            $items[$key] = [
                'item_name' => (string)$item->getName(),
                'item_id' => $product->getData($this->helperData->getProductIdentifier($store)),
                'price' => $this->getQuoteItemPrice($item),
                'quantity' => (int)$item->getQty(),
                'affiliation' => $storeName,
                'currency' => $currencyCode
            ];
            $discount = $item->getDiscountAmount();
            if ($discount > 0) {
                $items[$key]['discount'] = $discount;
            }
            if ($coupon) {
                $items[$key]['coupon'] = $coupon;
            }
            if ($options = $this->getQuoteItemOptions($item)) {
                $items[$key]['item_variant'] = $options;
            }
            if ($brandAttribute && ($brandAttributeValue = $product->getAttributeText($brandAttribute))) {
                $items[$key]['item_brand'] = $brandAttributeValue;
            }
            $items[$key] = array_merge($items[$key], $this->buildCategoriesData($product, $store));
        }

        return $items;
    }

    /**
     * @param Store|null $store
     * @param array $eventArguments
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function initEventData(Store $store = null, array $eventArguments = []): void
    {
        $cartId = $eventArguments['cart_id'] ?? null;
        if (!$cartId) {
            return;
        }
        if (!$quote = $this->initQuote($cartId)) {
            return;
        }
        if (null === $store) {
            $store = $quote->getStore();
        }

        $currencyCode = $quote->getCurrency()->getQuoteCurrencyCode();
        $this->_data = [
            self::DATA_LAYER_EVENT_KEY => $eventArguments['event_type'] ?? self::EVENT_TYPE,
            self::DATA_LAYER_ECOMMERCE_KEY => [
                'currency' => $currencyCode,
                'shipping_tier' => $quote->getShippingAddress()->getShippingDescription()
                    ?? $quote->getShippingAddress()->getShippingMethod(),
                'value' => $this->getQuoteTotalValue($quote, $store)
            ]
        ];
        if ($coupon = $quote->getCouponCode()) {
            $this->_data[self::DATA_LAYER_ECOMMERCE_KEY]['coupon'] = $coupon;
        }
        $items = $this->initItems($quote, $store, $currencyCode, $coupon);
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
