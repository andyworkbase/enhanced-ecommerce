<?php
/**
 * @author andy
 * @email andyworkbase@gmail.com
 * @team MageCloud
 * @package MageCloud_EnhancedEcommerce
 */
namespace MageCloud\EnhancedEcommerce\Plugin\Quote\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote\Config as DefaultConfig;
use MageCloud\EnhancedEcommerce\Helper\Data as HelperData;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Config
 * @package MageCloud\EnhancedEcommerce\Plugin\Quote\Model
 */
class Config
{
    /**
     * @var HelperData
     */
    private $helperData;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param HelperData $helperData
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        HelperData $helperData,
        StoreManagerInterface $storeManager
    ) {
        $this->helperData = $helperData;
        $this->storeManager = $storeManager;
    }

    /**
     * @param DefaultConfig $config
     * @param $result
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function afterGetProductAttributes(
        DefaultConfig $config,
        $result
    ):array {
        $store = $this->storeManager->getStore();
        if (!$this->helperData->isEnabled($store)) {
            return $result;
        }
        if ($brandAttribute = $this->helperData->getBrandAttribute($store)) {
            if (!in_array($brandAttribute, $result)) {
                $result = array_merge($result, [$brandAttribute]);
            }
        }
        return $result;
    }

}


