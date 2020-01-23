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

namespace ImaginationMedia\AwsPersonalize\Model\Export;

use ImaginationMedia\AwsPersonalize\Api\Export\DatasetInterface;
use ImaginationMedia\AwsPersonalize\Model\Export\Base;
use ImaginationMedia\AwsPersonalize\Model\System\Config\Base as BaseConfig;
use ImaginationMedia\AwsPersonalize\Model\System\Config\Export as BaseExport;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Model\ResourceModel\Iterator;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class Interaction extends Base implements DatasetInterface
{
    /**
     * @var array
     */
    protected $allCustomers = [];

    /**
     * @var Iterator
     */
    protected $iterator;

    /**
     * Interaction constructor.
     * @param ResourceConnection $resourceConnection
     * @param TimezoneInterface $timezone
     * @param BaseConfig $baseConfig
     * @param BaseExport $exportModel
     * @param Iterator $iterator
     * @param string $csvDelimiter
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        TimezoneInterface $timezone,
        BaseConfig $baseConfig,
        BaseExport $exportModel,
        Iterator $iterator,
        string $csvDelimiter = ","
    ) {
        parent::__construct($resourceConnection, $timezone, $baseConfig, $exportModel, $csvDelimiter);
        $this->iterator = $iterator;
    }

    /**
     * Return all the interactions between customers and products
     * @return array
     * @throws \Exception
     */
    public function prepareData(): array
    {
        $orderTable = $this->connection->getTableName("sales_order");
        $orderItemTable = $this->connection->getTableName("sales_order_item");
        $customersTable = $this->connection->getTableName("customer_entity");

        /**
         * Load all customers
         */
        $customersSelect = $this->connection->select()
            ->from(
                $customersTable,
                [
                    "entity_id",
                    "email"
                ]
            );
        $this->iterator->walk(
            $customersSelect,
            [
                [$this, 'processCustomer']
            ]
        );

        /**
         * Get all interactions
         */
        $selectQuery = $this->connection->select()
            ->from(
                $orderItemTable,
                [
                    "item_id",
                    "product_id"
                ]
            )->join(
                $orderTable,
                $orderItemTable . ".order_id = " . $orderTable . ".entity_id",
                [
                    "customer_email",
                    "created_at"
                ]
            );

        $interactions = $this->connection->fetchAll($selectQuery);

        $finalInteractions = [];

        foreach ($interactions as $interaction) {
            if (key_exists($interaction['customer_email'], $this->allCustomers)) {
                $timestamp = new \DateTime(
                    $interaction['created_at'],
                    new \DateTimeZone($this->timezone->getConfigTimezone())
                );
                $finalInteractions[] = [
                    "ITEM_ID" => (string)$interaction['product_id'],
                    "USER_ID" => (string)$this->allCustomers[$interaction['customer_email']],
                    "TIMESTAMP" => $timestamp->getTimestamp()
                ];
            }
        }

        return $finalInteractions;
    }

    /**
     * Process customer data
     * @param array $args
     */
    public function processCustomer(array $args) : void
    {
        $customer = $args['row'];

        $this->allCustomers[$customer['email']] = (int)$customer['entity_id'];
    }
}
