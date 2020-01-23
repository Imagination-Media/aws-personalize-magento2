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

namespace ImaginationMedia\AwsPersonalize\Api\Export;

/**
 * Interface Dataset
 * @package ImaginationMedia\AwsPersonalize\Api\Export
 */
interface DatasetInterface
{
    /**
     * Prepare data to be exported
     * @return array
     */
    public function prepareData() : array;

    /**
     * Export data to AWS dataset
     * @param array $data
     * @return bool
     */
    public function exportToAws(array $data)  : bool;
}
