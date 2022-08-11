<?php
/**
 * @author andy
 * @email andyworkbase@gmail.com
 * @team MageCloud
 * @package MageCloud_EnhancedEcommerce
 */
declare(strict_types=1);

namespace MageCloud\EnhancedEcommerce\Model\Config\Source\Product;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class ProductAttributes
 * @package MageCloud\EnhancedEcommerce\Model\Config\Source\Product
 */
class Attributes implements OptionSourceInterface
{
    /**
     * @var ProductAttributeRepositoryInterface
     */
    private $productAttributeRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * Options array
     *
     * @var array
     */
    private $options;

    /**
     * @var bool
     */
    private $addEntityId = false;

    /**
     * @param ProductAttributeRepositoryInterface $productAttributeRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param bool $addEntityId
     */
    public function __construct(
        ProductAttributeRepositoryInterface $productAttributeRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        bool $addEntityId = false
    ) {
        $this->productAttributeRepository = $productAttributeRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->addEntityId = $addEntityId;
    }

    /**
     * @return array[]
     */
    public function toOptionArray($isMultiselect = false): array
    {
        if (empty($this->options)) {
            /** @var \Magento\Framework\Api\SearchCriteria $searchCriteria */
            $searchCriteria = $this->searchCriteriaBuilder->create();
            /** @var \Magento\Catalog\Api\Data\ProductAttributeSearchResultsInterface $searchResult */
            $searchResult = $this->productAttributeRepository->getList($searchCriteria);

            $this->options = array_map(function (ProductAttributeInterface $attribute) {
                return [
                    'value' => $attribute->getAttributeCode(),
                    'label' => __($attribute->getDefaultFrontendLabel())
                ];
            }, $searchResult->getItems());
        }

        if ($this->addEntityId) {
            array_unshift($this->options, ['value' => 'id', 'label' => __('ID')]);
        }
        if (!$isMultiselect) {
            array_unshift($this->options, ['value' => '', 'label' => __('--Please Select--')]);
        }

        return $this->options;
    }
}
