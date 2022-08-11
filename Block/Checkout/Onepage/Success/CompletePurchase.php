<?php
/**
 * @author andy
 * @email andyworkbase@gmail.com
 * @team MageCloud
 * @package MageCloud_EnhancedEcommerce
 */
declare(strict_types=1);

namespace MageCloud\EnhancedEcommerce\Block\Checkout\Onepage\Success;

use MageCloud\EnhancedEcommerce\ViewModel\DataLayerViewModel;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;

/**
 * Class CompletePurchase
 * @package MageCloud\EnhancedEcommerce\Block\Checkout\Onepage\Success
 */
class CompletePurchase extends Template
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