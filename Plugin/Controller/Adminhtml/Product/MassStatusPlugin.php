<?php
/**
 * @category    Smartautobrains
 * @package     TestTask
 * @author      P.Kushnerevich <pkushnerevich@smartautobrains.com>
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @copyright   Copyright (c) 2022 Smartautobrains (https://smartautobrains.com/)
 */

declare(strict_types=1);

namespace Smartautobrains\TestTask\Plugin\Controller\Adminhtml\Product;

use Magento\Catalog\Controller\Adminhtml\Product\MassStatus;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Psr\Log\LoggerInterface as Logger;
use Smartautobrains\TestTask\Model\Indexer\Product\Configurable\Processor;

/**
 * Class MassStatusPlugin
 */
class MassStatusPlugin
{
    /**
     * Collection factory
     *
     * @var CollectionFactory
     */
    private CollectionFactory $collectionFactory;

    /**
     * Filter
     *
     * @var Filter
     */
    private Filter $filter;

    /**
     * Logger
     *
     * @var Logger
     */
    private Logger $logger;

    /**
     * Index processor
     *
     * @var Processor
     */
    private Processor $processor;

    /**
     * @param CollectionFactory $collectionFactory
     * @param Filter $filter
     * @param Logger $logger
     * @param Processor $processor
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        Filter $filter,
        Logger $logger,
        Processor $processor
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->filter = $filter;
        $this->logger = $logger;
        $this->processor = $processor;
    }

    /**
     * Reindex plp_data attribute after mass update
     *
     * @param MassStatus $subject
     * @param Redirect $result
     *
     * @return Redirect
     * @see MassStatus::execute()
     */
    public function afterExecute(
        MassStatus $subject,
        Redirect $result
    ):Redirect {
        try {
            $collectionFactory = $this->collectionFactory->create();
            $collection = $this->filter->getCollection($collectionFactory);
            $productIds = $collection->getAllIds();

            $this->processor->reindexList($productIds);
        } catch (LocalizedException $exception) {
            $this->logger->error($exception->getMessage());
            return $result;
        }

        return $result;
    }
}
