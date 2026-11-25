# Frame API Standards

**Version 0.6.0 · Phase 05 review draft · 24 November 2026**

Sources: masteroiece.pdf, Phase 01–04 requirements and architecture, and OpenAPI 3.0.3. D-020 establishes the physical schema reference. See the contract for operation-level policy gates.

## URLs and payloads

Use /api/v1, plural resource collections and stable opaque-to-clients IDs. JSON uses snake_case. Commands use explicit subresources such as publication, selection, acceptance and cancellation. The API derives actor, owner and state from authoritative records. Public lists expose only fields allowed by current visibility, blocking and ownership rules.

Return `data` for JSON success. Lists include `meta.page`, `meta.per_page` and `meta.has_more`. Use page 1 and 20 records by default, maximum 100. Accept only documented filters and sorts. Newest and oldest use creation time with IDs as deterministic tie-breakers. If a resource uses another ordering, document the field before implementation. List endpoints use the same authorization predicate as detail endpoints. Fields marked optional may be absent. Use RFC 3339 timestamps in UTC. Use two-decimal string amounts from the PDF and three-letter uppercase currency codes. Conversion to actual provider units remains gated by provider and precision decisions.

## Authentication and authorization

The candidate first-party browser flow uses a cookie-backed session. Initialize CSRF via GET /api/v1/csrf-cookie, then send X-XSRF-TOKEN on state changes including session creation. The server must issue a session cookie and rotate it on sign-in. Sanctum may support the selected SPA topology. Validate each command against active account, explicit capability, relation to resource and current state. Staff verification or dispute assignment does not convey payment permissions. Provider callback authentication follows the provider contract. The signature header in OpenAPI identifies a placeholder.

## Idempotency and state

Use Idempotency-Key on commands marked required. Scope by authenticated principal, operation and route identity. Repeat the same key with the same normalized body to return the original result. A different payload with the same key returns 409. Verify prior result access before replay. Lock the relevant work item before selecting an editor or applying a funding-sensitive transition. Durable uniqueness and failure handling remain required. Avoid issuing a new payment attempt while an earlier attempt has an unknown provider outcome.

## Execution and staging

Use 200 for reads and state transitions, 201 for creation, 202 for durable accepted work and 204 for no-body success. The contract distinguishes requested operations from implemented software. Conditional operations need the listed decisions, relevant persistence and verification. Existing PDF fields establish work items, applications, editor services, delivery, messaging and payment associations. Agreement versions, exact submission evidence, upload inspection state, callback receipts, notifications, staff scopes, audit and operational cases may need approved persistence additions. Funded milestones need a schema decision. The API uses work-item payment scope as its draft assumption.

## Postman

Import the collection and environment. Use a local or approved staging URL, set the origin, and provide a synthetic account, password, fixture IDs and a fresh idempotency key per new intent. Run CSRF initialization, then sign in under the appropriate role. Postman uses the cookie jar plus the environment CSRF variable, which can be filled from the XSRF-TOKEN cookie manually if it is unavailable to the script. Set a fresh key for the stale-selection test and use an eligible owner with a stale fixture. Run unauthorized checks in a clean cookie jar. Run individual endpoints by module; generic example IDs are placeholders. Callback examples include a negative request only because the provider signature format is undecided. Tests in the collection assert expected status and envelope once an implementation is available.
