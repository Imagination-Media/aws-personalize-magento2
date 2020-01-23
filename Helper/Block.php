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

namespace ImaginationMedia\AwsPersonalize\Helper;

use Magento\Customer\Model\Context as CustomerContext;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Http\Context as HttpContext;

class Block extends AbstractHelper
{
    /**
     * @var HttpContext
     */
    protected $httpContext;

    /**
     * Block constructor.
     * @param Context $context
     * @param HttpContext $httpContext
     */
    public function __construct(
        Context $context,
        HttpContext $httpContext
    ) {
        parent::__construct($context);
        $this->httpContext = $httpContext;
    }

    /**
     * Is customer logged in
     * @return bool
     */
    public function isCustomerLoggedIn() : bool
    {
        return (bool)$this->httpContext->getValue(CustomerContext::CONTEXT_AUTH);
    }
}
