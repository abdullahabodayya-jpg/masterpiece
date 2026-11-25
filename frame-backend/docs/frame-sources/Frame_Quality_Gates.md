# Frame Quality Gates

**Version 0.8.0 · 24 November 2026 · Proposed for review**

Source baseline: masteroiece.pdf, Phase 01–06 design documentation and [Frame Test Cases](Frame_Test_Cases.xlsx). Decision D-024 authorizes QA planning. This document defines proposed approvals for the enabled scope.

| Gate | Required evidence | Approval owner |
| --- | --- | --- |
| Module approval | Requirement and operation mappings, implemented behaviors, positive and negative tests, scoped auth and failure coverage. Check async and data effects. P0 relevant tests pass. Track blocked optional behaviors as disabled | Module owner and QA |
| Pull request merge | Reviewed code and scope, CI checks green, no unaddressed failing required test, compatible schema/config change, review of access and retry behavior. Include a focused regression check for fixes | Maintainer and designated reviewers |
| Technical staging release | End-to-end tests for each enabled intake, real-engine migration evidence, critical concurrency and replay coverage, accessible Arabic flows, sanitized logs and backup/restore rehearsal. Evidence includes build and dataset | Engineering and QA |
| Sensitive data enablement | Approved collection and retention/hold, per-resource authorization, access audit, storage/inspection evidence and an assigned incident owner | Security and data owner under D-011 |
| Real-money Pilot | Written provider and legal decisions, scoped staff and approvals, tested sandbox capture/refund/disputes, reconciliation of statements, runbooks and reviewed customer notices | Product, Finance, Ops and applicable legal reviewer |
| Public MVP | Both Jobs and Services complete user acceptance and applicable recovery and release checks. Current approval, performance and reliability targets are recorded, and outstanding cases map to controlled scope | Product and release owner |

## Blocking rules

- A failed or unexecuted required P0 case blocks approval for the affected capability. An open decision that changes expected behavior keeps affected work conditional. Required security, financial integrity and recovery checks apply to the actual release configuration.
- Dependency problems get a ticket and owner. A documented scope change can defer an optional capability when product and engineering agree and the capability is disabled in the released build.
- For non-blocking defects, record severity, user impact, scope, mitigation, owner and retest/fix checkpoint. Record any permitted exception in release evidence.
- Record commit, contract version, migration revision, provider/test environment, case IDs, execution results, sign-offs and rollback/recovery owner before enablement. Evidence kept in Phase 07 contains test plans, with execution results to be filled from actual runs.
