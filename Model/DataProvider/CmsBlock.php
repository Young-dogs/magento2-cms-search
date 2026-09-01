<?php
/**
 * Copyright © YoungDogs. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace YoungDogs\CmsSearch\Model\DataProvider;

use Magento\Cms\Model\ResourceModel\Block\CollectionFactory;
use YoungDogs\CmsSearch\Model\Filter\FilterApplier;
use YoungDogs\CmsSearch\Model\Html\ContentTextConverter;

/**
 * Builds a filtered, sorted and paginated result set of active CMS blocks for a store.
 */
class CmsBlock extends AbstractCollectionDataProvider
{
    /**
     * @var string[]
     */
    private const ALLOWED_FILTER_FIELDS = [
        'identifier',
        'title',
        'content',
    ];

    /**
     * @var array<string, string>
     */
    private const ALLOWED_SORT_FIELDS = [
        'TITLE' => 'title',
        'IDENTIFIER' => 'identifier',
    ];

    public function __construct(
        FilterApplier $filterApplier,
        private readonly CollectionFactory $collectionFactory,
        private readonly ContentTextConverter $contentTextConverter
    ) {
        parent::__construct($filterApplier);
    }

    /**
     * @param array<string, array<string, mixed>>|null $filter
     * @param array{field?: string, direction?: string}|null $sort
     * @return array{items: array[], page_info: array, total_count: int}
     */
    public function search(
        ?array $filter,
        int $pageSize,
        int $currentPage,
        int $maxPageSize,
        ?array $sort,
        int $storeId
    ): array {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('is_active', 1);
        $collection->addStoreFilter($storeId);

        $result = $this->applySearchCriteria(
            $collection,
            $filter,
            self::ALLOWED_FILTER_FIELDS,
            $pageSize,
            $currentPage,
            $maxPageSize,
            $sort,
            self::ALLOWED_SORT_FIELDS,
            'title'
        );

        $items = [];
        foreach ($collection as $block) {
            $items[] = [
                'identifier' => $block->getIdentifier(),
                'title' => $block->getTitle(),
                'content' => $block->getContent(),
                'content_text' => $this->contentTextConverter->toPlainText($block->getContent()),
            ];
        }

        return ['items' => $items] + $result;
    }
}
