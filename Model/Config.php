<?php
/**
 * Copyright © YoungDogs. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace YoungDogs\CmsSearch\Model;

use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Reads YoungDogs_CmsSearch store configuration (Stores > Configuration > CMS Search).
 */
class Config
{
    private const XML_PATH_ENABLED = 'yd_cms_search/general/enabled';
    private const XML_PATH_MAX_PAGE_SIZE = 'yd_cms_search/general/max_page_size';

    public function __construct(private readonly ScopeConfigInterface $scopeConfig)
    {
    }

    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getMaxPageSize(?int $storeId = null): int
    {
        $value = (int) $this->scopeConfig->getValue(
            self::XML_PATH_MAX_PAGE_SIZE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $value > 0 ? $value : PHP_INT_MAX;
    }
}
