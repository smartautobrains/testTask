<?php
/**
 * @category    Smartautobrains
 * @package     TestTask
 * @author      P.Kushnerevich <pkushnerevich@smartautobrains.com>
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @copyright   Copyright (c) 2022 Smartautobrains (https://smartautobrains.com/)
 */

declare(strict_types=1);

namespace Smartautobrains\TestTask\Observer;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Smartautobrains\TestTask\Model\Indexer\Product\Configurable\Processor;

/**
 * Class RecalculatePlpData
 */
class RecalculatePlpData implements ObserverInterface
{
    /**
     * Index processor
     *
     * @var Processor
     */
    private Processor $processor;

    /**
     * @param Processor $processor
     */
    public function __construct(
        Processor $processor
    ) {
        $this->processor = $processor;
    }

    /**
     * Recalculate plp data after product save
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer): void
    {
        $event = $observer->getEvent();
        /** @var Product $product */
        $product = $event->getProduct();

        if ($product->getTypeId() == Type::TYPE_SIMPLE) {
            $fields = [
                'price',
                'status',
            ];

            foreach ($fields as $field) {
                if ($product->dataHasChangedFor($field)) {
                    $this->processor->reindexRow($product->getId());
                    break;
                }
            }
        }
    }
}
