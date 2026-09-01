<?php
/**
 * Copyright © YoungDogs. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace YoungDogs\CmsSearch\Model\Filter;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use YoungDogs\CmsSearch\Model\SearchTerm\Parser;

/**
 * Applies GraphQL CmsSearchFilterTypeInput (eq / match / in) conditions to a CMS collection.
 */
class FilterApplier
{
    /**
     * Fields holding WYSIWYG/HTML markup; "match" strips tags before comparing so search hits visible text only.
     *
     * @var string[]
     */
    private const HTML_STRIPPED_FIELDS = ['content'];

    public function __construct(private readonly Parser $searchTermParser)
    {
    }

    /**
     * @param AbstractDb $collection
     * @param array<string, array<string, mixed>> $filter Field name => FilterTypeInput array (['eq' => ..., 'match' => ..., 'in' => ...])
     * @param string[] $allowedFields Fields that may be filtered on.
     */
    public function apply(AbstractDb $collection, array $filter, array $allowedFields): void
    {
        foreach ($filter as $field => $conditions) {
            if (!in_array($field, $allowedFields, true)) {
                throw new GraphQlInputException(__('Field "%1" cannot be used for filtering.', $field));
            }

            if (!is_array($conditions) || $conditions === []) {
                continue;
            }

            foreach ($conditions as $conditionType => $value) {
                $this->applyCondition($collection, $field, $conditionType, $value);
            }
        }
    }

    private function applyCondition(AbstractDb $collection, string $field, string $conditionType, mixed $value): void
    {
        switch ($conditionType) {
            case 'eq':
                $collection->addFieldToFilter($field, ['eq' => (string) $value]);
                break;
            case 'in':
                if (!is_array($value) || $value === []) {
                    throw new GraphQlInputException(__('The "in" filter for field "%1" requires a non-empty array.', $field));
                }
                $collection->addFieldToFilter($field, ['in' => $value]);
                break;
            case 'match':
                $this->applyMatch($collection, $field, (string) $value);
                break;
            default:
                throw new GraphQlInputException(__('Unknown filter condition "%1" for field "%2".', $conditionType, $field));
        }
    }

    private function applyMatch(AbstractDb $collection, string $field, string $value): void
    {
        $parsed = $this->searchTermParser->parse($value);

        if ($parsed['phrases'] === [] && $parsed['words'] === []) {
            return;
        }

        foreach ([...$parsed['phrases'], ...$parsed['words']] as $term) {
            $this->addLikeCondition($collection, $field, $term);
        }
    }

    private function addLikeCondition(AbstractDb $collection, string $field, string $term): void
    {
        $connection = $collection->getConnection();
        $column = in_array($field, self::HTML_STRIPPED_FIELDS, true)
            ? $this->stripHtmlExpression($connection, $field)
            : $connection->quoteIdentifier($field);

        // Select::where() calls quoteInto() on $cond unless $type is explicitly TYPE_CONDITION, and quoteInto()
        // blindly replaces every literal "?" (e.g. the "?" in the regex patterns below) with a bound value.
        $collection->getSelect()->where(
            $column . ' LIKE ' . $connection->quote('%' . $this->escapeLike($term) . '%'),
            null,
            Select::TYPE_CONDITION
        );
    }

    /**
     * Builds a SQL expression that removes script/style blocks and remaining tags, leaving visible text only.
     *
     * Also decodes &lt;/&gt; entities first, since some legacy content has double-encoded markup
     * (e.g. "...&gt;&lt;/iframe&gt;") that would otherwise never match a literal "<"/">" tag delimiter.
     *
     * Requires REGEXP_REPLACE (MySQL 8.0+ / MariaDB 10.0.5+), both satisfied by Magento's supported DB versions.
     */
    private function stripHtmlExpression(AdapterInterface $connection, string $field): string
    {
        $expression = $connection->quoteIdentifier($field);
        $expression = sprintf('REPLACE(%s, %s, %s)', $expression, $connection->quote('&lt;'), $connection->quote('<'));
        $expression = sprintf('REPLACE(%s, %s, %s)', $expression, $connection->quote('&gt;'), $connection->quote('>'));

        foreach (['<script[^>]*>.*?</script>', '<style[^>]*>.*?</style>', '<[^>]+>'] as $pattern) {
            $expression = sprintf(
                'REGEXP_REPLACE(%s, %s, %s)',
                $expression,
                $connection->quote($pattern),
                $connection->quote('')
            );
        }

        return $expression;
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '%_\\');
    }
}
