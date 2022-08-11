<?php
/**
 * @author andy
 * @email andyworkbase@gmail.com
 * @team MageCloud
 * @package MageCloud_EnhancedEcommerce
 */
declare(strict_types=1);

namespace MageCloud\EnhancedEcommerce\Model;

/**
 * Class EventResolverProvider
 * @package MageCloud\EnhancedEcommerce\Model
 */
class EventResolverProvider
{
    /**
     * @var EventResolverInterface[]
     */
    private $eventResolvers = [];

    /**
     * @param array $eventResolvers
     */
    public function __construct(
        array $eventResolvers = []
    ) {
        $this->eventResolvers = $eventResolvers;
    }

    /**
     * Retrieve a event resolvers list
     *
     * @return EventResolverInterface[]
     */
    public function getResolvers(): array
    {
        return $this->eventResolvers;
    }

    /**
     * Retrieve a specific event resolver by event type.
     *
     * @param string $type
     * @return EventResolverInterface|null
     */
    public function getResolver($type): ?EventResolverInterface
    {
        return $this->eventResolvers[$type] ?? null;
    }
}