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

namespace ImaginationMedia\AwsPersonalize\Model;

use Aws\PersonalizeEvents\PersonalizeEventsClient;
use Aws\PersonalizeRuntime\PersonalizeRuntimeClient;
use ImaginationMedia\AwsPersonalize\Model\System\Config\Base as BaseConfig;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;

class Api
{
    /**
     * @var PersonalizeRuntimeClient
     */
    protected $personalizeRuntimeClient;

    /**
     * @var PersonalizeEventsClient
     */
    protected $personalizeEvents;

    /**
     * @var BaseConfig
     */
    protected $baseConfig;

    /**
     * @var AdapterInterface
     */
    protected $connection;

    /**
     * @var ProductCollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var ImageHelper
     */
    protected $imageHelper;

    /**
     * @var PriceHelper
     */
    protected $priceHelper;

    /**
     * Api constructor.
     * @param BaseConfig $baseConfig
     * @param ResourceConnection $resourceConnection
     * @param ProductCollectionFactory $productCollectionFactory
     * @param ImageHelper $imageHelper
     * @param PriceHelper $priceHelper
     */
    public function __construct(
        BaseConfig $baseConfig,
        ResourceConnection $resourceConnection,
        ProductCollectionFactory $productCollectionFactory,
        ImageHelper $imageHelper,
        PriceHelper $priceHelper
    ) {
        $this->baseConfig = $baseConfig;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->imageHelper = $imageHelper;
        $this->priceHelper = $priceHelper;
        $this->connection = $resourceConnection->getConnection();

        $this->personalizeRuntimeClient = new PersonalizeRuntimeClient([
            'profile' => $this->baseConfig->getAwsProfile(),
            'version' => $this->baseConfig->getAwsVersion(),
            'region' => $this->baseConfig->getAwsRegion()
        ]);
        $this->personalizeEvents = new PersonalizeEventsClient([
            'profile' => $this->baseConfig->getAwsProfile(),
            'version' => $this->baseConfig->getAwsVersion(),
            'region' => $this->baseConfig->getAwsRegion()
        ]);
    }

    /**
     * Get product collection based on aws personalize suggestions
     * @param array $params
     * @return array|null
     */
    public function getProductRecommendations(array $params): ?array
    {
        $params = array_merge(
            $params,
            [
                'campaignArn' => $this->baseConfig->getCampaignArn()
            ]
        );

        /**
         * Force all values to be a string value
         */
        foreach ($params as $key => $value) {
            $params[$key] = (string)$value;
        }

        $recommendations = $this->personalizeRuntimeClient->getRecommendations($params);
        $ids = [];

        if (isset($recommendations['itemList'])) {
            foreach ($recommendations['itemList'] as $recommendation) {
                if (!in_array((int)$recommendation['itemId'], $ids)) {
                    $ids[] = (int)$recommendation['itemId'];
                }
            }
        }

        if (count($ids) > 0) {
            $collection = $this->productCollectionFactory->create()
                ->addAttributeToSelect([
                    "name",
                    "url_key",
                    "image",
                    "sku"
                ], "left")
                ->addAttributeToFilter("entity_id", ['in' => $ids]);

            /**
             * @var $collection ProductCollection
             * @var $product Product
             */
            $finalProducts = [];
            foreach ($collection as $product) {
                $finalProducts[(int)$product->getId()] = [
                    "id" => $product->getId(),
                    "name" => $product->getName(),
                    "sku" => $product->getSku(),
                    "price" => $product->getFinalPrice(),
                    "price_with_currency" => $this->priceHelper->currency($product->getFinalPrice(), true, false),
                    "url" => $product->getProductUrl(),
                    "image" => $this->imageHelper->init($product, 'product_base_image')->getUrl()
                ];
            }

            return $finalProducts;
        }
    }

    /**
     * Publish event in AWS Personalize when a new order is created
     * @param string $trackingId
     * @param string $eventType
     * @param string $userId
     * @param string $sessionId
     * @param array $interactions
     * @return bool
     */
    public function publishNewOrderInteraction(
        string $trackingId,
        string $eventType,
        string $userId,
        string $sessionId,
        array $interactions
    ): bool {
        $eventList = [];

        foreach ($interactions as $interaction) {
            $eventList[] = [
                'sentAt' => $interaction['TIMESTAMP'],
                'eventType' => $eventType,
                'properties' => json_encode([
                    "itemId" => $interaction['ITEM_ID']
                ])
            ];
        }

        $result = $this->personalizeEvents->putEvents([
            'trackingId' => $trackingId,
            'userId' => $userId,
            'sessionId' => $sessionId,
            'eventList' => $eventList
        ]);
        $metadata = $result->get('@metadata');
        return (isset($metadata['statusCode']) && (int)$metadata['statusCode'] === 200);
    }
}
