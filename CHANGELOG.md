# Changelog

## 1.0.4 - 2026-08-20

### Added

- Added authenticated API access to individual published Knowledge Base articles.
- Added authenticated Knowledge Base helpfulness voting for connected EcclesiaOS installations.
- Added persistent per-installation and per-user article feedback storage with one replaceable vote per article.
- Added helpfulness percentages to article API payloads.
- Added API contract coverage for full article content and helpfulness feedback.

### Improved

- Added local zero-click enrollment defaults aligned with EcclesiaOS on port 8090.
- Documented the shared enrollment key required by the EcclesiaOS local development setup.
- Updated the Central Support API contract documentation with the new Knowledge Base endpoints.
- Applied the repository formatter to the release candidate so the complete Central Support codebase passes style verification.

### Fixed

- Fixed EcclesiaOS full-article requests returning 404 because Central Support exposed only the article list endpoint.
- Fixed helpfulness votes failing because Central Support had no corresponding endpoint or persistence layer.
- Fixed article list payloads to report real helpfulness percentages instead of a hard-coded zero.

## 1.0.3 - 2026-08-20

### Fixed

- Fixed the release build to compile frontend assets before packaging.
- Fixed the release database preparation and immutable release workflow.
