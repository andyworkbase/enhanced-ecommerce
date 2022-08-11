<?php
/**
 * @author andy
 * @email andyworkbase@gmail.com
 * @team MageCloud
 * @package MageCloud_EnhancedEcommerce
 */
declare(strict_types=1);

namespace MageCloud\EnhancedEcommerce\Model;

use Magento\Framework\DataObject;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Api\ProductCustomOptionRepositoryInterface;
use Magento\Bundle\Model\Product\Type as BundleType;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use Magento\GroupedProduct\Model\Product\Type\Grouped as GroupedType;

/**
 * Class ProductOptionsHandler
 * @package MageCloud\EnhancedEcommerce\Model
 */
class ProductOptionsHandler extends DataObject
{
    /**
     * @var ProductCustomOptionRepositoryInterface
     */
    private $productCustomOptionRepository;

    /**
     * @var Product
     */
    private $product;

    /**
     * @param ProductCustomOptionRepositoryInterface $productCustomOptionRepository
     * @param Product|null $product
     * @param array $data
     */
    public function __construct(
        ProductCustomOptionRepositoryInterface $productCustomOptionRepository,
        Product $product = null,
        array $data = []
    ) {
        parent::__construct($data);
        $this->productCustomOptionRepository = $productCustomOptionRepository;
        $this->product = $product;
    }

    /**
     * @param $product
     * @return array
     */
    public function getCustomOptions($product): array
    {
        $result = [];
        foreach ($this->productCustomOptionRepository->getProductOptions($product) as $option) {
            foreach ($option->getValues() as $value) {
                $result[] = $value->getTitle();
            }
        }
        return $result;
    }

    /**
     * @param Product $product
     * @return array
     */
    public function getConfigurableOptions(Product $product): array
    {
        $result = [];
        foreach ($product->getTypeInstance()->getConfigurableAttributesAsArray($product) as $attributeData) {
            if (isset($attributeData['values'])) {
                $values = $attributeData['values'];

                if (is_array($values)) {
                    foreach ($values as $option) {
                        $result[] = $option['label'] ?? $option['value'];
                    }
                } else if (is_string($values)) {
                    $result[] = $values;
                }
            }
        }
        return $result;
    }

    /**
     * @param Product $product
     * @return array
     */
    public function getGroupedOptions(Product $product): array
    {
        $result = [];
        foreach ($product->getTypeInstance()->getAssociatedProducts($product) as $associatedProduct) {
            $result[] = $associatedProduct->getName();
        }
        return $result;
    }

    /**
     * @param Product $product
     * @return array
     */
    public function getBundleOptions(Product $product): array
    {
        $result = [];
        /** @var \Magento\Bundle\Model\ResourceModel\Option\Collection $options */
        $options = $product->getTypeInstance()->getOptionsCollection($product);
        /** @var \Magento\Bundle\Model\ResourceModel\Selection\Collection $selections */
        $selections = $product->getTypeInstance()->getSelectionsCollection($options->getAllIds(), $product);
        foreach ($selections as $selection) {
            $result[] = $selection->getName();
        }
        return $result;
    }

    /**
     * @param Product $product
     * @return array
     */
    public function getOptionsByProductType(Product $product): array
    {
        $typeId = $product->getTypeId();
        if ($typeId == ConfigurableType::TYPE_CODE) {
            return $this->getConfigurableOptions($product);
        } else if ($typeId == BundleType::TYPE_CODE) {
            return $this->getBundleOptions($product);
        } else if ($typeId == GroupedType::TYPE_CODE) {
            $this->getGroupedOptions($product);
        }
        return [];
    }

    /**
     * @return array
     */
    public function getAllOptions(): array
    {
        $product = $this->product;
        return array_merge(
            $this->getOptionsByProductType($product),
            $this->getCustomOptions($product)
        );
    }
}
