<?php
/**
 * @category    Smartautobrains
 * @package     TestTask
 * @author      P.Kushnerevich <pkushnerevich@smartautobrains.com>
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @copyright   Copyright (c) 2022 Smartautobrains (https://smartautobrains.com/)
 */

declare(strict_types=1);

namespace Smartautobrains\TestTask\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Ui\Component\Container;
use Magento\Ui\Component\Form\Element\DataType\Text;
use Magento\Ui\Component\Form\Element\DataType\Date;
use Magento\Ui\Component\Form\Element\DataType\Price;
use Magento\Ui\Component\Form\Element\Input;
use Magento\Ui\Component\Form\Field;
use Smartautobrains\TestTask\Model\ConfigurablePlpData;
use Smartautobrains\TestTask\Setup\Patch\Data\CreatePlpDataAttribute;

/**
 * Class Components
 */
class PlpData extends AbstractModifier
{
    /**
     * Name in table.
     */
    private const COLUMN_NAME_ITEM = 'Name';

    /**
     * Price column in table.
     */
    private const COLUMN_NAME_PRICE = 'Price';

    /**
     * Array manager
     *
     * @var ArrayManager
     */
    private ArrayManager $arrayManager;

    /**
     * @param ArrayManager $arrayManager
     */
    public function __construct(
        ArrayManager $arrayManager
    ) {
        $this->arrayManager = $arrayManager;
    }

    /**
     * Modify data
     *
     * @param array $data
     * @return array
     */
    public function modifyData(array $data): array
    {
        $plpDataPath = $this->arrayManager->findPath(CreatePlpDataAttribute::ATTRIBUTE_CODE, $data);
        if (!$plpDataPath) {
            return $data;
        }
        $plpData = $this->arrayManager->get($plpDataPath, $data);

        $plpBeautyFormat = 'Max price: '
            . number_format((float)$plpData[ConfigurablePlpData::MAX_PRICE_PLP_DATA_ATTRIBUTE], 2, '.', '')
            . '. Min price: '
            . number_format((float)$plpData[ConfigurablePlpData::MIN_PRICE_PLP_DATA_ATTRIBUTE], 2, '.', '')
        ;

        return $this->arrayManager->set($plpDataPath . '_view', $data, $plpBeautyFormat);
    }

    /**
     * Replace default text input Meta with dynamic rows.
     *
     * @param array $meta
     * @return array
     */
    public function modifyMeta(array $meta): array
    {
        $elementPath = $this->arrayManager->findPath(CreatePlpDataAttribute::ATTRIBUTE_CODE, $meta, null, 'children');

        if (!$elementPath) {
            return $meta;
        }

        $plpData = $this->arrayManager->get($elementPath, $meta);
        $this->arrayManager->remove($elementPath, $meta);
        $this->arrayManager->set($elementPath . '_view', $meta, $plpData);

        return $this->arrayManager->set(
            $elementPath . '_view' . '/arguments/data/config/disabled',
            $meta,
            true
        );
    }
}
