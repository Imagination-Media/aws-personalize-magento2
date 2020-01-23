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

namespace ImaginationMedia\AwsPersonalize\Block;

use ImaginationMedia\AwsPersonalize\Model\System\Config\Base as BaseConfig;
use Magento\Customer\Model\Context as CustomerContext;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\View\Element\Template;

class ProductRecommendation extends Template
{
    /**
     * @var HttpContext
     */
    protected $httpContext;

    /**
     * @var BaseConfig
     */
    protected $baseConfig;

    /**
     * ProductRecommendation constructor.
     * @param Template\Context $context
     * @param HttpContext $httpContext
     * @param BaseConfig $baseConfig
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        HttpContext $httpContext,
        BaseConfig $baseConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->httpContext = $httpContext;
        $this->baseConfig = $baseConfig;
    }

    /**
     * Check if current session is allowed or not to show product recommendations from AWS Personalize
     * @return bool
     */
    public function canShowRecommendations() : bool
    {
        $isLoggedIn = (bool)$this->httpContext->getValue(CustomerContext::CONTEXT_AUTH);
        $isRecommendationEnabled = $this->baseConfig->isRecommendationEnabled();

        return $isLoggedIn && $isRecommendationEnabled;
    }
}
