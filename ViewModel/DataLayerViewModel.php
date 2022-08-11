<?php
/**
 * @author andy
 * @email andyworkbase@gmail.com
 * @team MageCloud
 * @package MageCloud_EnhancedEcommerce
 */
declare(strict_types=1);

namespace MageCloud\EnhancedEcommerce\ViewModel;

use MageCloud\EnhancedEcommerce\Model\EventManager;
use MageCloud\EnhancedEcommerce\Model\EventManagerFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * Class DataLayerViewModel
 * @package MageCloud\EnhancedEcommerce\ViewModel
 */
class DataLayerViewModel extends DataObject implements ArgumentInterface
{
    /**
     * @var EventManagerFactory
     */
    protected $eventManagerFactory;

    /**
     * @var null|string
     */
    private $eventType = null;

    /**
     * @param EventManagerFactory $eventManagerFactory
     * @param $eventType
     * @param array $data
     */
    public function __construct(
        EventManagerFactory $eventManagerFactory,
        $eventType = null,
        array $data = []
    ) {
        parent::__construct(
            $data
        );
        $this->eventManagerFactory = $eventManagerFactory;
        $this->eventType = $eventType;
    }

    /**
     * @param array $eventArguments
     * @return string
     * @throws NoSuchEntityException
     */
    public function execute(array $eventArguments = []): string
    {
        /** @var EventManager $eventManager */
        $eventManager = $this->eventManagerFactory->create(
            [
                'eventArguments' => array_merge(['event_type' => $this->eventType], $eventArguments)
            ]
        );
        return $eventManager->initEvent();
    }
}