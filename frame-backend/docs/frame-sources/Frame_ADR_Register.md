# Frame Architecture Decision Register

**Version 0.5.0 · 24 November 2026 · Phase 04 review draft**

Sources: masteroiece.pdf (approved schema reference), Frame Business Documentation, Phase 01 requirements and Phase 02 analysis subject to the schema reconciliation.


| ADR | Context | Decision and status | Alternative and consequence |
| --- | --- | --- | --- |
| ADR-01 | New schema source supplied | Adopt masteroiece.pdf for table/field names [APPROVED] | Phase 03 DBML is historical. Original schema constraints require source DDL confirmation |
| ADR-02 | Cross-domain transactions and small team | Modular monolith [PROPOSED] | Services create distributed consistency and operational cost. Module boundaries retain future extraction options |
| ADR-03 | Work item owns marketplace lifecycle | Use work_items as common Job/Service Request aggregate [PROPOSED] | A separate project aggregate needs an approved extension. Current approach avoids duplicating lifecycle ownership |
| ADR-04 | Mutable work terms and one assent timestamp | Persist versioned terms and party assents before activation [PROPOSED, D-004] | A single timestamp cannot establish both assents and exact terms. Approve evidence schema and backfill rules |
| ADR-05 | Electronic payments and PDF decimal fields | Use decimal domain values and authoritative callbacks, with persistent dedupe and reconciliation [PROPOSED, D-005/007/010] | Browser success and volatile job dedupe are insufficient. Funded milestones need a schema change. Verify provider precision before live money |
| ADR-06 | Confidential work and verification evidence | Private object storage, quarantine and scoped grants [PROPOSED, D-011] | Public direct links expose restricted media. Persistence of inspection state must be designed |
| ADR-07 | Coarse PDF staff role values | Role plus scoped capability and assignment [PROPOSED, D-008] | Role-only authorization cannot distinguish payment and review actions. Approve storage for scopes and audit |
| ADR-08 | First-party browser auth | Cookie-backed sessions with CSRF, Sanctum if SPA [PROPOSED, D-009] | Browser bearer tokens complicate storage and lifecycle. Confirm deployment origin |
| ADR-09 | Initial moderate workload | Database queue and worker, explicit durable inbox/outbox for critical domain effects [PROPOSED] | Redis is an operational option after measurement. Confirm schema and replay policies |
| ADR-10 | Runtime compatibility | Laravel 13.x and PHP 8.4.x with MySQL 8.4.x candidate [PROPOSED, D-009] | PHP 8.2 requires Laravel 12 or runtime upgrade. Pin exact versions after host and repo validation |

## Decisions before implementation

D-004 defines acceptance and activation evidence. D-005/007/010 and D-012 govern payment granularity, provider, precision and rounding. D-003/008/011 govern verification, staff and private records. D-013 sets operating and accessibility targets. Phase 05 can draft resource intent from the baseline and mark unresolved commands conditional.
