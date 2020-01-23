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
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\ResourceModel\Iterator;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class Product extends Base implements DatasetInterface
{
    /**
     * @var ProductCollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var CategoryCollectionFactory
     */
    protected $categoryCollectionFactory;

    /**
     * @var Iterator
     */
    protected $iterator;

    /**
     * @var array
     */
    protected $categories = [];

    /**
     * Product constructor.
     * @param ResourceConnection $resourceConnection
     * @param TimezoneInterface $timezone
     * @param BaseConfig $baseConfig
     * @param BaseExport $exportModel
     * @param ProductCollectionFactory $productCollectionFactory
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param Iterator $iterator
     * @param string $csvDelimiter
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        TimezoneInterface $timezone,
        BaseConfig $baseConfig,
        BaseExport $exportModel,
        ProductCollectionFactory $productCollectionFactory,
        CategoryCollectionFactory $categoryCollectionFactory,
        Iterator $iterator,
        string $csvDelimiter = ","
    ) {
        parent::__construct($resourceConnection, $timezone, $baseConfig, $exportModel, $csvDelimiter);
        $this->productCollectionFactory = $productCollectionFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->iterator = $iterator;
    }

    /**
     * Get category names
     * @param string $path
     * @return string
     */
    private function getParenCategoryName($path = '') : string
    {
        $parentName = '';
        $rootCats = [1, 2];

        $catTree = explode("/", $path);

        array_pop($catTree);

        if ($catTree && (count($catTree) > count($rootCats))) {
            foreach ($catTree as $catId) {
                if (!in_array((int)$catId, $rootCats)) {
                    $category = $this->categories[(int)$catId];
                    $categoryName = $category['name'];

                    if ($catId !== end($catTree)) {
                        $parentName .= $categoryName . ' > ';
                    } else {
                        $parentName .= $categoryName;
                    }
                }
            }
        }

        return $parentName;
    }

    /**
     *
     * @return array
     * @throws LocalizedException
     */
    public function prepareData(): array
    {
        $finalProducts = [];

        /**
         * @var $products ProductCollection
         */
        $products = $this->productCollectionFactory->create()
            ->addAttributeToSelect([
                "name",
                "meta_keyword",
                "price"
            ], 'left')->addAttributeToFilter(
                "visibility",
                ['in' => [
                    Visibility::VISIBILITY_BOTH,
                    Visibility::VISIBILITY_IN_CATALOG,
                    Visibility::VISIBILITY_IN_SEARCH
                ]]
            );

        /**
         * Load categories
         */
        $collection = $this->categoryCollectionFactory->create()
            ->addAttributeToSelect(["name", "path"], "inner");
        $this->iterator->walk(
            $collection->getSelect(),
            [
                [$this, 'processCategories']
            ]
        );

        /**
         * Process products
         */
        $this->iterator->walk(
            $products->getSelect(),
            [
                [$this, 'processProduct']
            ],
            [
                'products' => &$finalProducts
            ]
        );

        return $finalProducts;
    }

    /**
     * Get all category ids that a product is associated to
     * @param int $productId
     * @return array
     */
    protected function getCategoryIds(int $productId) : array
    {
        $categories = [];

        $tableName = "catalog_category_product";
        $selectQuery = $this->connection->select()
            ->from(
                $tableName,
                [
                    "category_id"
                ]
            )->where($tableName . ".product_id = " . $productId);

        foreach ($this->connection->fetchAll($selectQuery) as $item) {
            $categories[] = (int)$item['category_id'];
        }

        return $categories;
    }

    /**
     * Add category data to our categories array
     * @param array $args
     */
    public function processCategories(array $args) : void
    {
        $category = $args['row'];

        $this->categories[(int)$category['entity_id']] = [
            'path' => $category['path'],
            'name' => $category['name']
        ];
    }

    /**
     * Process products
     * @param array $args
     */
    public function processProduct(array $args) : void
    {
        $product = $args['row'];
        $products = &$args['products'];
        $currentProduct = [];
        $currentProduct['ITEM_ID'] = (string)$product['entity_id'];
        $currentProduct['PRICE'] = (double)$product['price'];
        $currentProduct['NAME'] = (string)$product['name'];
        $currentProduct['KEYS'] = ((string)$product['meta_keyword'] === "")
            ? "Empty" : (string)$product['meta_keyword'];

        /**
         * Get categories
         */
        $categories = $this->getCategoryIds((int)$product['entity_id']);
        $categoryNames = "";
        foreach ($categories as $categoryId) {
            $category = $this->categories[$categoryId];

            if ($categoryNames === "") {
                $categoryNames = $this->getParenCategoryName($category['path']);
            } else {
                $categoryNames .= " | " . $this->getParenCategoryName($category['path']);
            }
        }
        $currentProduct['CATEGORIES'] = ($categoryNames !== "") ? $categoryNames : "Empty";

        $products[] = $currentProduct;
    }
}
