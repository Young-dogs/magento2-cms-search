# YoungDogs_CmsSearch

Adds flexible GraphQL search for Magento 2 CMS **pages** and CMS **blocks**.

Out of the box, Magento only lets you fetch a CMS page by its exact `identifier`, or CMS blocks by an
exact list of `identifiers`. This module adds two additional queries, `cmsPageSearch` and
`cmsBlockSearch`, that let you search by content, title, and other fields — with exact match,
word/phrase search, or "in list" filtering.

## Requirements

- PHP 8.2 - 8.5
- Magento 2.4.6+
- `Magento_Cms`, `Magento_Store`, `Magento_GraphQl` (installed by default with Magento)

## Installation

```bash
composer require youngdogs/magento2-cms-search
bin/magento module:enable YoungDogs_CmsSearch
bin/magento setup:upgrade
bin/magento cache:flush
```

## Configuration

**Stores > Configuration > General > CMS Search**

| Field | Description |
| --- | --- |
| Enable CMS Search | Yes/No. When disabled, both queries return a GraphQL input error. |
| Maximum Page Size | Hard upper limit applied to `pageSize`, regardless of what the client requests. |

Only **active** pages/blocks belonging to the current store view are returned.

## GraphQL usage

### Search CMS pages

```graphql
{
  cmsPageSearch(
    filter: {
      content: { match: "\"free shipping\" promo" }
      identifier: { in: ["about-us", "contact-us"] }
    }
    pageSize: 10
    currentPage: 1
    sort: { field: TITLE, direction: ASC }
  ) {
    items {
      identifier
      title
      content
      content_text
      content_heading
      page_layout
      meta_title
      meta_description
      meta_keywords
    }
    total_count
    page_info {
      page_size
      current_page
      total_pages
    }
  }
}
```

### Search CMS blocks

```graphql
{
  cmsBlockSearch(filter: { title: { match: "homepage banner" } }) {
    items {
      identifier
      title
      content
      content_text
    }
    total_count
    page_info {
      page_size
      current_page
      total_pages
    }
  }
}
```

### Filterable fields

| Query | Fields |
| --- | --- |
| `cmsPageSearch` | `identifier`, `title`, `content`, `content_heading`, `meta_title`, `meta_description`, `meta_keywords` |
| `cmsBlockSearch` | `identifier`, `title`, `content` |

Each field accepts a `CmsSearchFilterTypeInput`:

| Condition | Behavior |
| --- | --- |
| `eq` | Exact match. |
| `in` | Matches any value in the given array. |
| `match` | Search-engine style: `"quoted phrases"` are matched literally; remaining words are split on whitespace and combined with AND. Case-insensitive substring match. |

Multiple fields in the same `filter` object are combined with AND.

### Plain-text content

Both `cmsPageSearch` and `cmsBlockSearch` items expose a `content_text` field alongside `content`: the same
content with HTML tags, `<script>`/`<style>` blocks, and entities stripped/decoded, leaving plain readable
text. Useful when passing results to an AI agent that shouldn't have to parse markup.

### Sorting

`sort: { field: TITLE | IDENTIFIER, direction: ASC | DESC }` (defaults to `title ASC`).

## Limitations

- `match` performs a `LIKE`-based substring search (no fulltext/relevance engine dependency required). For
  very large CMS catalogs, consider indexing `content` externally (e.g. Elasticsearch/OpenSearch) if
  search performance becomes a concern.
- Only active pages/blocks are searchable; there is no admin/preview mode for inactive content.

## License

MIT — see [LICENSE](LICENSE).
