# Frame Module Architecture

**Version 0.5.0 · 24 November 2026 · Phase 04 review draft**

Sources: masteroiece.pdf (approved schema reference), Frame Business Documentation, Phase 01 requirements and Phase 02 analysis subject to the schema reconciliation.


Place domain code in `app/Domain/<Module>` and HTTP adapters in Laravel's HTTP structure. A controller receives a Form Request and calls an action or scoped query. Policies authorize actor and target. Action checks stage again inside the owning transaction. API Resources whitelist output fields. Use Eloquent relationships for confirmed schema relations and explicit table overrides for `conversation_body`. Application workflows call module interfaces and pass identifiers or immutable facts.

| Module | PDF models | Commands, policies and tests |
| --- | --- | --- |
| Identity | User | Authenticate, recover and suspend. Check active status and session revocation. Test recovery expiry and unauthorized role mutation |
| Profiles | User, UserLink, ContentType, EditingTool, PortfolioProject | Update owned profile, query discovery, approve publication rules. Test public projection and private file exclusions |
| Verification | EditorVerificationRequest | Submit evidence, claim scoped review, decide, request changes. Check current criteria and restricted document access. Test wrong reviewer and accepted/rejected paths |
| Marketplace | WorkItem, JobApplication, EditorService | Publish intake, apply, withdraw, accept offer and create pending agreement state. Use work type and role guards. Test duplicate and concurrent selection |
| Agreements and Work | WorkItem | Coordinate term assent and work activation. Shared transaction rechecks authoritative funding. Version evidence storage requires approved extension. Test stale assent and duplicate activation |
| Delivery | Deliverable, DeliverableRevision | Inspect, submit, request revision and decide a scoped output. Coordinate with Work and private storage. Test obsolete review decisions and interrupted upload |
| Finance | Payment, Payout, Refund | Use provider adapter with verified callbacks, idempotency and reconciliation. Separate requester, approver and execution grants. Test duplicate receipt, refund reservations and timeout after provider success |
| Communications | Conversation, ConversationParticipant, ConversationMessage | Enforce member access on listing, sending and downloading. Maintain last read state. Notifications require approved persistence choice. Test removed participant and privacy-safe notification |
| Reputation | Review | Check qualifying participant pair, completion and payment policy. Test duplicate or reversal cases |
| Community | Post, Comment, Reaction, Connection, UserFollow, UserBlock | Apply visibility and effective blocks, record moderation outcomes via scoped command. Test mixed public and restricted views |
| Staff Operations | User plus proposed scoped grants, cases and audit persistence | Scope verification, moderation, support, Diwan and disputes. Route financial effects to Finance. Test authorization separation and assignment changes |

## Component selection

- Controller and Form Request for public HTTP commands. Query objects only for reusable complex reads. Action classes own named transitions such as AcceptApplication and ApproveDeliverable.
- Use services for genuine shared capabilities such as exact Money arithmetic, the provider adapter and signed-file authorization. Avoid thin wrappers around simple Eloquent queries.
- Define backed enums for known PDF states. Expose typed domain errors for conflict, eligibility and unconfirmed funding. Map them consistently to HTTP errors without leaking policy internals.
- Jobs execute slow or retriable tasks such as inspection, reconciliation and notification delivery. Events describe committed facts. Listeners perform independent reactions with deduplication. Store durable financial effects under transactional controls.
- Feature tests cover roles, ownership, invalid states and response shaping. Integration tests exercise the target DB engine, adapter and private storage. Unit tests cover monetary rounding and state guards. Keep test fixtures synthetic.
