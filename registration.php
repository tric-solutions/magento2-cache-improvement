<?php
/**
 * @copyright Copyright © TRIC Solutions. All rights reserved.
 * @license   https://www.tric.dk/TRIC-LICENSE-COMMUNITY.txt
 * @link      https://www.tric.dk
 */
declare(strict_types=1);

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'TRIC_CacheImprovement',
    __DIR__
);
