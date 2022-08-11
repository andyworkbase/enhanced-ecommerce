<?php
/**
 * @author andy
 * @email andyworkbase@gmail.com
 * @team MageCloud
 * @package MageCloud_EnhancedEcommerce
 */
declare(strict_types=1);

namespace MageCloud\EnhancedEcommerce\Block\Catalog\Product;

use MageCloud\EnhancedEcommerce\ViewModel\DataLayerViewModel;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;

/**
 * Class ViewItem
 * @package MageCloud\EnhancedEcommerce\Block\Catalog\Product
 */
class ViewItem extends Template
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