# Frame Implementation Roadmap

**Version 0.9.0 · 24 November 2026 · Proposed execution baseline**

The product starts in Jordan and the public MVP includes Jobs and Services, professional Editor verification and electronic payments. Source of physical names is masteroiece.pdf under D-020. Phase 03 DBML remains historical. Work estimates and release gates use the [backlog](Frame_Product_Backlog.xlsx) and Phase 07 QA plan. No calendar schedule or staffing commitment is assumed.

| Increment | Deliverables and dependencies | Exit evidence |
| --- | --- | --- |
| 0. Bootstrap and schema evidence | BL-01 and BL-02. Align Laravel, PHP, database and CI. Obtain original DDL or DBML and compare to work_items baseline. Begin provider qualification BL-14 in parallel | Clean checkout and CI run. Relevant keys, states and constraints verified on chosen engine before committing migrations |
| 1. Access and verification | BL-03–07 and BL-26. Authentication, recovery, policies, profiles, portfolio and private evidence. Approve verification and scoped staff persistence | Both public roles can sign in. Private evidence and staff scopes pass QA. Verification eligibility is enforceable |
| 2. Marketplace intake | BL-08–12. Jobs and proposals, Editor Services and Service Requests. Develop intake branches in parallel when dependencies clear and share one work_items acceptance command | Both types produce one authorized commitment per work item, including concurrent selection and stale inputs |
| 3. Agreement and delivery | BL-13, BL-15, BL-17, BL-18 and BL-22. Versioned terms, synthetic funding, activation, private submissions and exact-version acceptance. BL-16 remains conditional | One effective version and trusted funding permit one activation. Evidence and scoped communication survive retry and worker failure |
| 4. Financial and operational closure | BL-19–21, BL-23 and BL-27. Provider integration and reconciliation, case/refund separation, qualifying reviews and reporting. BL-24/25 depend on release scope | Reconciled sandbox outcomes, exception queues and independent authorization. Agreements, accepted work and payouts remain traceable |
| 5. Staging, pilot and public readiness | BL-28–32, with localization and environment setup started earlier. Verify privacy, recovery, capacity and stakeholder acceptance. Controlled live enablement follows legal and provider gates | Enabled Pilot scope passes required QA. Both Jobs and Services meet the public MVP gate. Release evidence includes restore and incident ownership |

## Parallel work and decisions

Infrastructure can start with BL-01. D-009 affects runtime and database parity. Original schema evidence D-014 shapes BL-02. D-003 and D-008 establish eligibility and staff grants. D-004 determines version and assent persistence. D-005, D-007, D-010 and D-012 govern provider selection, transaction unit, law and approved economics. D-006 governs cancellations, disputes and reviews. D-011 governs private evidence and retention. D-013 governs accessibility, performance and recovery. D-002/017 govern optional and Pilot sequencing. Use trusted synthetic funding for initial vertical slices. Treat real-money compatibility and rollout as separate acceptance gates.

## First implementation slice

Initialize the Frame repository, document the verified runtime and target engine, add a CI boot and test check, and capture physical schema evidence. Prepare the initial accounts slice only after agreeing minimum columns, unique identity, account status and role behavior. For each change, explain request-to-policy-to-action-to-persistence flow, demonstrate with a synthetic fixture and link regression evidence. Limit each PR to a reviewable outcome. Review the next dependency before assigning additional scope. Size estimates in the backlog are relative complexity only.
