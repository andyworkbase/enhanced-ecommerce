<?php
/**
 * @author andy
 * @email andyworkbase@gmail.com
 * @team MageCloud
 * @package MageCloud_EnhancedEcommerce
 */
declare(strict_types=1);

namespace MageCloud\EnhancedEcommerce\Model;

use Magento\Framework\Session\SessionManager;

/**
 * Class EventSession
 * @package MageCloud\EnhancedEcommerce\Model
 */
class EventSession extends SessionManager
{
    /**
     * Event session prefix key for storage
     */
    const EVENT_SESSION_KEY_PREFIX = 'magecloud';

    /**
     * @param string $key
     * @return string
     */
    private function getEventSessionDataKey($key): string
    {
        return sprintf('%s_%s', self::EVENT_SESSION_KEY_PREFIX, $key);
    }

    /**
     * @param string $key
     * @param bool $clear
     * @return mixed
     */
    public function getEventSessionData($key, $clear = true)
    {
        return $this->getData($this->getEventSessionDataKey($key), $clear);
    }

    /**
     * @param string $key
     * @param string $data
     * @return EventSession
     */
    public function setEventSessionData($key, $data): EventSession
    {
        $this->setData($this->getEventSessionDataKey($key), $data);
        return $this;
    }
}