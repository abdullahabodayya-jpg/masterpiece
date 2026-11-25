# Frame — Codex source handoff

Source authority: masteroiece.pdf is the approved physical schema reference (D-020). Original DDL / matching original DBML is not available in this bundle. The Phase 03 DBML, dictionary, constraints and enums were drafted against a different proposed schema; they are intentionally excluded. Do not infer unseen nullability, defaults, keys, FK actions or types from Laravel defaults. Report missing evidence and request owner review before creating domain migrations. Requirements and architecture drafts describe behavior and proposed standards, not authorization to add or rename tables.

Owner direction in current development: Laravel 12 (observed 12.74.0), PHP 8.2.12, React frontend, local MySQL-compatible connection, repo masterpiece/frame-backend, branch feature/frame-backend. Verify actual database server/version. Laravel 13/PHP 8.4 and exact MySQL version in older documents are proposals, not current local choices. Sanctum installation and routing are pending complete verification.

The immediate task is a read-only bootstrap and schema-evidence check (BL-01/BL-02). No migration execution, file changes, dependency installation, commit or push until reviewed. Do not output .env contents or secrets.

Some references in the original documents point to files outside this focused bundle. Ask for relevant sources as needed rather than substituting historical proposals.
