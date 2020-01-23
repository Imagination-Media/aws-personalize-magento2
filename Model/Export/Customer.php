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

class Customer extends Base implements DatasetInterface
{
    /**
     * Prepare customers data
     * @return array
     * @throws \Exception
     */
    public function prepareData(): array
    {
        $finalCustomers = [];

        $customerTable = $this->connection->getTableName("customer_entity");
        $customerAddressTable = $this->connection->getTableName("customer_address_entity");
        $customerVisitorTable = $this->connection->getTableName("customer_visitor");

        /**
         * Get basic customer info
         */
        $customerQuery = $this->connection->select()
            ->from(
                $customerTable,
                [
                    'entity_id',
                    'group_id',
                    'updated_at',
                    'dob',
                    'gender',
                    'default_shipping'
                ]
            )->where($customerTable . ".is_active = ?", 1);

        /**
         * Join address
         */
        $customerQuery->join(
            $customerAddressTable,
            $customerAddressTable . ".entity_id = " . $customerTable . ".entity_id",
            [
                "region"
            ]
        );

        /**
         * Join customer visitor table
         */
        $customerQuery->joinLeft(
            $customerVisitorTable,
            $customerVisitorTable . ".customer_id = " . $customerTable . ".entity_id AND " .
            $customerVisitorTable . ".visitor_id IN (" .
            $this->connection->select()
                ->from(
                    $customerVisitorTable . " AS visitor_aux",
                    "MAX(visitor_aux.visitor_id)"
                )
                ->group("visitor_aux.customer_id")
            . ")",
            [
                "visitor_id",
                "last_visit_at"
            ]
        );

        $customers = $this->connection->fetchAll($customerQuery);

        foreach ($customers as $customer) {
            $lastVisitAt = (is_null($customer['last_visit_at']))
                ? new \DateTime(
                    $customer['updated_at'],
                    new \DateTimeZone($this->timezone->getConfigTimezone())
                )
                : new \DateTime(
                    $customer['last_visit_at'],
                    new \DateTimeZone($this->timezone->getConfigTimezone())
                );
            $dob = (!is_null($customer['dob'])) ? new \DateTime(
                (string)$customer['dob'],
                new \DateTimeZone($this->timezone->getConfigTimezone())
            ) : null;

            $finalCustomers[] = [
                'USER_ID' => (string)$customer['entity_id'],
                'GENDER' => (int)$customer['gender'],
                'LAST_VISIT_AT' => $lastVisitAt->getTimestamp(),
                'STATE' => (string)$customer['region'],
                'DATE_OF_BIRTH' => (!is_null($dob)) ? $dob->getTimestamp() : 0,
                'GROUP_ID' => (int)$customer['group_id']
            ];
        }

        return $finalCustomers;
    }
}
