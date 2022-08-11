<?php
/**
 * @author andy
 * @email andyworkbase@gmail.com
 * @team MageCloud
 * @package MageCloud_EnhancedEcommerce
 */
namespace MageCloud\EnhancedEcommerce\Observer;

use MageCloud\EnhancedEcommerce\Model\EventManager;
use MageCloud\EnhancedEcommerce\Model\EventManagerFactory;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\NoSuchEntityException;
use MageCloud\EnhancedEcommerce\Model\EventResolver\RemoveFromCart;
use Magento\Quote\Model\Quote\Item;

/**
 * Class SalesQuoteRemoveItemObserver
 * @package MageCloud\EnhancedEcommerce\Observer
 */
class SalesQuoteRemoveItemObserver implements ObserverInterface
{
    /**
     * @var EventManagerFactory
     */
    private $eventManagerFactory;

    /**
     * @param EventManagerFactory $eventManagerFactory
     */
    public function __construct(
        EventManagerFactory $eventManagerFactory
    ) {
        $this->eventManagerFactory = $eventManagerFactory;
    }

    /**
     * @param Observer $observer
     * @return void
     * @throws NoSuchEntityException
     */
    public function execute(Observer $observer): void
    {
        /** @var Item $quoteItem */
        $quoteItem = $observer->getEvent()->getQuoteItem();

        /** @var EventManager $eventManager */
        $eventManager = $this->eventManagerFactory->create(
            [
                'eventArguments' => [
                    'event_type' => RemoveFromCart::EVENT_TYPE,
                    'dynamic' => true,
                    'quote_item' => $quoteItem
                ]
            ]
        );
        $eventManager->initEvent();
    }
}
