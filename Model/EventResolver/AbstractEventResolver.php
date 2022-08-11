<?php
/**
 * @author andy
 * @email andyworkbase@gmail.com
 * @team MageCloud
 * @package MageCloud_EnhancedEcommerce
 */
declare(strict_types=1);

namespace MageCloud\EnhancedEcommerce\Model\EventResolver;

use Magento\Catalog\Helper\Product\ConfigurationPool;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use MageCloud\EnhancedEcommerce\Model\ResourceModel\ResourceManager\Product as ResourceProduct;
use MageCloud\EnhancedEcommerce\Model\ResourceModel\ResourceManager\Category as ResourceCategory;
use MageCloud\EnhancedEcommerce\Helper\Data as HelperData;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use MageCloud\EnhancedEcommerce\Model\ProductOptionsHandler;
use MageCloud\EnhancedEcommerce\Model\ProductOptionsHandlerFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\Store;
use Magento\GroupedProduct\Model\Product\Type\Grouped as GroupedProductType;
use Magento\Bundle\Model\Product\Type as BundleProductType;

/**
 * Class AbstractEventResolver
 * @package MageCloud\EnhancedEcommerce\Model\EventResolver
 */
abstract class AbstractEventResolver extends DataObject
{
    /**
     * Data layer keys
     */
    const DATA_LAYER_EVENT_KEY = 'event';
    const DATA_LAYER_ECOMMERCE_KEY = 'ecommerce';

    /**
     * Max number of the categories levels
     */
    const MAX_ITEM_CATEGORIES = 5;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var ProductOptionsHandlerFactory
     */
    private $productOptionsHandlerFactory;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var ConfigurationPool
     */
    protected $configurationPool;

    /**
     * @var CartRepositoryInterface
     */
    protected $cartRepository;

    /**
     * @var QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;

    /**
     * @var ResourceProduct
     */
    protected $resourceProduct;

    /**
     * @var ResourceCategory
     */
    protected $resourceCategory;

    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;

    /**
     * @var null[]
     */
    private $initialEcommerceData = [
        self::DATA_LAYER_ECOMMERCE_KEY => null
    ];

    /**
     * @param CheckoutSession $checkoutSession
     * @param ProductOptionsHandlerFactory $productOptionsHandlerFactory
     * @param RequestInterface $request
     * @param ConfigurationPool $configurationPool
     * @param CartRepositoryInterface $cartRepository
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param ResourceProduct $resourceProduct
     * @param ResourceCategory $resourceCategory
     * @param HelperData $helperData
     * @param Json $json
     * @param Registry $registry
     * @param ProductRepositoryInterface $productRepository
     * @param StockRegistryInterface $stockRegistry
     * @param array $data
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        ProductOptionsHandlerFactory $productOptionsHandlerFactory,
        RequestInterface $request,
        ConfigurationPool $configurationPool,
        CartRepositoryInterface $cartRepository,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        ResourceProduct $resourceProduct,
        ResourceCategory $resourceCategory,
        HelperData $helperData,
        Json $json,
        Registry $registry,
        ProductRepositoryInterface $productRepository,
        StockRegistryInterface $stockRegistry,
        array $data = []
    ) {
        parent::__construct($data);
        $this->checkoutSession = $checkoutSession;
        $this->productOptionsHandlerFactory = $productOptionsHandlerFactory;
        $this->request = $request;
        $this->configurationPool = $configurationPool;
        $this->cartRepository = $cartRepository;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->resourceProduct = $resourceProduct;
        $this->resourceCategory = $resourceCategory;
        $this->helperData = $helperData;
        $this->json = $json;
        $this->registry = $registry;
        $this->productRepository = $productRepository;
        $this->stockRegistry = $stockRegistry;
    }

    /**
     * @param Store|null $store
     * @return bool
     */
    protected function isEnabled(Store $store = null): bool
    {
        return $this->helperData->isEnabled($store);
    }

    /**
     * @param $entityId
     * @return null
     */
    protected function initProduct($entityId = null)
    {
        if (null === $entityId) {
            return $this->registry->registry('current_product') ?? $this->registry->registry('product');
        }

        try {
            $product = $this->productRepository->getById($entityId);
        } catch (\Exception $e) {
            $product = null;
        }
        return $product;
    }

    /**
     * @param $cartId
     * @param $masked
     * @return CartInterface|null
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function initQuote($cartId = null, $masked = null): ?CartInterface
    {
        if (null === $cartId) {
            return $this->checkoutSession->getQuote();
        }

        try {
            if ($masked) {
                $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
                $cartId = $quoteIdMask->getQuoteId();
            }
            $quote = $this->cartRepository->getActive($cartId);
        } catch (NoSuchEntityException $e) {
            $quote = null;
        }

        return $quote;
    }

    /**
     * @param QuoteItem $item
     * @return string
     */
    public function getQuoteItemOptions(QuoteItem $item): string
    {
        $values = [];
        $options = $this->configurationPool->getByProductType($item->getProductType())->getOptions($item);
        foreach ($options as $option) {
            $value = $option['value'] ?? null;
            if (!$value) {
                continue;
            }

            if (is_array($value)) {
                foreach ($value as $valueOption) {
                    $values[] = strip_tags($valueOption);
                }
            } else if (is_string($value)) {
                $values[] = $value;
            }
        }
        return implode(',', $values);
    }

    /**
     * @param QuoteItem $quoteItem
     * @param bool $includeTax
     * @return float
     */
    protected function getQuoteItemPrice(QuoteItem $quoteItem, $includeTax = false): float
    {
        $value = $quoteItem->getCustomPrice()
            ?? ($includeTax ? ($quoteItem->getPrice() + $quoteItem->getTaxAmount()) : $quoteItem->getPrice());
        return (float)sprintf('%.2F', $value);
    }

    /**
     * @param Quote $quote
     * @param Store $store
     * @return float
     */
    protected function getQuoteTotalValue(Quote $quote, Store $store): float
    {
        $totalKey = $this->helperData->getOrderTotal($store);
        $value = $quote->getData($totalKey);
        if (
            ($totalKey == OrderInterface::GRAND_TOTAL)
            && $this->helperData->deductTaxFromGrandTotal($store)
        ) {
            $totals = $quote->getTotals();
            if (isset($totals['tax'])) {
                $value = $value - $quote->getTotals()['tax']->getValue();
            }
        }
        return (float)sprintf('%.2F', $value);
    }

    /**
     * @param Product $product
     * @return string
     */
    public function getItemOptions(Product $product): string
    {
        /** @var ProductOptionsHandler $productOptionsHandler */
        $productOptionsHandler = $this->productOptionsHandlerFactory->create(['product' => $product]);
        return implode(',', $productOptionsHandler->getAllOptions());
    }

    /**
     * @param Store|null $store
     * @return mixed
     */
    protected function getItemBrandAttribute(Store $store = null)
    {
        return $this->helperData->getBrandAttribute($store);
    }

    /**
     * @param Product $product
     * @param Store|null $store
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    protected function getItemCategories(Product $product, Store $store = null): array
    {
        if (empty($product->getCategoryIds())) {
            $candidateProductId = $this->resourceProduct->getParentId($product);
            if ($candidateProductId && ($candidateProductId !== (int)$product->getId())) {
                $product = $this->initProduct($candidateProductId);
            }
        }
        $storeId = $store ? $store->getId() : null;
        return $this->resourceCategory->getProductCategories($product, $storeId);
    }

    /**
     * @param Product $product
     * @param null $store
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    protected function buildCategoriesData(Product $product, $store = null)
    {
        $categories = $this->getItemCategories($product, $store);
        $result = [];
        if (!empty($categories)) {
            $categories = array_slice($categories, 0, self::MAX_ITEM_CATEGORIES);
            $categoryKey = 'item_category';
            $result[$categoryKey] = array_shift($categories);
            $index = 2;
            foreach ($categories as $categoryName) {
                $result[sprintf('%s%d', $categoryKey, $index)] = $categoryName;
                $index++;
            }
        }
        return $result;
    }

    /**
     * @param Product $product
     * @return float
     */
    protected function getItemPrice(Product $product): float
    {
        $price = $product->getPriceInfo()->getPrice('final_price')->getAmount()->getValue();
        $typeId = $product->getTypeId();
        if ($typeId == GroupedProductType::TYPE_CODE) {
            $associatedProducts = $product->getTypeInstance()->getAssociatedProducts($product);
            if (!empty($associatedProducts)) {
                $minimalPrice = (float)array_shift($associatedProducts)->getPriceInfo()->getPrice('final_price')
                    ->getAmount()->getValue();

                foreach ($associatedProducts as $associatedProduct) {
                    $associatedProductPrice = (float)$associatedProduct->getPriceInfo()->getPrice('final_price')
                        ->getAmount()->getValue();
                    if ($minimalPrice && $associatedProductPrice && ($associatedProductPrice < $minimalPrice)) {
                        $minimalPrice = $associatedProductPrice;
                    }
                }
                $price = round($minimalPrice,2, PHP_ROUND_HALF_UP);
            }
        } elseif ($typeId == BundleProductType::TYPE_CODE) {
            $price = (float)$product->getPriceInfo()->getPrice('final_price')->getMinimalPrice()->getValue();
        }

        return (float)sprintf('%.2F', $price);
    }

    /**
     * @param Product $product
     * @return null
     */
    private function getMinimalQty(Product $product)
    {
        $stockItem = $this->stockRegistry->getStockItem($product->getId(), $product->getStore()->getWebsiteId());
        $minSaleQty = $stockItem->getMinSaleQty();
        return $minSaleQty > 0 ? $minSaleQty : null;
    }

    /**
     * @param Product $product
     * @return mixed
     */
    protected function getDefaultQty(Product $product)
    {
        $qty = $this->getMinimalQty($product);
        $config = $product->getPreconfiguredValues();
        $configQty = $config->getQty();
        if ($configQty > $qty) {
            $qty = $configQty;
        }
        return $qty;
    }

    /**
     * @param $eventType
     * @return string
     */
    private function renderAsScript($eventType)
    {
        $dataLayerScript = "<!-- START: MageCloud GA4 DataLayer -->";
        $dataLayerScript .= "<script data-exclude='true' data-ga-event='{$eventType}'>";
        // init dataLayer
        $dataLayerScript .= "window.dataLayer = window.dataLayer || [];";
        // clear the previous ecommerce object
        $dataLayerScript .= "dataLayer.push({$this->json->serialize($this->initialEcommerceData)});";
        // push event data
        $dataLayerScript .= "dataLayer.push({$this->toJson()});";
        $dataLayerScript .= "</script>";
        $dataLayerScript .= "<!-- END: MageCloud GA4 DataLayer -->";
        return $dataLayerScript;
    }

    /**
     * @param $eventType
     * @param bool $asScript
     * @return mixed
     */
    protected function renderEventData($eventType, $asScript = false)
    {
        if ($this->isEmpty()) {
            return '';
        }
        if ($asScript) {
            // for view page events (category, product, cart, etc.)
            return $this->renderAsScript($eventType);
        }
        // for dynamic events (add to cart, remove from cart, add payment info, etc.)
        return $this->toJson();
    }
}
