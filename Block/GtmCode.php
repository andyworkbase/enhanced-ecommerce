<?php
/**
 * @author andy
 * @email andyworkbase@gmail.com
 * @team MageCloud
 * @package MageCloud_EnhancedEcommerce
 */
declare(strict_types=1);

namespace MageCloud\EnhancedEcommerce\Block;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Class GtmCode
 * @package MageCloud\EnhancedEcommerce\Block
 */
class GtmCode extends Template
{
    /**
     * @param $store
     * @return StoreInterface
     * @throws NoSuchEntityException
     */
    public function getStore($store = null): StoreInterface
    {
        return $this->_storeManager->getStore($store = null);
    }
}