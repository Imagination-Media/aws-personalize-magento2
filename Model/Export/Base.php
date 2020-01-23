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

use Aws\Personalize\PersonalizeClient;
use Aws\S3\S3Client;
use ImaginationMedia\AwsPersonalize\Model\System\Config\Base as BaseConfig;
use ImaginationMedia\AwsPersonalize\Model\System\Config\Export as BaseExport;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

abstract class Base
{
    /**
     * @var AdapterInterface
     */
    protected $connection;

    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    /**
     * @var PersonalizeClient
     */
    protected $personalizeClient;

    /**
     * @var BaseConfig
     */
    protected $baseConfig;

    /**
     * @var BaseExport
     */
    protected $exportModel;

    /**
     * @var S3Client
     */
    protected $s3Client;

    /**
     * @var string
     */
    protected $csvDelimiter;

    /**
     * Base constructor.
     * @param ResourceConnection $resourceConnection
     * @param TimezoneInterface $timezone
     * @param BaseConfig $baseConfig
     * @param BaseExport $exportModel
     * @param string $csvDelimiter
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        TimezoneInterface $timezone,
        BaseConfig $baseConfig,
        BaseExport $exportModel,
        string $csvDelimiter = ","
    ) {
        $this->baseConfig = $baseConfig;
        $this->timezone = $timezone;
        $this->exportModel = $exportModel;
        $this->csvDelimiter = $csvDelimiter;

        /**
         * Init AWS S3 Client
         */
        $this->s3Client = new S3Client([
            'version' => $this->baseConfig->getAwsVersion(),
            'region'  => $this->baseConfig->getAwsRegion()
        ]);

        /**
         * Initialize AWS Personalize client
         */
        $this->personalizeClient = new PersonalizeClient([
            'profile' => $this->baseConfig->getAwsProfile(),
            'version' => $this->baseConfig->getAwsVersion(),
            'region' => $this->baseConfig->getAwsRegion()
        ]);

        $this->connection = $resourceConnection->getConnection();
    }

    /**
     * Convert associative array data to a csv format
     * @param array $data
     * @return string
     */
    protected function getCsvFromArray(array $data) : string
    {
        $newData = [];
        $newData[0] = array_keys($data[0]);
        $newData = array_merge($newData, $data);

        $finalData = "";

        foreach ($newData as $datum) {
            $finalData .= implode($this->csvDelimiter, $datum) . "\n";
        }

        return $finalData;
    }

    /**
     * Export data to AWS dataset
     * @param array $data
     * @return bool
     */
    public function exportToAws(array $data)  : bool
    {
        if (empty($data)) {
            throw new \Error(__("Data can't be empty!"));
        }

        $csvData = $this->getCsvFromArray($data);

        /**
         * Upload csv file
         */
        $result = $this->s3Client->putObject([
            'Bucket' => $this->exportModel->getBucketName(),
            'Key'    => $this->exportModel->getFilepath(),
            'Body'   => $csvData,
            'ACL'    => 'bucket-owner-full-control'
        ]);

        if (isset($result['ObjectURL'])) {
            /**
             * Import csv file
             */
            $this->personalizeClient->createDatasetImportJob([
                'dataSource' => [
                    'dataLocation' => $this->exportModel->getDataLocation(),
                ],
                'datasetArn' => $this->exportModel->getDataSetArn(),
                'jobName' => $this->exportModel->getJobName(),
                'roleArn' => $this->exportModel->getRoleArn(),
            ]);

            return true;
        } else {
            throw new \Error(__("Not possible to upload the csv to the S3 bucket. Please check the credentials."));
        }
    }
}
