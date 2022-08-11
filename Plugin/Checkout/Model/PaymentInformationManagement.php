<?php
/**
 * @author andy
 * @email andyworkbase@gmail.com
 * @team MageCloud
 * @package MageCloud_EnhancedEcommerce
 */
namespace MageCloud\EnhancedEcommerce\Plugin\Checkout\Model;

use Magento\Checkout\Model\PaymentInformationManagement as DefaultPaymentInformationManagement;
use MageCloud\EnhancedEcommerce\Model\EventManager;
use MageCloud\EnhancedEcommerce\Model\EventManagerFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use MageCloud\EnhancedEcommerce\Model\EventResolver\BeginCheckout;
use MageCloud\EnhancedEcommerce\Model\EventResolver\AddPaymentInfo;

/**
 * Class PaymentInformationManagement
 * @package MageCloud\EnhancedEcommerce\Plugin\Checkout\Model
 */
class PaymentInformationManagement
{
    /**
     * @var EventManagerFactory
     */
    private $eventManagerFactory;

    /**
     * @var bool
     */
    private $submitPlaceOrder = false;

    /**
     * @param EventManagerFactory $eventManagerFactory
     */
    public function __construct(
        EventManagerFactory $eventManagerFactory
    ) {
        $this->eventManagerFactory = $eventManagerFactory;
    }

    /**
     * @param DefaultPaymentInformationManagement $subject
     * @param $cartId
     * @param PaymentInterface $paymentMethod
     * @param AddressInterface|null $billingAddress
     * @return null
     * @throws NoSuchEntityException
     */
    public function beforeSavePaymentInformationAndPlaceOrder(
        DefaultPaymentInformationManagement $subject,
        $cartId,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null
    ) {
        // to prevent duplicate events
        $this->submitPlaceOrder = true;
        /** @var EventManager $eventManager */
        $eventManager = $this->eventManagerFactory->create(
            [
                'eventArguments' => [
                    'event_type' => BeginCheckout::EVENT_TYPE,
                    'dynamic' => true,
                    'cart_id' => $cartId
                ]
            ]
        );
        $eventManager->initEvent();
        return null;
    }

    /**
     * @param DefaultPaymentInformationManagement $subject
     * @param $result
     * @param $cartId
     * @param PaymentInterface $paymentMethod
     * @param AddressInterface|null $billingAddress
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function afterSavePaymentInformation(
        DefaultPaymentInformationManagement $subject,
        $result,
        $cartId,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null
    ) {
        // to prevent duplicate events
        if ($this->submitPlaceOrder) {
            return $result;
        }
        /** @var EventManager $eventManager */
        $eventManager = $this->eventManagerFactory->create(
            [
                'eventArguments' => [
                    'event_type' => AddPaymentInfo::EVENT_TYPE,
                    'dynamic' => true,
                    'cart_id' => $cartId
                ]
            ]
        );
        $eventManager->initEvent();
        return $result;
    }
}