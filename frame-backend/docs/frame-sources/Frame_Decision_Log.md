# Frame — Decision Log

| Field | Detail |
| --- | --- |
| Version and date | 0.9.0 · 24 November 2026 |
| Status | Phase 08 review draft with recorded approvals |
| Purpose | Record choices, alternatives, consequences and evidence before dependent work starts |
| Scope | Discovery, Phase 01 requirements Phase 02 system analysis and Phase 03 design decisions |
| Source references | B = Frame_Business_Documentation.docx v1.0. T = Pasted text.txt. H = historical design context. A = owner directions recorded in the Frame conversation, including the prior MVP decision and Phase 01 approval |
| Dependencies | [Master Index](FRAME_MASTER_INDEX.md), [MVP Scope](https://chatgpt.com/library?file_id=libfile_f262759dba648191b1ea6a0246f27916&account_id=personal), [PRD](https://chatgpt.com/library?file_id=libfile_2b5291ce687081918d33b680bb3605b8&account_id=personal) and the Discovery Report |
| Approved decisions | D-000, D-001 public MVP inclusion, D-015, D-016 D-018, D-019, D-020 D-021 D-022 D-023 D-024 and D-025 |
| Open decisions | D-002–014 and D-017. Review Pilot scope, actor eligibility and contract/payment dependencies first |
| Requirement references | B FR-001–044, BR-01–44 and §13.1. Phase 01 FR-SVC-001/002, FR-MLT-001, FR-MET-001 and BR-45–53 |

## Decision register

Discovery, Phase 01 and Phase 02 register entries are dated 2026-11-24. Historical decision dates are separately recorded below. Accountable functions are proposed until assigned by the project owner.

| ID · status | Category / modules · proposed owner | Decision, alternatives and rationale | Consequences / exit evidence | Source |
| --- | --- | --- | --- | --- |
| D-000 · [APPROVED] | Process / all · Project Owner | Follow the requested discovery-first, staged approval workflow. The all-at-once specification option is excluded by the task brief | Review discovery before starting Phase 01. Record consequential decisions before dependent finalization | T §§12–13 |
| D-001 · [APPROVED] | Release / Marketplace, Agreements · Product Owner | Public MVP includes Jobs and Services, based on the earlier owner decision reported on 17 October 2026 and carried into Phase 01. Jobs-only public MVP and later Services were considered in discovery. | FR-SVC-001/002 and BR-48 enter the Phase 01 baseline. Service details and Pilot timing remain open under D-017 and related policy decisions. | Earlier owner decision as recorded in A. B §12 draft differs. See approval history |
| D-002 · [DECISION REQUIRED] | Release / Operations, Community, Diwan, Reviews · Product Owner + Operations | Choose a staged pilot with essential messaging, reports, support and audit before broader social/Diwan release, or one integrated MVP. [PROPOSED] Stage the rollout | Write pilot/public capability checklist, owners, manual case process, review milestone and release criteria | B §§12–13,16,19 FR-032–044 BR-35–44 |
| D-003 · [DECISION REQUIRED] | Policy / Identity, Profiles, Verification · Product Owner + Verification | Decide role cardinality, role changes and permitted unverified actions. [PROPOSED] One public role to start. Allow legitimate profile/evidence preparation and require verified relevant specialty before applying or offering editing through any included route. Compare verification only at hire and dual-role operation | Specify activation, job visibility, badge expiry/revocation, accepted evidence, appeals and handling of existing projects. Blocks resource action policies and eligibility stories | B §§11,13–14 FR-001–011/014–018 BR-01–12/17 |
| D-004 · [DECISION REQUIRED] | Lifecycle / Hiring, Agreements, Projects · Product Owner + Counsel | Decide when the project record exists, when both parties commit and when editors gain work access. [PROPOSED] Offer acceptance creates inactive project. Effective agreement and confirmed funding activate it. Compare creation after both conditions | Define aggregate lifecycle, accepted-version integrity, stale acceptance, cancellation before activation, and amendment effects. Counsel reviews contract terms | B §§9,11,13–15 FR-018–023 BR-14–24 |
| D-005 · [LEGAL REVIEW REQUIRED] | Payments / Projects, Ledger · Payments + Counsel | Compare authorized-then-captured, upfront provider capture and provider-managed conditional settlement, only where supported. [PROPOSED] Prototype provider-agnostic funding events. Keep funds within the approved provider model | Written provider compatibility, flow-of-funds approval, settlement/payout definitions, timeout and retry policy, and reconciliation sample required to finalize live design | B §§1,9,14–16 FR-025–031 BR-20/27–34 |
| D-006 · [DECISION REQUIRED] | Policy / Delivery, Support, Payments, Reviews · Product + Operations + Counsel | Define inactivity, acceptance, cancellation, revision limits, dispute deadlines, refund eligibility and review effects. [PROPOSED] Explicit acceptance and manual authorized escalation during the pilot. Consider auto-acceptance only after policy and capability review | Approved cases and outcomes for partial work, disputed delivery, refund, chargeback, review eligibility and financial authorities. Blocks state transitions and customer promises | B §§9,11,14–17 FR-022/028–030/038/043 BR-23–36 |
| D-007 · [DECISION REQUIRED] | Data / Agreements, Delivery, Payments · Product + Engineering | Choose project-level funding and settlement, or milestone-level obligations. [PROPOSED] Start at project level for the pilot and revisit after provider and product validation | Granularity governs fee snapshots, partial settlement, cancellation effects and ERD. Document whether milestones are tracking-only, funded or deferred | B §§9,11–13 FR-020/022/025–031 BR-20–25, H |
| D-008 · [DECISION REQUIRED] | Access / Staff, Support, Diwan, Finance · Operations + Security | Confirm baseline roles, queue scope, assignment, escalation and second approval. [PROPOSED] Use B §11.3 duties, scoped permission bundles and a named authorized financial reviewer. Compare dedicated Diwan supervisory roles if Diwan scope requires them | Staffing map and RBAC matrix with conflict-of-duty restrictions, audit, role grants and escalation authority before staff access | B §§11,13–15 FR-009/030/035/037–042 BR-03/05–06/31–32/38/40–44, T §7 |
| D-009 · [DECISION REQUIRED] | Architecture / platform and API · Engineering | Assess Laravel 13 with a supported PHP runtime and MySQL, a Laravel 12 bridge if runtime cannot yet move, and layered versus modular monolith. [PROPOSED] Modular monolith and first-party Sanctum where deployment topology supports it | ADR records hosting/runtime, compatibility, PHP upgrade needs, auth topology, maintenance commitments and deployment check. Exact package versions follow verification | T §§5–6, B §13.1, [Laravel](https://laravel.com/framework/docs/13.x/releases) |
| D-010 · [LEGAL REVIEW REQUIRED] | Compliance / core marketplace and Payments · Founder + Counsel | Confirm applicable Jordanian intermediary/media, electronic contracting, consumer, IP, custody, tax/invoicing and KYC/AML responsibilities. Compare approved provider models and defer live operations while classification remains open | Written applicability assessment, signed policies and approved provider responsibilities before live transactions | B §§9,15–16,19 |
| D-011 · [LEGAL REVIEW REQUIRED] | Data / Verification, Delivery, Diwan, Support · Counsel + Security | Define identity evidence, portfolio and raw footage processing, hosting/transfers, retention, deletion, legal hold, evidence access and incident response. [PROPOSED] Synthetic evidence until approved | Data inventory, operational roles and technical access requirements before real sensitive data. Cost and policy shape storage design | B §§11,13.1,15,18 FR-010/036/040–042 BR-06/26/39/42–44 |
| D-012 · [TO VALIDATE] | Commercial / marketplace and fees · Product + Finance | Confirm target niche, rate, creator fee, deduction bearer, refund treatment and economics. Test B’s scenarios against actual provider quote and pilot evidence. [PROPOSED] Preserve configurable fee snapshot and approval requirement | Written fee policy and pricing evidence before production charges. Set sourcing/pilot cohort and success criteria | B §§4–10,16–18 FR-026/029 BR-29–31 |
| D-013 · [DECISION REQUIRED] | Quality / platform, media and analytics · Engineering + Product | Agree service level, latency, storage, support, accessibility, localization, backup and restore targets. [PROPOSED] Set pilot baselines and validate against load/budget | Measurable NFRs and operating thresholds with owner, method and evidence. Define eligible paid-project metric including reversals | B §§13.1,16–19 |
| D-014 · [TO VERIFY] | Evidence / data and architecture · Engineering | Determine whether original ERD/DBML and a code repo are available. Retrieve relevant revision if available or proceed from approved source requirements with a documented provenance gap | Historical design review if supplied. Scope and entity integrity govern schema size. No table-level findings until source is provided | B §§13,19, T §§3,7, H |
| D-015 · [APPROVED] | Process / Requirements Engineering · Project Owner | Begin Phase 01 after the owner’s instruction to continue. Holding at discovery was the pending alternative. | Issue the Phase 01 review pack. Phase 02 remains subject to the staged review process. | Owner message «كمل», 24 November 2026 |
| D-016 · [APPROVED] | Product / Verification, Payments · Product Owner | Require professional Editor verification and electronic payments in the public MVP. Optional editor vetting and manual-only payment are outside this MVP baseline. | Document eligibility and electronic payment behavior. Verification triggers, provider selection, fees, settlement and operating policy remain open under D-003/005/006/010/012. | Earlier owner decision as recorded in A, 17 October 2026 |
| D-017 · [DECISION REQUIRED] | Release / Pilot · Product Owner | Choose a Jobs-first Pilot or a Jobs + Services Pilot. [PROPOSED] Begin with Jobs to test the shared transaction and operations flow through one entry path. | Pilot sequence only. Confirm Services entry criteria, staffing and first cohort scope. The public MVP includes both paths under D-001. | Phase 01 direction, 24 November 2026. B §§12/16 |
| D-018 · [APPROVED] | Process / System Analysis · Project Owner | Begin Phase 02 after the owner instructed the team to continue and start. | Issue the system-analysis review package. D-002–014 and D-017 retain their open status. Phase 03 depends on approval of its applicable assumptions. | Owner messages «كمل» and «ابدا», 24 November 2026 |
| D-019 · [APPROVED] | Process / Database Engineering · Project Owner | Begin Phase 03 following the request to continue. | Review draft design. D-004 and D-007 remain open. Migration readiness requires applicable policy and engine verification. | Owner message «كمل», 24 November 2026 |
| D-020 · [APPROVED] | Schema authority · Project Owner | Adopt masteroiece.pdf as reference for the supplied database design. | Use work_items and visible schema names. Prior DBML is historical. Confirm original DDL before migrations. | Owner «اوك» after schema confirmation |
| D-021 · [APPROVED] | Phase entry · Project Owner | Begin Phase 04 on the approved schema reference. | Architecture is for review. Separate acceptance of schema extensions and open policies. | Owner «يلا» |
| D-022 · [APPROVED] | Phase entry · Project Owner | Begin Phase 05 on the approved PDF schema reference. | API operations are candidate contracts. Pending business and provider decisions retain their status. | Owner «كمل» |
| D-023 · [APPROVED] | Phase entry · Project Owner | Start Phase 06. | Payment design remains proposed. No provider or custody decision was approved. | Owner «ابدا» |
| D-024 · [APPROVED] | Phase entry · Project Owner | Begin Phase 07 Testing and QA planning. | Draft test evidence requirements and keep execution results unset until actual runs. | Owner «كمل» |
| D-025 · [APPROVED] | Phase entry · Project Owner | Begin Phase 08 development planning. | Issue implementation roadmap, backlog and deployment planning. Outstanding scope and implementation decisions stay open. | Owner «كمل» |

## How to record an approval

Append an entry with decision ID, date, approver, selected option, source of approval, effective release, affected FR/BR IDs and follow-up owner. For a revision, log the superseding decision and update the impacted documents and acceptance criteria. Keep rationale and earlier status in the history.

## Approval history

| ID | Recorded date | Approval basis | Effective decision |
| --- | --- | --- | --- |
| D-000 | 2026-11-24 | Explicit instructions in T §§12–13 | Deliver initial discovery documents, review unresolved decisions and start Phase 01 after approval |
| D-001 | 2026-10-17 (reported) | Earlier MVP decision recorded in project context and carried forward in the conversation | Jobs and Services in the public MVP. Exact Pilot composition remains D-017 |
| D-015 | 2026-11-24 | Owner instructed «كمل» after the Phase 01 approval request | Begin Requirements Engineering |
| D-016 | 2026-10-17 (reported) | Earlier MVP decision recorded in project context | Electronic payments and mandatory professional Editor verification |
| D-018 | 2026-11-24 | Instructions «كمل» then «ابدا» | Begin System Analysis |
| D-019 | 2026-11-24 | Owner instructed «كمل» after Phase 02 delivery | Begin Database Engineering |
| D-020 | 2026-11-24 | Owner confirmed uploaded schema reference | masteroiece.pdf governs schema mapping |
| D-021 | 2026-11-24 | Owner asked to continue | Start Phase 04 |
| D-022 | 2026-11-24 | Owner asked to continue | Start Phase 05 |
| D-023 | 2026-11-24 | Owner asked to begin | Start Phase 06 |
| D-024 | 2026-11-24 | Owner asked to continue | Begin Phase 07 |
| D-025 | 2026-11-24 | Owner asked to continue | Begin Phase 08 |

## Change history

| Version | Date | Change |
| --- | --- | --- |
| 0.1.0 | 2026-11-24 | Added discovery decisions and approval procedure |
| 0.2.0 | 2026-11-24 | Recorded the previous public MVP direction and Phase 01 approval. Opened the independent Pilot decision |
| 0.3.0 | 2026-11-24 | Recorded authorization to begin Phase 02 and preserved pending product and policy decisions |
| 0.4.0 | 2026-11-24 | Recorded Phase 03 entry and preserved the open database design choices |
| 0.5.0 | 2026-11-24 | Recorded schema authority and architecture entry |
| 0.6.0 | 2026-11-24 | Recorded API design entry |
| 0.7.0 | 2026-11-24 | Recorded payment engineering entry |
| 0.8.0 | 2026-11-24 | Recorded QA planning entry |
| 0.9.0 | 2026-11-24 | Recorded development planning entry |
