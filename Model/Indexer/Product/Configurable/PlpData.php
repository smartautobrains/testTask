<?php
/**
 * @category    Smartautobrains
 * @package     TestTask
 * @author      P.Kushnerevich <pkushnerevich@smartautobrains.com>
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @copyright   Copyright (c) 2022 Smartautobrains (https://smartautobrains.com/)
 */

declare(strict_types=1);

namespace Smartautobrains\TestTask\Model\Indexer\Product\Configurable;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Type\AbstractType;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Indexer\ActionInterface as IndexerActionInterface;
use Magento\Framework\Mview\ActionInterface as MviewActionInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Smartautobrains\TestTask\Model\ConfigurablePlpData;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable as ConfigurableResource;

/**
 * Class PlpData
 */
class PlpData implements IndexerActionInterface, MviewActionInterface
{
    /**
     * @var ProductCollectionFactory
     */
    private ProductCollectionFactory $collectionFactory;

    /**
     * @var ConfigurablePlpData
     */
    private ConfigurablePlpData $configurablePlpData;

    /**
     * @var ConfigurableResource
     */
    private ConfigurableResource $configurableResource;

    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;

    /**
     * @param ProductCollectionFactory $collectionFactory
     * @param ConfigurablePlpData $configurablePlpData
     * @param ConfigurableResource $configurableResource
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        ProductCollectionFactory $collectionFactory,
        ConfigurablePlpData $configurablePlpData,
        ConfigurableResource $configurableResource,
        ProductRepositoryInterface $productRepository
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->configurablePlpData = $configurablePlpData;
        $this->configurableResource = $configurableResource;
        $this->productRepository = $productRepository;
    }

    /**
     * Execute full
     */
    public function executeFull(): void
    {
        $productCollection = $this->getConfigurableProducts();
        /**
         * @var $configurableProduct ProductInterface
         */
        foreach ($productCollection as $configurableProduct) {
            $this->recalculatePlpData($configurableProduct);
        }
    }

    /**
     * Execute list
     *
     * @param array $ids
     */
    public function executeList(array $ids): void
    {
        $parentIds = $this->configurableResource->getParentIdsByChild($ids);

        if ($parentIds) {
            $productCollection = $this->getConfigurableProducts($parentIds);
            /**
             * @var $configurableProduct ProductInterface
             */
            foreach ($productCollection as $configurableProduct) {
                $this->recalculatePlpData($configurableProduct);
            }
        }
    }

    /**
     * Execute row
     *
     * @param int $id
     * @throws NoSuchEntityException
     */
    public function executeRow($id): void
    {
        $product = $this->productRepository->getById($id);
        $parentIds = [];

        if ($product->getTypeId() === Type::TYPE_SIMPLE) {
            $parentIds = $this->configurableResource->getParentIdsByChild($id);
        } elseif ($product->getTypeId() === Configurable::TYPE_CODE) {
            $parentIds = [$id];
        }

        if ($parentIds) {
            $productCollection = $this->getConfigurableProducts($parentIds);
            /**
             * @var $configurableProduct ProductInterface
             */
            foreach ($productCollection as $configurableProduct) {
                $this->recalculatePlpData($configurableProduct);
            }
        }
    }

    /**
     * Execute reindex
     *
     * @param int[] $ids
     */
    public function execute($ids): void
    {
        $parentIds = $this->configurableResource->getParentIdsByChild($ids);
        $parents = $this->getConfigurableProducts($ids);

        /**
         * @var $configurableProduct ProductInterface
         */
        foreach ($parents as $configurableProduct) {
            $this->recalculatePlpData($configurableProduct);
        }

        if ($parentIds) {
            $productCollection = $this->getConfigurableProducts($parentIds);

            /**
             * @var $configurableProduct ProductInterface
             */
            foreach ($productCollection as $configurableProduct) {
                $this->recalculatePlpData($configurableProduct);
            }
        }
    }

    /**
     * Get children
     *
     * @param ProductInterface $configurableProduct
     * @return array
     */
    private function getChildren(ProductInterface $configurableProduct): array
    {
        /**
         * @var $productTypeInstance AbstractType
         */
        $productTypeInstance = $configurableProduct->getTypeInstance();
        $childrenPriceList = [];

        foreach ($productTypeInstance->getUsedProducts($configurableProduct) as $child) {
            if ($child->getStatus() == Status::STATUS_ENABLED) {
                $childrenPriceList[] = $child->getPrice();
            }
        }

        return $childrenPriceList;
    }

    /**
     * Recalculate plp data attribute
     *
     * @param ProductInterface $configurableProduct
     */
    private function recalculatePlpData(ProductInterface $configurableProduct): void
    {
        $children = $this->getChildren($configurableProduct);

        $maxPrice = (float)max($children);
        $minPrice = (float)min($children);

        $plpData = $this->configurablePlpData->getPlpDataInformation($configurableProduct);

        if (
            $plpData->getData(ConfigurablePlpData::MAX_PRICE_PLP_DATA_ATTRIBUTE) != $maxPrice
            && $plpData->getData(ConfigurablePlpData::MIN_PRICE_PLP_DATA_ATTRIBUTE) !== $minPrice
        ) {
            $this->configurablePlpData->setPlpDataInformation($configurableProduct, $minPrice, $maxPrice);
        }
    }

    /**
     * Get configurable products
     *
     * @param array|null $configurableIds
     * @return Collection
     */
    private function getConfigurableProducts(?array $configurableIds = null): Collection
    {
        $productCollection = $this->collectionFactory->create();
        $productCollection->addFieldToFilter(
            ProductInterface::TYPE_ID,
            [
                'eq' => [
                    Configurable::TYPE_CODE
                ]
            ]
        );

        if ($configurableIds) {
            $productCollection->addFieldToFilter('entity_id', ['in' => $configurableIds]);
        }

        return $productCollection;
    }
}
