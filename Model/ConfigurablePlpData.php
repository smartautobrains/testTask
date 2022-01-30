<?php
/**
 * @category    Smartautobrains
 * @package     TestTask
 * @author      P.Kushnerevich <pkushnerevich@smartautobrains.com>
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @copyright   Copyright (c) 2022 Smartautobrains (https://smartautobrains.com/)
 */

declare(strict_types=1);

namespace Smartautobrains\TestTask\Model;

use Exception;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\DataObject;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Smartautobrains\TestTask\Setup\Patch\Data\CreatePlpDataAttribute;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;

/**
 * Class ConfigurablePlpData
 */
class ConfigurablePlpData
{
    /**
     * Plp data information attributes
     */
    public const MIN_PRICE_PLP_DATA_ATTRIBUTE = 'min_price';
    public const MAX_PRICE_PLP_DATA_ATTRIBUTE = 'max_price';

    /**
     * Data object factory
     *
     * @var DataObjectFactory
     */
    private DataObjectFactory $dataObjectFactory;

    /**
     * Serializer
     *
     * @var Json
     */
    private Json $serializer;

    /**
     * Product resource model
     *
     * @var ProductResource
     */
    private ProductResource $productResource;

    /**
     * Logger
     *
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * ConfigurablePlpData constructor.
     *
     * @param DataObjectFactory $dataObjectFactory
     * @param Json $serializer
     * @param ProductResource $productResource
     * @param LoggerInterface $logger
     */
    public function __construct(
        DataObjectFactory $dataObjectFactory,
        Json $serializer,
        ProductResource $productResource,
        LoggerInterface $logger
    ) {
        $this->dataObjectFactory = $dataObjectFactory;
        $this->serializer = $serializer;
        $this->productResource = $productResource;
        $this->logger = $logger;
    }

    /**
     * Get plp data information
     *
     * @param ProductInterface $product
     * @return DataObject
     */
    public function getPlpDataInformation(ProductInterface $product): DataObject
    {
        $plpData = $product->getData(CreatePlpDataAttribute::ATTRIBUTE_CODE);

        if (!empty($plpData)) {
            if (!is_array($plpData)) {
                try {
                    $plpData = $this->serializer->unserialize($plpData);
                } catch (Exception $exception) {
                    $plpData = [];
                }
            }
        } else {
            $plpData = [];
        }

        $result = $this->dataObjectFactory->create();

        $result->setData(self::MIN_PRICE_PLP_DATA_ATTRIBUTE, $plpData[self::MIN_PRICE_PLP_DATA_ATTRIBUTE] ?? 0);
        $result->setData(self::MAX_PRICE_PLP_DATA_ATTRIBUTE, $plpData[self::MAX_PRICE_PLP_DATA_ATTRIBUTE] ?? 0);

        return $result;
    }

    /**
     * Set plp data information
     *
     * @param ProductInterface $product
     * @param float $minPrice
     * @param float $maxPrice
     */
    public function setPlpDataInformation(ProductInterface $product, float $minPrice, float $maxPrice): void
    {
        $plpData = [
            self::MIN_PRICE_PLP_DATA_ATTRIBUTE => $minPrice,
            self::MAX_PRICE_PLP_DATA_ATTRIBUTE => $maxPrice
        ];

        try {
            $plpData = $this->serializer->serialize($plpData);
        } catch (Exception $exception) {
            $plpData = $this->serializer->serialize([]);
        }

        $product->setCustomAttribute(CreatePlpDataAttribute::ATTRIBUTE_CODE, $plpData);

        try {
            $this->productResource->saveAttribute($product, CreatePlpDataAttribute::ATTRIBUTE_CODE);
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
        }
    }
}
