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
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\Store;

/**
 * Class Purchase
 * @package MageCloud\EnhancedEcommerce\Model\EventResolver
 */
class Purchase extends AbstractEventResolver implements EventResolverInterface
{
    /**
     * Indicates the event for which the enhanced e-commerce tag in GTM will be activated
     */
    const EVENT_TYPE = 'purchase';

    /**
     * @return Order
     */
    private function initOrder(): Order
    {
        return $this->checkoutSession->getLastRealOrder();
    }

    /**
     * @param OrderItemInterface $item
     * @return string
     */
    private function getOrderItemOptions(OrderItemInterface $item): string
    {
        $values = [];
        $options = $item->getProductOptions()['options'] ?? [];
        foreach ($options as $option) {
            $values[] = $option['value'] ?? $option['label'];
        }
        return implode(',', $values);
    }

    /**
     * @param Order $order
     * @param Store $store
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    private function initItems(Order $order, Store $store): array
    {
        $brandAttribute = $this->getItemBrandAttribute($store);
        $items = [];
        /** @var OrderItemInterface $item */
        foreach ($order->getAllVisibleItems() as $key => $item) {
            /** @var Product $product */
            $product = $item->getProduct();

            $items[$key] = [
                'item_name' => (string)$item->getName(),
                'item_id' => $product->getData($this->helperData->getProductIdentifier($store)),
                'price' => (float)$item->getPrice(),
                'quantity' => (int)$item->getQtyOrdered()
            ];
            if ($options = $this->getOrderItemOptions($item)) {
                $items[$key]['item_variant'] = $options;
            }
            if ($brandAttribute && ($brandAttributeValue = $product->getAttributeText($brandAttribute))) {
                $items[$key]['item_brand'] = $brandAttributeValue;
            }
            $items[$key] = array_merge($items[$key], $this->buildCategoriesData($product, $store));
        }

        return array_values($items);
    }

    /**
     * @param Order $order
     * @return float
     */
    private function getOrderTotalValue(Order $order)
    {
        $store = $order->getStore();
        $orderTotalKey = $this->helperData->getOrderTotal($store);
        $value = $order->getData($orderTotalKey);
        if (
            ($orderTotalKey == OrderInterface::GRAND_TOTAL)
            && $this->helperData->deductTaxFromGrandTotal($store)
        ) {
            $value = $value - $order->getTaxAmount();
        }
        return (float)sprintf('%.2F', $value);
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
        if (!$order = $this->initOrder()) {
            return;
        }
        if (null === $store) {
            $store = $order->getStore();
        }

        $this->_data = [
            self::DATA_LAYER_EVENT_KEY => $eventArguments['event_type'] ?? self::EVENT_TYPE,
            self::DATA_LAYER_ECOMMERCE_KEY => [
                'transaction_id' => $order->getIncrementId(),
                'affiliation' => $store->getFrontendName(),
                'value' => $this->getOrderTotalValue($order),
                'currency' => $this->helperData->getCurrentCurrencyCode($store)
            ]
        ];
        if ($this->helperData->includeTax($store)) {
            $this->_data[self::DATA_LAYER_ECOMMERCE_KEY]['tax'] =
                (float)sprintf('%.2F',$order->getTaxAmount());
        }
        if ($this->helperData->includeShipping($store)) {
            $this->_data[self::DATA_LAYER_ECOMMERCE_KEY]['shipping'] =
                (float)sprintf('%.2F', $order->getShippingAmount());
        }
        if ($coupon = $order->getCouponCode()) {
            $this->_data[self::DATA_LAYER_ECOMMERCE_KEY]['coupon'] = $coupon;
        }
        $items = $this->initItems($order, $store);
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
