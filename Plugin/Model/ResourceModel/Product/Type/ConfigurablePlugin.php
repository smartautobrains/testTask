<?php
/**
 * @category    Smartautobrains
 * @package     TestTask
 * @author      P.Kushnerevich <pkushnerevich@smartautobrains.com>
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @copyright   Copyright (c) 2022 Smartautobrains (https://smartautobrains.com/)
 */

declare(strict_types=1);

namespace Smartautobrains\TestTask\Plugin\Model\ResourceModel\Product\Type;

use Magento\Catalog\Model\Product as ProductModel;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Magento\ConfigurableProduct\Model\ResourceModel\Attribute\OptionProvider;
use Magento\Framework\Exception\LocalizedException;
use Smartautobrains\TestTask\Model\Indexer\Product\Configurable\Processor;
use Magento\Customer\Model\Session;

/**
 * Class ConfigurablePlugin
 */
class ConfigurablePlugin
{
    /**
     * Session key for attribute updating
     */
    private const IS_NEED_UPDATE_ATTRIBUTE = 'is_need_update_attribute';

    /**
     * @var OptionProvider
     */
    private OptionProvider $optionProvider;

    /**
     * @var Processor
     */
    private Processor $processor;

    /**
     * @var Session
     */
    private Session $customerSession;

    /**
     * @param OptionProvider $optionProvider
     * @param Processor $processor
     * @param Session $customerSession
     */
    public function __construct(
        OptionProvider $optionProvider,
        Processor $processor,
        Session $customerSession
    ) {
        $this->optionProvider = $optionProvider;
        $this->processor = $processor;
        $this->customerSession = $customerSession;
    }

    /**
     * Before save products
     *
     * @param Configurable $subject
     * @param ProductModel $mainProduct
     * @param array $productIds
     * @throws LocalizedException
     *
     * @see Configurable::saveProducts()
     */
    public function beforeSaveProducts(
        Configurable $subject,
        ProductModel $mainProduct,
        array $productIds
    ): void {
        $linksField = $this->optionProvider->getProductEntityLinkField();
        $productId = $mainProduct->getData($linksField);
        $connection = $subject->getConnection();
        $select = $connection->select();
        $select = $select->from(
            ['t' => $subject->getMainTable()],
            ['product_id']
        );
        $select->where(
            't.parent_id = ?',
            $productId
        );

        $existingProductIds = $connection->fetchCol($select);
        $insertProductIds = array_diff($productIds, $existingProductIds);
        $deleteProductIds = array_diff($existingProductIds, $productIds);

        if ($insertProductIds || $deleteProductIds) {
            $this->customerSession->setData(self::IS_NEED_UPDATE_ATTRIBUTE, true);
        }
    }

    /**
     * Reindex plp_data attribute after add/remove children
     *
     * @param Configurable $subject
     * @param Configurable $result
     * @param ProductModel $mainProduct
     * @param array $productIds
     * @return Configurable
     *
     * @see Configurable::saveProducts()
     */
    public function afterSaveProducts(
        Configurable $subject,
        Configurable $result,
        ProductModel $mainProduct,
        array $productIds
    ): Configurable {
        if ($this->customerSession->getData(self::IS_NEED_UPDATE_ATTRIBUTE)) {
            $this->processor->reindexRow($mainProduct->getId());
            $this->customerSession->unsetData(self::IS_NEED_UPDATE_ATTRIBUTE);
        }

        return $result;
    }
}
