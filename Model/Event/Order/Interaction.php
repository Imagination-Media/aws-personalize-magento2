<?php

/**
 * Amazon AWS Personalize integration (https://docs.aws.amazon.com/personalize/index.html)
 *
 * Use AWS Personalize to generate recommendations
 *
 * @package     ImaginationMedia\AwsPersonalize
 * @author      Igor Ludgero Miura <igor@imaginationmedia.com>
 * @copyright   Copyright (c) 2019 - 2020 Imagination Media (https://www.imaginationmedia.com/)
 * @license     https://opensource.org/licenses/OSL-3.0.php Open Software License 3.0
 */

declare(strict_types=1);

namespace ImaginationMedia\AwsPersonalize\Model\Event\Order;

use ImaginationMedia\AwsPersonalize\Model\System\Config\Base;

class Interaction extends Base
{
    const SYSTEM_CONFIG_EVENT_ORDER_INTERACTION_TRACKING_ID = "personalize/events/interactions/tracking_id";
    const SYSTEM_CONFIG_EVENT_ORDER_INTERACTION_EVENT_TYPE = "personalize/events/interactions/event_type";

    /**
     * Get event tracking id
     * @return string
     */
    public function getTrackingId() : string
    {
        return (string)$this->getConfigValue(self::SYSTEM_CONFIG_EVENT_ORDER_INTERACTION_TRACKING_ID);
    }

    /**
     * Get event type
     * @return string
     */
    public function getEventType() : string
    {
        return (string)$this->getConfigValue(self::SYSTEM_CONFIG_EVENT_ORDER_INTERACTION_EVENT_TYPE);
    }
}
