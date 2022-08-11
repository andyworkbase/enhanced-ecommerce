<?php
/**
 * @author andy
 * @email andyworkbase@gmail.com
 * @team MageCloud
 * @package MageCloud_EnhancedEcommerce
 */
declare(strict_types=1);

namespace MageCloud\EnhancedEcommerce\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use MageCloud\EnhancedEcommerce\Model\EventResolverInterface;
use MageCloud\EnhancedEcommerce\Model\EventResolverProvider;

/**
 * Class AvailableEvents
 * @package MageCloud\EnhancedEcommerce\Model\Config\Source
 */
class AvailableEvents implements OptionSourceInterface
{
    /**
     * Key indicating that all events are available
     */
    const ALL_KEY = 'all';

    /**
     * @var EventResolverProvider
     */
    private $eventResolverProvider;

    /**
     * Options array
     *
     * @var array
     */
    private $options;

    /**
     * @var bool
     */
    private $includeAll = true;

    /**
     * @param EventResolverProvider $eventResolverProvider
     */
    public function __construct(
        EventResolverProvider $eventResolverProvider
    ) {
        $this->eventResolverProvider = $eventResolverProvider;
    }

    /**
     * @return array[]
     */
    public function toOptionArray()
    {
        if (empty($this->options)) {
            $this->options = array_map(function (EventResolverInterface $event) {
                return [
                    'value' => $event->getEventType(),
                    'label' => __(ucwords(str_replace('_', ' ', $event->getEventType())))
                ];
            }, $this->eventResolverProvider->getResolvers());

            if ($this->includeAll) {
                array_unshift($this->options, ['value' => self::ALL_KEY, 'label' => __('All')]);
            }
        }
        return $this->options;
    }
}
