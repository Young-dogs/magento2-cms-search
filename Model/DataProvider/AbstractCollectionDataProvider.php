<?php
/**
 * Copyright © YoungDogs. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace YoungDogs\CmsSearch\Model\DataProvider;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use YoungDogs\CmsSearch\Model\Filter\FilterApplier;

/**
 * Shared filtering, sorting and pagination logic for CMS search collections.
 */
abstract class AbstractCollectionDataProvider
{
    public function __construct(protected readonly FilterApplier $filterApplier)
    {
    }

    /**
     * @param array<string, array<string, mixed>>|null $filter
     * @param string[] $allowedFilterFields
     * @param array<string, string> $allowedSortFields GraphQL enum value => DB column name
     * @return array{page_info: array{page_size: int, current_page: int, total_pages: int}, total_count: int}
     */
    protected function applySearchCriteria(
        AbstractDb $collection,
        ?array $filter,
        array $allowedFilterFields,
        int $pageSize,
        int $currentPage,
        int $maxPageSize,
        ?array $sort,
        array $allowedSortFields,
        string $defaultSortField
    ): array {
        if ($pageSize < 1) {
            throw new GraphQlInputException(__('pageSize must be greater than 0.'));
        }
        if ($currentPage < 1) {
            throw new GraphQlInputException(__('currentPage must be greater than 0.'));
        }

        $pageSize = min($pageSize, $maxPageSize);

        if ($filter !== null) {
            $this->filterApplier->apply($collection, $filter, $allowedFilterFields);
        }

        $sortField = $defaultSortField;
        $sortDirection = 'ASC';
        if ($sort !== null && !empty($sort['field'])) {
            if (!isset($allowedSortFields[$sort['field']])) {
                throw new GraphQlInputException(__('Field "%1" cannot be used for sorting.', $sort['field']));
            }
            $sortField = $allowedSortFields[$sort['field']];
            $sortDirection = $sort['direction'] ?? 'ASC';
        }
        $collection->addOrder($sortField, $sortDirection);

        $totalCount = $collection->getSize();

        $collection->setCurPage($currentPage);
        $collection->setPageSize($pageSize);
        // Force collection to load with the pagination applied.
        $collection->clear()->load();

        return [
            'page_info' => [
                'page_size' => $pageSize,
                'current_page' => $currentPage,
                'total_pages' => (int) ceil($totalCount / $pageSize),
            ],
            'total_count' => $totalCount,
        ];
    }
}
