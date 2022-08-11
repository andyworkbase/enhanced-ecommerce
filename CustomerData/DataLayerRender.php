<?php
/**
 * @author andy
 * @email andyworkbase@gmail.com
 * @team MageCloud
 * @package MageCloud_EnhancedEcommerce
 */
namespace MageCloud\EnhancedEcommerce\CustomerData;

use MageCloud\EnhancedEcommerce\Model\EventSession;
use MageCloud\EnhancedEcommerce\Model\EventResolver\AddToCart;
use MageCloud\EnhancedEcommerce\Model\EventResolver\RemoveFromCart;
use MageCloud\EnhancedEcommerce\Model\EventResolver\AddShippingInfo;
use MageCloud\EnhancedEcommerce\Model\EventResolver\AddPaymentInfo;
use MageCloud\EnhancedEcommerce\Model\EventResolver\BeginCheckout;
use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Framework\DataObject;

/**
 * Class DataLayerRender
 * @package MageCloud\EnhancedEcommerce\CustomerData
 */
class DataLayerRender extends DataObject implements SectionSourceInterface
{
    /**
     * @var EventSession
     */
    private $eventSession;

    /**
     * @param EventSession $eventSession
     * @param array $data
     */
    public function __construct(
        EventSession $eventSession,
        array $data = []
    ) {
        parent::__construct($data);
        $this->eventSession = $eventSession;
    }

    /**
     * {@inheritdoc}
     */
    public function getSectionData()
    {
        $dataLayerEvents = [];
        if ($removeFromCartEventData = $this->eventSession->getEventSessionData(RemoveFromCart::EVENT_TYPE)) {
            $dataLayerEvents[] = $removeFromCartEventData;
        }
        if ($addToCarEventData = $this->eventSession->getEventSessionData(AddToCart::EVENT_TYPE)) {
            $dataLayerEvents[] = $addToCarEventData;
        }
        if ($addShippingInfoEventData = $this->eventSession->getEventSessionData(AddShippingInfo::EVENT_TYPE)) {
            $dataLayerEvents[] = $addShippingInfoEventData;
        }
        if ($addPaymentInfoEventData = $this->eventSession->getEventSessionData(AddPaymentInfo::EVENT_TYPE)) {
            $dataLayerEvents[] = $addPaymentInfoEventData;
        }
        if ($beginCheckoutEventData = $this->eventSession->getEventSessionData(BeginCheckout::EVENT_TYPE)) {
            $dataLayerEvents[] = $beginCheckoutEventData;
        }

        return [
            'events' => $dataLayerEvents
        ];
    }
}