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

namespace ImaginationMedia\AwsPersonalize\Controller\Recommendation;

use ImaginationMedia\AwsPersonalize\Model\Api;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

class Index extends Action
{
    /**
     * @var Api
     */
    protected $api;

    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * @var Session
     */
    protected $session;

    /**
     * Index constructor.
     * @param Context $context
     * @param Api $api
     * @param JsonFactory $jsonFactory
     * @param Session $session
     */
    public function __construct(
        Context $context,
        Api $api,
        JsonFactory $jsonFactory,
        Session $session
    ) {
        parent::__construct($context);
        $this->api = $api;
        $this->jsonFactory = $jsonFactory;
        $this->session = $session;
    }

    /**
     * @return Json
     */
    public function execute() : Json
    {
        $data = $this->getRequest()->getParams();

        $result = $this->jsonFactory->create();

        try {
            /**
             * Get recommendation based on a product (Magento related products) for an user
             */
            if (isset($data['itemId'])) {
                $items = $this->api->getProductRecommendations([
                    'itemId' => (string)$data['itemId'],
                    'userId' => (string)$this->session->getCustomerId()
                ]);
                $result->setData($items);
            } else {
                /**
                 * Get recommendations for an user
                 */
                $items = $this->api->getProductRecommendations(['userId' => (int)$this->session->getCustomerId()]);
                $result->setData($items);
            }
        } catch (\Exception $ex) {
            $result->setData(['error' => $ex->getMessage()]);
        }

        return $result;
    }
}
