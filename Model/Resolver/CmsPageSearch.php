<?php
/**
 * Copyright © YoungDogs. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace YoungDogs\CmsSearch\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use YoungDogs\CmsSearch\Model\Config;
use YoungDogs\CmsSearch\Model\DataProvider\CmsPage as CmsPageDataProvider;

/**
 * Resolves the cmsPageSearch GraphQL query.
 */
class CmsPageSearch implements ResolverInterface
{
    public function __construct(
        private readonly Config $config,
        private readonly CmsPageDataProvider $dataProvider
    ) {
    }

    /**
     * @inheritDoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (!$context instanceof ContextInterface) {
            throw new GraphQlInputException(__('Invalid request context.'));
        }

        $storeId = (int) $context->getExtensionAttributes()->getStore()->getId();

        if (!$this->config->isEnabled($storeId)) {
            throw new GraphQlInputException(__('CMS Search is currently disabled.'));
        }

        return $this->dataProvider->search(
            $args['filter'] ?? null,
            (int) ($args['pageSize'] ?? 20),
            (int) ($args['currentPage'] ?? 1),
            $this->config->getMaxPageSize($storeId),
            $args['sort'] ?? null,
            $storeId
        );
    }
}
