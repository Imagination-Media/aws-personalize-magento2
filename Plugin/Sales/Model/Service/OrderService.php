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

namespace ImaginationMedia\AwsPersonalize\Plugin\Sales\Model\Service;

use ImaginationMedia\AwsPersonalize\Model\Api;
use ImaginationMedia\AwsPersonalize\Model\Event\Order\Interaction;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Service\OrderService as Subject;

class OrderService
{
    /**
     * @var Api
     */
    protected $api;

    /**
     * @var AdapterInterface
     */
    protected $connection;

    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    /**
     * @var Interaction
     */
    protected $interactionEventConfig;

    /**
     * PlaceOrder constructor.
     * @param Api $api
     * @param ResourceConnection $resourceConnection
     * @param TimezoneInterface $timezone
     * @param Interaction $interactionEventConfig
     */
    public function __construct(
        Api $api,
        ResourceConnection $resourceConnection,
        TimezoneInterface $timezone,
        Interaction $interactionEventConfig
    ) {
        $this->api = $api;
        $this->timezone = $timezone;
        $this->interactionEventConfig = $interactionEventConfig;
        $this->connection = $resourceConnection->getConnection();
    }

    /**
     * Execute this piece of code after Magento create the order
     * @param Subject $subject
     * @param OrderInterface $order
     * @param OrderInterface $result
     * @return OrderInterface
     */
    public function afterPlace(
        Subject $subject,
        OrderInterface $order,
        OrderInterface $result
    ) : OrderInterface {
        $salesOrderTable = $this->connection->getTableName("sales_order");
        $salesOrderItemTable = $this->connection->getTableName("sales_order_item");
        $customerTable = $this->connection->getTableName("customer_entity");

        /**
         * Prepare SQL select to return sales order items from the created order
         */
        $itemsQuery = $this->connection->select()
            ->from(
                $salesOrderItemTable,
                [
                    "created_at",
                    "order_id",
                    "product_id"
                ]
            )->where($salesOrderItemTable . ".order_id = " . $order->getId());

        /**
         * Join sales_order
         */
        $itemsQuery->join(
            $salesOrderTable,
            $salesOrderTable . ".entity_id = " . $salesOrderItemTable . ".order_id",
            [
                "customer_email"
            ]
        );

        /**
         * Join customer
         */
        $itemsQuery->join(
            $customerTable,
            $customerTable . ".email LIKE " . $salesOrderTable . ".customer_email",
            [
                "entity_id AS customer_id"
            ]
        );

        try {
            $interactions = $this->connection->fetchAll($itemsQuery);

            if (count($interactions) > 0) {
                $finalInteractions = [];

                $userId = (string)$interactions[0]["customer_id"];
                $sessionId = (string)$interactions[0]["order_id"];

                foreach ($interactions as $interaction) {
                    $dateTime = new \DateTime(
                        $interaction['created_at'],
                        new \DateTimeZone($this->timezone->getConfigTimezone())
                    );

                    $finalInteractions[] = [
                        "ITEM_ID" => (string)$interaction["product_id"],
                        "TIMESTAMP" => $dateTime->getTimestamp()
                    ];
                }

                $trackingId = $this->interactionEventConfig->getTrackingId();
                $eventType = $this->interactionEventConfig->getEventType();
                /**
                 * Publish interactions in AWS Personalize
                 */
                $this->api->publishNewOrderInteraction(
                    $trackingId,
                    $eventType,
                    $userId,
                    $sessionId,
                    $finalInteractions
                );
            }
        } catch (\Exception $ex) {
            /**
             * Ignore and proceed
             */
        }

        return $result;
    }
}
