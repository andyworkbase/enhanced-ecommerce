<?php
/**
 * @author andy
 * @email andyworkbase@gmail.com
 * @team MageCloud
 * @package MageCloud_EnhancedEcommerce
 */
declare(strict_types=1);

namespace MageCloud\EnhancedEcommerce\Block\Checkout;

use MageCloud\EnhancedEcommerce\ViewModel\DataLayerViewModel;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;

/**
 * Class BeginCheckout
 * @package MageCloud\EnhancedEcommerce\Block\Checkout
 */
class BeginCheckout extends Template
{
    /**
     * @return string
     * @throws NoSuchEntityException
     */
    public function toHtml()
    {
        /** @var DataLayerViewModel $viewModel */
        $viewModel = $this->getData('viewModel');
        $eventArguments = [
            'store' => $this->_storeManager->getStore()
        ];
        return $viewModel->execute($eventArguments);
    }
}