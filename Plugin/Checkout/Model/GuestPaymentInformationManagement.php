<?php
/**
 * @author andy
 * @email andyworkbase@gmail.com
 * @team MageCloud
 * @package MageCloud_EnhancedEcommerce
 */
namespace MageCloud\EnhancedEcommerce\Plugin\Checkout\Model;

use Magento\Checkout\Model\GuestPaymentInformationManagement as DefaultGuestPaymentInformationManagement;
use MageCloud\EnhancedEcommerce\Model\EventManager;
use MageCloud\EnhancedEcommerce\Model\EventManagerFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use MageCloud\EnhancedEcommerce\Model\EventResolver\AddPaymentInfo;

/**
 * Class PaymentInformationManagement
 * @package MageCloud\EnhancedEcommerce\Plugin\Checkout\Model
 */
class GuestPaymentInformationManagement
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
     * @param DefaultGuestPaymentInformationManagement $subject
     * @param $result
     * @param $cartId
     * @param $email
     * @param PaymentInterface $paymentMethod
     * @param AddressInterface|null $billingAddress
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function afterSavePaymentInformation(
        DefaultGuestPaymentInformationManagement $subject,
        $result,
        $cartId,
        $email,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null
    ) {
        /** @var EventManager $eventManager */
        $eventManager = $this->eventManagerFactory->create(
            [
                'eventArguments' => [
                    'event_type' => AddPaymentInfo::EVENT_TYPE,
                    'dynamic' => true,
                    'cart_id' => $cartId,
                    'masked_cart_id' => true
                ]
            ]
        );
        $eventManager->initEvent();
        return $result;
    }
}