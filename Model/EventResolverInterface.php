<?php
/**
 * @author andy
 * @email andyworkbase@gmail.com
 * @team MageCloud
 * @package MageCloud_EnhancedEcommerce
 */
declare(strict_types=1);

namespace MageCloud\EnhancedEcommerce\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\Store;

/**
 * Interface EventResolverInterface
 * @package MageCloud\EnhancedEcommerce\Model;
 */
interface EventResolverInterface
{
    /**
     * @return string
     */
    public function getEventType(): string;

    /**
     * @param Store|null $store
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isAvailable(Store $store = null): bool;

    /**
     * @param array $eventArguments
     * @return mixed
     */
    public function resolve(array $eventArguments = []);
}
