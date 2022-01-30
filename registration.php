<?php
/**
 * @category    Smartautobrains
 * @package     TestTask
 * @author      P.Kushnerevich <pkushnerevich@smartautobrains.com>
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @copyright   Copyright (c) 2022 Smartautobrains (https://smartautobrains.com/)
 */

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Smartautobrains_TestTask',
    __DIR__
);
