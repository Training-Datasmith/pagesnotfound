# Architecture: pagesnotfound

## Purpose

A PrestaShop statistics module that logs 404 "Page Not Found" requests and displays them in the back-office stats panel. Helps store administrators find broken links and dead bookmarks.

## Directory Structure

```
pagesnotfound.php      — Module class: install/uninstall hooks, admin display, 404 logging
index.php              — Security: prevents direct directory listing
views/
  css/
    stacking_responsive.css  — Responsive table styles for the stats panel
```

## Key Design Decisions

- **Hook-based**: Uses PrestaShop's hook system rather than overrides
  - `displayTop`: fires on every frontend page load to detect and log 404 hits
  - `displayAdminStatsModules`: renders the stats table in the back office
- **Database logging**: Each 404 hit is written to `ps_pagenotfound` with `request_uri`, `http_referer`, `date_add`, and shop context
- **Admin truncation**: Provides two admin actions — delete records for the current stats period, or truncate the entire table

## Extension Points

- The module integrates with PrestaShop's `ModuleGraph::getDateBetween()` for date-range filtering, which is driven by the standard stats date picker
- Extend by adding new columns to the `ps_pagenotfound` table and updating `hookDisplayTop` / `getPages()` accordingly

## Dependency Flow

```
PagesNotFound extends Module (PrestaShop)
  ├── Db::getInstance()          — raw SQL queries
  ├── ModuleGraph::getDateBetween() — date range for stats queries
  ├── Shop::addSqlRestriction()  — multi-shop filtering
  ├── Tools, Validate            — input sanitization
  └── Context::getContext()      — current shop/controller context
```
