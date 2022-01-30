<?php
/**
 * @category    Smartautobrains
 * @package     TestTask
 * @author      P.Kushnerevich <pkushnerevich@smartautobrains.com>
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @copyright   Copyright (c) 2022 Smartautobrains (https://smartautobrains.com/)
 */

declare(strict_types=1);

namespace Smartautobrains\TestTask\Setup\Patch\Data;

use Exception;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Eav\Model\Entity\Attribute\Backend\JsonEncoded;
use Psr\Log\LoggerInterface;

/**
 * Class CreatePlpDataAttribute
 */
class CreatePlpDataAttribute implements DataPatchInterface
{
    /**
     * Attribute code for plp data product attribute
     */
    public const ATTRIBUTE_CODE = 'plp_data';

    /**
     * Eav setup factory
     *
     * @var EavSetupFactory
     */
    private EavSetupFactory $eavSetupFactory;

    /**
     * Logger
     *
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param EavSetupFactory $eavSetupFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory,
        LoggerInterface $logger
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->logger = $logger;
    }

    /**
     * Get array of patches that have to be executed prior to this.
     *
     * @return array
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * Get aliases (previous names) for the patch.
     *
     * @return array
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * Create plp_data attribute
     *
     * @return CreatePlpDataAttribute
     */
    public function apply(): CreatePlpDataAttribute
    {
        $eavSetup = $this->eavSetupFactory->create();

        try {
            $eavSetup->addAttribute(Product::ENTITY, self::ATTRIBUTE_CODE,
                [
                    'type'             => 'text',
                    'label'            => 'Plp data',
                    'input'            => 'text',
                    'backend'          => JsonEncoded::class,
                    'frontend'         => '',
                    'class'            => '',
                    'source'           => '',
                    'global'           => ScopedAttributeInterface::SCOPE_GLOBAL,
                    'visible'          => true,
                    'required'         => false,
                    'visible_on_front' => false,
                    'apply_to'         => Configurable::TYPE_CODE,
                ]
            );
        } catch (Exception $e) {
            $this->logger->error($e);
        }

        return $this;
    }
}
