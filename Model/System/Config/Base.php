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

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Base
{
    const SYSTEM_CONFIG_AWS_PROFILE = "personalize/general/profile";
    const SYSTEM_CONFIG_AWS_VERSION = "personalize/general/version";
    const SYSTEM_CONFIG_AWS_REGION = "personalize/general/region";
    const SYSTEM_CONFIG_CAMPAIGN_ARN = "personalize/general/campaignArn";

    const SYSTEM_CONFIG_RECOMMENDATION_ENABLE = "personalize/recommendation/recommendation/enable";

    const SYSTEM_CONFIG_RELATED_ENABLE = "personalize/recommendation/related/enable";

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Base constructor.
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get value from admin config
     * @param string $path
     * @return string
     */
    protected function getConfigValue(string $path) : string
    {
        return (string)$this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get flag value from admin config
     * @param string $path
     * @return bool
     */
    protected function getFlagValue(string $path) : bool
    {
        return $this->scopeConfig->isSetFlag($path, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get aws region
     * @return string
     */
    public function getAwsRegion() : string
    {
        $region = $this->getConfigValue(self::SYSTEM_CONFIG_AWS_REGION);
        return ($region !== "") ? $region : "us-east-2";
    }

    /**
     * Get Aws profile
     * @return string
     */
    public function getAwsProfile() : string
    {
        $profile = $this->getConfigValue(self::SYSTEM_CONFIG_AWS_PROFILE);
        return ($profile !== "") ? $profile : "default";
    }

    /**
     * Get aws version
     * @return string
     */
    public function getAwsVersion() : string
    {
        $version = $this->getConfigValue(self::SYSTEM_CONFIG_AWS_VERSION);
        return ($version !== "") ? $version : "latest";
    }

    /**
     * Is product recommendation going to be shown for customers?
     * @return bool
     */
    public function isRecommendationEnabled() : bool
    {
        return $this->getFlagValue(self::SYSTEM_CONFIG_RECOMMENDATION_ENABLE);
    }

    /**
     * Get campaign arn used for related/recommended products
     * @return string
     */
    public function getCampaignArn() : string
    {
        return $this->getConfigValue(self::SYSTEM_CONFIG_CAMPAIGN_ARN);
    }
}
