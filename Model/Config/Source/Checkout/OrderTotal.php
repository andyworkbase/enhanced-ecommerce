<?php
/**
 * @author andy
 * @email andyworkbase@gmail.com
 * @team MageCloud
 * @package MageCloud_EnhancedEcommerce
 */
declare(strict_types=1);

namespace MageCloud\EnhancedEcommerce\Model\Config\Source\Checkout;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Class ProductAttributes
 * @package MageCloud\EnhancedEcommerce\Model\Config\Source
 */
class OrderTotal implements OptionSourceInterface
{
    /**
     * Options array
     *
     * @var array
     */
    private $options;

    /**
     * @return array[]
     */
    public function toOptionArray(): array
    {
        if (empty($this->options)) {
            $this->options = [
                [
                    'value' => OrderInterface::GRAND_TOTAL,
                    'label' => __('Grand Total')
                ],
                [
                    'value' => OrderInterface::SUBTOTAL,
                    'label' => __('Subtotal')
                ]
            ];
        }
        return $this->options;
    }
}
