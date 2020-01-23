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

namespace ImaginationMedia\AwsPersonalize\Model\System\Config;

use ImaginationMedia\AwsPersonalize\Model\System\Config\Base as GeneralBase;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Export extends GeneralBase
{
    /**
     * @var string|null
     */
    protected $bucketName;

    /**
     * @var string|null
     */
    protected $filePath;

    /**
     * @var string|null
     */
    protected $datasetArn;

    /**
     * @var string|null
     */
    protected $jobName;

    /**
     * @var string|null
     */
    protected $roleArn;

    /**
     * Export constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param string|null $bucketName
     * @param string|null $filePath
     * @param string|null $datasetArn
     * @param string|null $jobName
     * @param string|null $roleArn
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        string $bucketName = null,
        string $filePath = null,
        string $datasetArn = null,
        string $jobName = null,
        string $roleArn = null
    ) {
        parent::__construct($scopeConfig);
        $this->bucketName = $bucketName;
        $this->filePath = $filePath;
        $this->datasetArn = $datasetArn;
        $this->jobName = $jobName;
        $this->roleArn = $roleArn;
    }

    /**
     * Get bucket name
     * @return string
     */
    public function getBucketName() : string
    {
        if (is_null($this->bucketName)) {
            throw new \Error(__("Not valid value for bucketName."));
        }

        return (string)$this->getConfigValue($this->bucketName);
    }

    /**
     * Get the file path to the csv file
     * @return string
     */
    public function getFilepath() : string
    {
        if (is_null($this->filePath)) {
            throw new \Error(__("Not valid value for filePath."));
        }

        return (string)$this->getConfigValue($this->filePath);
    }

    /**
     * Get data location config value
     * @return string
     */
    public function getDataLocation() : string
    {
        return "s3://" . $this->getBucketName() . "/" . $this->getFilepath();
    }

    /**
     * Get dataset arn config value
     * @return string
     */
    public function getDataSetArn() : string
    {
        if (is_null($this->datasetArn)) {
            throw new \Error(__("Not valid value for datasetArn."));
        }

        return (string)$this->getConfigValue($this->datasetArn);
    }

    /**
     * Get job name config value
     * @return string
     */
    public function getJobName() : string
    {
        if (is_null($this->jobName)) {
            throw new \Error(__("Not valid value for jobName."));
        }

        return (string)$this->getConfigValue($this->jobName) . "-" . date('YmdHis');
    }

    /**
     * Get roleArn config value
     * @return string
     */
    public function getRoleArn() : string
    {
        if (is_null($this->roleArn)) {
            throw new \Error(__("Not valid value for roleArn."));
        }

        return (string)$this->getConfigValue($this->roleArn);
    }
}
