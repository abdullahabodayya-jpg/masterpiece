# Frame Coding Standards

**Version 0.5.0 · 24 November 2026 · Phase 04 review draft**

Sources: masteroiece.pdf (approved schema reference), Frame Business Documentation, Phase 01 requirements and Phase 02 analysis subject to the schema reconciliation.


- Follow Laravel conventions and PSR-12 where applicable. Format with Laravel Pint and check the committed configuration in CI. Use descriptive method and class names, singular Eloquent models and immutable domain inputs where useful.
- Keep route definitions by capability, form requests focused on input validation, policies focused on authorization and action classes focused on business transitions. Use named routes. Recheck sensitive command eligibility inside the write transaction.
- Map relations and columns to the approved schema. Keep explicit table and foreign-key names where the PDF differs from conventions. Specify whitelist serialization separately from writable attributes. Avoid unrestricted request assignment and raw SQL interpolation.
- Use descriptive exceptions for disallowed transitions, stale state, duplicate intent and storage or provider failure. HTTP adapters log correlation and return scoped messages. Data and content supplied by services are untrusted until validated.
- Migrations state evidence, named keys, supported engine and rollback considerations. For unsafe reversals use reviewed forward repair and documented recovery. Prefer expand, backfill, switch and contract for live changes. Keep synthetic factories deterministic when tests require it and exclude demonstration seeders from production paths.
- Use short feature branches and focused commits. A PR documents requirement, rule or ADR IDs, schema effects, permission effects, tests run and staged dependencies. Require approval and CI on protected main. Review for authorization, concurrent behavior, retries, retention and accurate error handling.
- Write comments for invariants and rationale. Maintain the API contract, decision register and schema notes alongside behavioral changes. Prefer existing Laravel capabilities until a recurring problem justifies extraction.
