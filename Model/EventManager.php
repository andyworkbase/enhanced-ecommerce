<?php
/**
 * @author andy
 * @email andyworkbase@gmail.com
 * @team MageCloud
 * @package MageCloud_EnhancedEcommerce
 */
namespace MageCloud\EnhancedEcommerce\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class DataLayerManager
 * @package MageCloud\EnhancedEcommerce\Model
 */
class EventManager
{
    /**
     * @var EventResolverProvider
     */
    private $eventResolverProvider;

    /**
     * @var EventSession
     */
    private $eventSession;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var array
     */
    private $eventArguments = [];

    /**
     * @param EventResolverProvider $eventResolverProvider
     * @param EventSession $eventSession
     * @param StoreManagerInterface $storeManager
     * @param array $eventArguments
     */
    public function __construct(
        EventResolverProvider $eventResolverProvider,
        EventSession $eventSession,
        StoreManagerInterface $storeManager,
        array $eventArguments = []
    ) {
        $this->eventResolverProvider = $eventResolverProvider;
        $this->eventSession = $eventSession;
        $this->storeManager = $storeManager;
        $this->eventArguments = $eventArguments;
    }

    /**
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function initEvent()
    {
        $output = '';
        $eventType = $this->eventArguments['event_type'] ?? null;
        if (!$eventType) {
            return $output;
        }
        if (!isset($this->eventArguments['store'])) {
            $this->eventArguments = array_merge(['store' => $this->storeManager->getStore()], $this->eventArguments);
        }
        /** @var EventResolverInterface $resolver */
        $resolver = $this->eventResolverProvider->getResolver($eventType);
        $output = $resolver->resolve($this->eventArguments);

        $dynamic = $this->eventArguments['dynamic'] ?? false;
        if ($output && $dynamic) {
            $this->eventSession->setEventSessionData($eventType, $output);
        }

        return $output;
    }
}