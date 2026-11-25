# Frame: Business Rules

| Field | Detail |
| --- | --- |
| Version and date | 0.2.0 · 24 November 2026 |
| Status | [PROPOSED] Phase 01 review draft |
| Purpose | Specify marketplace policy, permitted actions and exceptions |
| Scope | Public accounts, Jobs, Services, agreements, delivery, payments, support and operations |
| Sources | B = Frame_Business_Documentation.docx v1.0. T = Pasted text.txt, Phase 01 brief. A = owner direction in the Frame conversation, including the recorded earlier MVP choice and approval to proceed. |
| Dependencies | [PRD](Frame_PRD.md), [User Stories](Frame_User_Stories.md), [Glossary](Frame_Glossary.md) |
| Approved decisions | D-015 authorizes Phase 01. D-001 records the established public MVP inclusion of Jobs and Services, D-016 records electronic payments and mandatory professional Editor verification |
| Open decisions | D-002–008, D-010–013 and D-017. See [Decision Log](Frame_Decision_Log.md) |
| Requirement references | FR-001–044, FR-SVC-001/002, FR-MLT-001, FR-MET-001, NFR catalog and BR-01–53 |

## Rule catalog

BR-01–44 retain the IDs in B §14 and expand them into proposed operating rules. BR-45–53 specify Phase 01 gaps. Product principles approved in D-001/016 apply to the catalog. Detailed operating policy remains open where the decision log identifies a dependency.

### BR-01 Account eligibility

| Field | Detail |
| --- | --- |
| Actor | Public user |
| Preconditions | Account exists when acting on a protected resource |
| Trigger | Registration, login, recovery or protected action |
| Expected behavior | Validate credentials or recovery proof and check current activation/status on protected commands |
| Restrictions | No public staff provisioning or client-set eligibility |
| Exceptions | Generic recovery response and approved case access |
| Related requirements | FR-001, FR-002, FR-003, NFR-SEC-001 |
| Approval status and source | [PROPOSED]. B §14. |

### BR-02 Independent verification states

| Field | Detail |
| --- | --- |
| Actor | Public user and Verification Specialist |
| Preconditions | A role-specific account exists |
| Trigger | Account activation, identity check or specialty decision |
| Expected behavior | Maintain distinct account, identity and professional specialty states |
| Restrictions | One state cannot confer another status |
| Exceptions | Re-evaluate specialty eligibility on revocation |
| Related requirements | FR-001, FR-004, FR-005 |
| Approval status and source | [PROPOSED]. B §14. |

### BR-03 Verification decision authority

| Field | Detail |
| --- | --- |
| Actor | Verification Specialist |
| Preconditions | Assigned request and authorized staff access |
| Trigger | Request evidence, approve or reject |
| Expected behavior | Verify assignment and allowed transition, record outcome and inform applicant |
| Restrictions | The applicant and unauthorized staff cannot self-approve or override |
| Exceptions | Route conflicts or appeals to an authorized reviewer |
| Related requirements | FR-009 |
| Approval status and source | [PROPOSED]. B §14. |

### BR-04 Specialty badges

| Field | Detail |
| --- | --- |
| Actor | Editor and Verification Specialist |
| Preconditions | Current approved specialty evidence |
| Trigger | Publish or display a verification badge |
| Expected behavior | Identify the applicable specialty and current verification meaning |
| Restrictions | Do not imply state licensing or guaranteed performance |
| Exceptions | Remove or annotate expired or revoked status under policy |
| Related requirements | FR-004, FR-005, FR-007 |
| Approval status and source | [PROPOSED]. B §14. |

### BR-05 Resource and role scope

| Field | Detail |
| --- | --- |
| Actor | Public user and staff |
| Preconditions | Authenticated actor and identifiable resource |
| Trigger | Read, list, create, update, download or decide |
| Expected behavior | Check role, ownership, participation or assignment before exposing the operation |
| Restrictions | No broad access from role labels or identifier possession alone |
| Exceptions | Explicit limited delegation only under authorized policy |
| Related requirements | FR-004, FR-006, FR-012, FR-016, FR-017, FR-032, FR-036, FR-037, FR-042, FR-044, NFR-SEC-001 |
| Approval status and source | [PROPOSED]. B §14. |

### BR-06 Attributable staff activity

| Field | Detail |
| --- | --- |
| Actor | Authorized staff |
| Preconditions | Active staff grant |
| Trigger | Sensitive access, administrative change or decision |
| Expected behavior | Preserve actor, action, target, outcome and time |
| Restrictions | Avoid logging credentials or unrestricted evidence |
| Exceptions | Record queued or delegated action with accountable identity |
| Related requirements | FR-010, FR-037, FR-041, NFR-SEC-001, NFR-AUD-001 |
| Approval status and source | [PROPOSED]. B §14. |

### BR-07 Suspension effects

| Field | Detail |
| --- | --- |
| Actor | Authorized staff and suspended user |
| Preconditions | Suspendable account or case under policy |
| Trigger | Suspension, reinstatement or restricted operation |
| Expected behavior | Block new restricted actions and recheck access |
| Restrictions | Existing commitments and records retain required history |
| Exceptions | Existing financial obligations or support access follow approved exception policy |
| Related requirements | FR-002, FR-011 |
| Approval status and source | [PROPOSED]. B §14. |

### BR-08 Versioned specialty criteria

| Field | Detail |
| --- | --- |
| Actor | Verification Specialist and Editor |
| Preconditions | Defined specialty and approved evidence criteria |
| Trigger | Submit application or revise criteria |
| Expected behavior | Apply a named criteria version and permitted evidence checklist |
| Restrictions | Do not silently change the criteria underlying prior decisions |
| Exceptions | Unpublished criteria block submission for affected specialty |
| Related requirements | FR-008 |
| Approval status and source | [PROPOSED]. B §14. |

### BR-09 Verification history

| Field | Detail |
| --- | --- |
| Actor | Verification Specialist |
| Preconditions | Authorized review and evidence reference |
| Trigger | Review decision |
| Expected behavior | Record reviewer, reason, time, criteria version and affected specialty |
| Restrictions | Past decisions remain traceable |
| Exceptions | Corrections use a recorded follow-up decision |
| Related requirements | FR-009, FR-010, NFR-AUD-001 |
| Approval status and source | [PROPOSED]. B §14. |

### BR-10 Verification status visibility

| Field | Detail |
| --- | --- |
| Actor | Editor and Verification Specialist |
| Preconditions | Request linked to applicant |
| Trigger | Submission or meaningful status change |
| Expected behavior | Show status, missing information and policy-approved next step |
| Restrictions | Protect reviewer-only evidence and other applicants’ data |
| Exceptions | Escalate disputed or stalled status through a case |
| Related requirements | FR-008, FR-010, FR-033, NFR-ACC-001, NFR-LOC-001 |
| Approval status and source | [PROPOSED]. B §14. |

### BR-11 Expiry and revocation

| Field | Detail |
| --- | --- |
| Actor | Verification Specialist |
| Preconditions | Verification currently valid or eligible for revisit |
| Trigger | Policy expiry, invalid evidence or loss of eligibility |
| Expected behavior | Restrict affected new actions, retain history and notify the Editor |
| Restrictions | Do not silently void existing obligations |
| Exceptions | Route active work to approved continuation policy |
| Related requirements | FR-011 |
| Approval status and source | [PROPOSED]. B §14. |

### BR-12 Appeals and re-review

| Field | Detail |
| --- | --- |
| Actor | Editor and Verification Specialist |
| Preconditions | Appealable decision and eligible actor |
| Trigger | Appeal or re-review request |
| Expected behavior | Assign authorized review and record reason and outcome |
| Restrictions | Respect deadline, evidence scope and conflict-of-duty policy |
| Exceptions | Pending policy limits to intake and manual referral |
| Related requirements | FR-009, FR-011 |
| Approval status and source | [PROPOSED]. B §14. |

### BR-13 Structured job brief

| Field | Detail |
| --- | --- |
| Actor | Creator |
| Preconditions | Eligible account and owned draft |
| Trigger | Create, edit or publish a brief |
| Expected behavior | Capture expected output, footage duration, technical requirements, deadline, budget, revisions and rights |
| Restrictions | Require all approved fields before public posting |
| Exceptions | Incomplete brief can be retained as a draft |
| Related requirements | FR-012, FR-013 |
| Approval status and source | [PROPOSED]. B §14. |

### BR-14 Hire and activation conditions

| Field | Detail |
| --- | --- |
| Actor | Creator and Editor |
| Preconditions | Eligible proposal or service request |
| Trigger | Select, accept or activate |
| Expected behavior | Confirm Editor and terms, then check effective agreement and funding before work activation |
| Restrictions | A shortlist or selection cannot grant work access |
| Exceptions | Expired or superseded commitment follows offer policy |
| Related requirements | FR-018, FR-019, FR-021 |
| Approval status and source | [PROPOSED]. B §14. |

### BR-15 No unilateral scope changes

| Field | Detail |
| --- | --- |
| Actor | Creator and Editor |
| Preconditions | Existing commercial commitment |
| Trigger | Attempt to change scope or price |
| Expected behavior | Require a versioned change with authorized agreement |
| Restrictions | Neither participant can silently override accepted terms |
| Exceptions | Apply mandatory remedies through the appropriate case path |
| Related requirements | FR-020, FR-023 |
| Approval status and source | [PROPOSED]. B §14. |

### BR-16 Recorded amendments

| Field | Detail |
| --- | --- |
| Actor | Creator and Editor |
| Preconditions | Existing version and eligible participants |
| Trigger | Propose or accept a change |
| Expected behavior | Record changed terms, effective version and required assent |
| Restrictions | Reject stale acceptance and protect previously effective records |
| Exceptions | If funding changes, evaluate additional obligations under approved policy |
| Related requirements | FR-021, FR-023 |
| Approval status and source | [PROPOSED]. B §14. |

### BR-17 Application eligibility

| Field | Detail |
| --- | --- |
| Actor | Editor |
| Preconditions | Open eligible job and applicable specialty approval |
| Trigger | Submit or edit proposal |
| Expected behavior | Validate role, account, verification, deadline and permitted proposal version |
| Restrictions | Avoid duplicate active proposals or bypass of closure policy |
| Exceptions | Withdrawal and reapplication follow published policy |
| Related requirements | FR-014, FR-015, FR-016 |
| Approval status and source | [PROPOSED]. B §14. |

### BR-18 Closed job restrictions

| Field | Detail |
| --- | --- |
| Actor | Creator and Editor |
| Preconditions | Job in closed, filled or cancelled state |
| Trigger | Apply or request reopening |
| Expected behavior | Reject new proposals unless owner-authorized reopening is permitted |
| Restrictions | Preserve status and prior proposal history |
| Exceptions | Policy may require reposting and a new application window |
| Related requirements | FR-013, FR-014, FR-015, FR-017, FR-024 |
| Approval status and source | [PROPOSED]. B §14. |

### BR-19 Repost lineage

| Field | Detail |
| --- | --- |
| Actor | Creator |
| Preconditions | Owned eligible prior job |
| Trigger | Repost |
| Expected behavior | Create a new brief linked to its origin and retain historical records |
| Restrictions | Do not implicitly carry prior offers, contracts or payments |
| Exceptions | Denied when prior job state or dispute restrictions bar reposting |
| Related requirements | FR-024 |
| Approval status and source | [PROPOSED]. B §14. |

### BR-20 Work authorization

| Field | Detail |
| --- | --- |
| Actor | Creator, Editor and authorized provider source |
| Preconditions | Commercial terms and applicable payment obligation recorded |
| Trigger | Request activation or restricted project material |
| Expected behavior | Require effective bilateral agreement and authoritative funding confirmation under approved terms |
| Restrictions | Selection, payment redirect and provider-unknown state cannot unlock work |
| Exceptions | Sandbox uses synthetic confirmation and has no real financial effect |
| Related requirements | FR-019, FR-021, FR-022, FR-025, FR-027 |
| Approval status and source | [PROPOSED] · Live implementation requires approved payment model where applicable. B §14. |

### BR-21 Agreement terms

| Field | Detail |
| --- | --- |
| Actor | Creator and Editor |
| Preconditions | Identified parties and transaction origin |
| Trigger | Prepare or accept effective version |
| Expected behavior | Capture price, schedule, scope, revision limits, confidentiality, delivery and rights |
| Restrictions | Acceptance applies only to the version presented |
| Exceptions | A material change requires the approved amendment path |
| Related requirements | FR-020, FR-021, FR-023, FR-026, NFR-LOC-001 |
| Approval status and source | [PROPOSED]. B §14. |

### BR-22 Source files and third-party rights

| Field | Detail |
| --- | --- |
| Actor | Creator and Editor |
| Preconditions | Proposed deliverables and material identified |
| Trigger | Specify or change agreement scope |
| Expected behavior | Record editable-file handling, third-party licenses and usage rights explicitly |
| Restrictions | Do not assume source-file transfer or portfolio reuse |
| Exceptions | Unknown rights require clarification before agreement acceptance |
| Related requirements | FR-020 |
| Approval status and source | [PROPOSED]. B §14. |

### BR-23 Delivery history

| Field | Detail |
| --- | --- |
| Actor | Editor and Creator |
| Preconditions | Eligible active project and authorized material |
| Trigger | Submit, review, accept or request revision |
| Expected behavior | Record exact delivery version, author, timestamp and review decision |
| Restrictions | Preserve superseded and accepted evidence under retention rules |
| Exceptions | Incomplete or quarantined uploads remain unavailable for acceptance |
| Related requirements | FR-022, NFR-PER-001, NFR-SCA-001 |
| Approval status and source | [PROPOSED]. B §14. |

### BR-24 Revision scope

| Field | Detail |
| --- | --- |
| Actor | Creator and Editor |
| Preconditions | Submitted delivery and applicable versioned terms |
| Trigger | Request or agree a revision |
| Expected behavior | Check in-scope allowance and record related delivery version and requested change |
| Restrictions | Out-of-scope work requires change agreement or quotation |
| Exceptions | Route disputed scope or allowance to the case process |
| Related requirements | FR-022 |
| Approval status and source | [PROPOSED]. B §14. |

### BR-25 Explicit time limits

| Field | Detail |
| --- | --- |
| Actor | Creator, Editor and Operations |
| Preconditions | Applicable accepted policy |
| Trigger | Display deadline, inactivity threshold, cancellation or dispute window |
| Expected behavior | Persist and expose agreed deadline and next action |
| Restrictions | No automatic acceptance or refund based on undefined policy |
| Exceptions | Authorized handling of exceptions follows D-006 |
| Related requirements | FR-030 |
| Approval status and source | [PROPOSED]. B §14. |

### BR-26 Portfolio reuse

| Field | Detail |
| --- | --- |
| Actor | Editor |
| Preconditions | Ownership or approved reuse permission can be evidenced |
| Trigger | Publish a project-linked sample |
| Expected behavior | Check applicable agreement, license and confidentiality |
| Restrictions | Private footage or identity evidence remains restricted |
| Exceptions | Remove publication or refer a rights dispute under policy |
| Related requirements | FR-007, FR-032, FR-036, NFR-PRI-001 |
| Approval status and source | [PROPOSED]. B §14. |

### BR-27 Trusted payment state

| Field | Detail |
| --- | --- |
| Actor | Payments and authorized reviewer |
| Preconditions | Provider references and obligation known |
| Trigger | Receive verified event or authorized reconciliation |
| Expected behavior | Validate provider identity, amount, currency and current state before updating |
| Restrictions | Client redirect or unauthenticated input cannot settle or activate work |
| Exceptions | Assign mismatches for review and preserve event history |
| Related requirements | FR-025, FR-027, FR-029, NFR-REL-001 |
| Approval status and source | [PROPOSED] · Live implementation requires approved payment model where applicable. B §14. |

### BR-28 Custody boundary

| Field | Detail |
| --- | --- |
| Actor | Payments and Operations |
| Preconditions | Approved model and provider terms |
| Trigger | Configure a flow or describe payment to users |
| Expected behavior | Use approved third-party payment capabilities and accurately describe custody |
| Restrictions | No independent holding, wallet or escrow assumption |
| Exceptions | D-005 and D-010 must be resolved before enabling live flows |
| Related requirements | FR-025 |
| Approval status and source | [PROPOSED] · Live implementation requires approved payment model where applicable. B §14. |

### BR-29 Fee visibility

| Field | Detail |
| --- | --- |
| Actor | Creator, Editor and Payments |
| Preconditions | Versioned price and approved fee policy |
| Trigger | Show terms before commitment or show payment summary |
| Expected behavior | Display currency, Creator total, deductions and expected Editor net using applicable rounding |
| Restrictions | Do not silently swap fee policy after bilateral acceptance |
| Exceptions | Refresh agreement and assent when approved changes affect terms |
| Related requirements | FR-020, FR-025, FR-026, FR-029, NFR-LOC-001 |
| Approval status and source | [PROPOSED]. B §14. |

### BR-30 Refund and dispute policy

| Field | Detail |
| --- | --- |
| Actor | Creator, Editor and authorized staff |
| Preconditions | Identified qualifying project and applicable policy |
| Trigger | File, review or request a remedy |
| Expected behavior | Evaluate deadline, delivery, evidence and provider capability before effect |
| Restrictions | Participant or case reviewer cannot independently override approved limits |
| Exceptions | Unsupported or ambiguous outcomes remain under review |
| Related requirements | FR-028, FR-030 |
| Approval status and source | [PROPOSED] · Live implementation requires approved payment model where applicable. B §14. |

### BR-31 Authorized financial adjustments

| Field | Detail |
| --- | --- |
| Actor | Financial reviewer or provider source |
| Preconditions | Identified record, eligible amount and authority |
| Trigger | Request refund, payout action or correction |
| Expected behavior | Record actor, reason, amount, policy path and provider references. Preserve the original history |
| Restrictions | Customer Service and Moderation receive only their scoped privileges |
| Exceptions | Pending provider outcome stays pending until confirmed or reconciled |
| Related requirements | FR-028, FR-029, FR-031, FR-041, NFR-AUD-001 |
| Approval status and source | [PROPOSED] · Live implementation requires approved payment model where applicable. B §14. |

### BR-32 Separation of decisions and ledger rights

| Field | Detail |
| --- | --- |
| Actor | Dispute reviewer and Financial reviewer |
| Preconditions | Assigned case and explicit scopes |
| Trigger | Recommend a remedy or record approved financial effect |
| Expected behavior | Route financial execution through authorized Payments functions |
| Restrictions | Case assignment does not confer refund, payout or ledger edit permission |
| Exceptions | Second approval applies only where policy requires it |
| Related requirements | FR-028, FR-029, FR-030, FR-037 |
| Approval status and source | [PROPOSED] · Live implementation requires approved payment model where applicable. B §14. |

### BR-33 Reconciliation

| Field | Detail |
| --- | --- |
| Actor | Financial reviewer |
| Preconditions | Provider report or verified event and internal references |
| Trigger | Run comparison or resolve exception |
| Expected behavior | Match records and assign differences, recording decision and evidence |
| Restrictions | No silent financial overwrites or assumption that provider IDs imply an exact match |
| Exceptions | Amounts, currencies and unmatched records route to investigation |
| Related requirements | FR-031, FR-MET-001, NFR-REL-001 |
| Approval status and source | [PROPOSED] · Live implementation requires approved payment model where applicable. B §14. |

### BR-34 Payment communications

| Field | Detail |
| --- | --- |
| Actor | Payments and Communications |
| Preconditions | Meaningful committed financial state change |
| Trigger | Payment, refund, dispute or payout status update |
| Expected behavior | Deliver appropriately scoped notifications with duplicate-safe delivery |
| Restrictions | Avoid exposing private financial evidence in broad notifications |
| Exceptions | Retry delivery separately from the committed financial action |
| Related requirements | FR-033, NFR-SCA-001, NFR-ACC-001, NFR-LOC-001 |
| Approval status and source | [PROPOSED]. B §14. |

### BR-35 Review eligibility

| Field | Detail |
| --- | --- |
| Actor | Creator and Editor |
| Preconditions | Participant and qualifying transaction |
| Trigger | Submit or inspect review eligibility |
| Expected behavior | Apply agreed completion, payment and refund predicates before publication |
| Restrictions | No review by unrelated parties or for ineligible transaction state |
| Exceptions | Late reversals and existing reviews follow D-006 |
| Related requirements | FR-043, FR-MET-001 |
| Approval status and source | [PROPOSED]. B §14. |

### BR-36 Review integrity

| Field | Detail |
| --- | --- |
| Actor | Creator, Editor and Moderator |
| Preconditions | Eligible participant and related subject |
| Trigger | Create, edit or moderate review |
| Expected behavior | Enforce policy-defined uniqueness and investigate manipulated reviews |
| Restrictions | Prevent repeats, fabricated reviews and prohibited incentives |
| Exceptions | Review edit, removal and appeal follow published policy |
| Related requirements | FR-043 |
| Approval status and source | [PROPOSED]. B §14. |

### BR-37 Community conduct

| Field | Detail |
| --- | --- |
| Actor | Public user and Moderator |
| Preconditions | Enabled community surface and applicable guidelines |
| Trigger | Post, comment, report or connect |
| Expected behavior | Apply permitted participation and reporting policy |
| Restrictions | Prohibit guideline violations and unauthorized reuse |
| Exceptions | Temporary restrictions and appeals follow moderation policy |
| Related requirements | FR-034, FR-044 |
| Approval status and source | [PROPOSED]. B §14. |

### BR-38 Reviewable moderation

| Field | Detail |
| --- | --- |
| Actor | Moderator |
| Preconditions | Assigned report or authorized moderation task |
| Trigger | Apply enforcement, no-action or appeal decision |
| Expected behavior | Record reason, policy, actor and scope. Inform permitted participants |
| Restrictions | No financial privileges through moderation |
| Exceptions | Urgent containment can use authorized escalation |
| Related requirements | FR-035, FR-041, FR-044, NFR-AUD-001 |
| Approval status and source | [PROPOSED]. B §14. |

### BR-39 Confidential content handling

| Field | Detail |
| --- | --- |
| Actor | Public user and staff |
| Preconditions | Restricted, personal or copyrighted content |
| Trigger | Publish, message, upload or disclose data |
| Expected behavior | Check purpose, permission, audience and allowed content before disclosure |
| Restrictions | Do not publish confidential project files, private data or unauthorized copyrighted material |
| Exceptions | Authorized case evidence uses separate access and retention |
| Related requirements | FR-006, FR-007, FR-032, FR-034, FR-036, FR-042, FR-044, NFR-PRI-001 |
| Approval status and source | [PROPOSED]. B §14. |

### BR-40 Case record minimums

| Field | Detail |
| --- | --- |
| Actor | Customer Service, Moderator or relevant staff |
| Preconditions | Valid intake channel and subject |
| Trigger | Create or assign a case |
| Expected behavior | Record unique reference, category, status, accountable owner and history |
| Restrictions | Restrict access to case participants and assigned scope |
| Exceptions | Use a dedicated unassigned queue only under approved triage policy |
| Related requirements | FR-030, FR-033, FR-034, FR-035, FR-038, NFR-AVA-001, NFR-ACC-001 |
| Approval status and source | [PROPOSED]. B §14. |

### BR-41 Escalation history

| Field | Detail |
| --- | --- |
| Actor | Support staff and supervisory roles |
| Preconditions | Assigned case eligible for transfer |
| Trigger | Escalate, reassign or close |
| Expected behavior | Record owner transition, reason, evidence, urgency and status |
| Restrictions | No invisible transfer or lost case history |
| Exceptions | Urgent routing follows BR-44 |
| Related requirements | FR-038, FR-039, FR-041, NFR-AUD-001 |
| Approval status and source | [PROPOSED]. B §14. |

### BR-42 Diwan records

| Field | Detail |
| --- | --- |
| Actor | Correspondence Officer and authorized manager |
| Preconditions | Permitted official communication |
| Trigger | Register, route or close a record |
| Expected behavior | Record reference, classification, owner, dates, restricted attachments and status |
| Restrictions | No access outside authorized correspondence scope |
| Exceptions | Follow urgent legal escalation and approved retention or hold |
| Related requirements | FR-040, NFR-PRI-001, NFR-POR-001 |
| Approval status and source | [PROPOSED]. B §14. |

### BR-43 Restricted case and evidence access

| Field | Detail |
| --- | --- |
| Actor | Authorized staff and participants |
| Preconditions | Case or material classified as sensitive |
| Trigger | Inspect, attach, export or download |
| Expected behavior | Enforce assignment and purpose with attributed access where required |
| Restrictions | No broad staff access or uncontrolled file link |
| Exceptions | Urgent access follows approved escalation and audit |
| Related requirements | FR-016, FR-030, FR-035, FR-036, FR-037, FR-038, FR-040, FR-042, NFR-SEC-001, NFR-PRI-001, NFR-POR-001 |
| Approval status and source | [PROPOSED]. B §14. |

### BR-44 Urgent escalation

| Field | Detail |
| --- | --- |
| Actor | Technical staff, Customer Service and Correspondence Officer |
| Preconditions | Credible security or legal emergency |
| Trigger | Report an urgent issue |
| Expected behavior | Route to designated responsible role and preserve transfer history |
| Restrictions | Ordinary queue ordering must not delay the urgent route |
| Exceptions | After containment, return follow-up to case owner under policy |
| Related requirements | FR-039, FR-040, NFR-AVA-001, NFR-SCA-001 |
| Approval status and source | [PROPOSED]. B §14. |

### BR-45 Single commitment and project origin

| Field | Detail |
| --- | --- |
| Actor | Creator, Editor and Marketplace |
| Preconditions | Open single-hire intake and eligible offer |
| Trigger | Accept a Job offer or Service Request commitment |
| Expected behavior | Atomically preserve one winning commitment and trace the project to its origin |
| Restrictions | Repeated or competing actions cannot create duplicate projects or obligations |
| Exceptions | If the first result is uncertain, recover the existing commitment by request identity |
| Related requirements | FR-018, FR-019, FR-SVC-002, NFR-REL-001 |
| Approval status and source | [PROPOSED]. Phase 01 proposed refinement. |

### BR-46 Delivery and completion gate

| Field | Detail |
| --- | --- |
| Actor | Creator, Editor and authorized staff |
| Preconditions | Eligible version, effective agreement and defined policy |
| Trigger | Accept delivery, cancel or complete |
| Expected behavior | Record the exact accepted version and check applicable contract and financial predicates before marking completion or enabling review |
| Restrictions | Acceptance must not implicitly simulate settlement |
| Exceptions | Manual case path handles partial delivery, inactivity and disputed acceptance |
| Related requirements | FR-022, FR-043, FR-MLT-001, FR-MET-001 |
| Approval status and source | [PROPOSED]. Phase 01 proposed refinement. |

### BR-47 Financial operation integrity

| Field | Detail |
| --- | --- |
| Actor | Payments and Financial reviewer |
| Preconditions | Identified obligation and authoritatively observed attempt |
| Trigger | Retry, receive event or record settlement |
| Expected behavior | Deduplicate business effects, preserve amount and currency, track uncertainty and reconcile ambiguous results before replacement |
| Restrictions | No second effect for the same provider event or economic operation |
| Exceptions | Reordered events, provider outage and failed payout go to controlled retry or exception |
| Related requirements | FR-025, FR-027, FR-028, FR-029, FR-031, FR-MLT-001, NFR-AUD-001, NFR-REL-001 |
| Approval status and source | [PROPOSED] · Live implementation requires approved payment model where applicable. Phase 01 proposed refinement. |

### BR-48 Service offer and request eligibility

| Field | Detail |
| --- | --- |
| Actor | Verified Editor and Creator |
| Preconditions | Eligible listing with explicit commercial terms |
| Trigger | Publish, request or accept a service |
| Expected behavior | Record approved offer version and request scope and route a single accepted commitment into the shared agreement and project flow |
| Restrictions | No payment or activation by listing publication, request submission or stale terms alone |
| Exceptions | Availability, expiry, edits, cancellation and withdrawal follow service policy |
| Related requirements | FR-SVC-001, FR-SVC-002 |
| Approval status and source | [APPROVED] Services in public MVP · [PROPOSED] operating rule. Phase 01 proposed refinement. |

### BR-49 Verification at restricted actions

| Field | Detail |
| --- | --- |
| Actor | Editor |
| Preconditions | Active account with applicable professional specialty status evaluated |
| Trigger | Apply, publish an offer, accept a commitment or continue work |
| Expected behavior | Check the D-003 eligibility policy at each relevant action and enforce mandatory professional verification |
| Restrictions | Verification approval for another specialty or expired status cannot substitute where specialty eligibility is required |
| Exceptions | Existing contracts and visibility follow explicit policy on revocation |
| Related requirements | FR-008, FR-011, FR-014, FR-015, FR-SVC-001 |
| Approval status and source | [APPROVED] Mandatory Editor verification · [PROPOSED] action-specific eligibility. Phase 01 proposed refinement. |

### BR-50 Proposal and request decisions

| Field | Detail |
| --- | --- |
| Actor | Creator and Editor |
| Preconditions | Eligible open application or service request |
| Trigger | Withdraw, reject, select or accept |
| Expected behavior | Record actor, reason or status as applicable and notify permitted participants |
| Restrictions | Closed, expired, superseded or previously resolved intake cannot create a second valid commitment |
| Exceptions | Reapplication and offer reopening follow D-001/003/004 |
| Related requirements | FR-015, FR-017, FR-018, FR-SVC-002 |
| Approval status and source | [PROPOSED]. Phase 01 proposed refinement. |

### BR-51 Restricted media handling

| Field | Detail |
| --- | --- |
| Actor | Public user and authorized staff |
| Preconditions | Authorized resource and media class |
| Trigger | Request upload, preview, download or disposition |
| Expected behavior | Validate file purpose, type, size and inspection state. Apply approved storage, access and retention controls |
| Restrictions | Sensitive files cannot be published or accessed without current permission |
| Exceptions | Quarantine, rejected upload, expiry and legal hold follow approved process |
| Related requirements | FR-042, FR-SVC-001, NFR-SEC-001, NFR-PRI-001, NFR-PER-001, NFR-POR-001 |
| Approval status and source | [PROPOSED]. Phase 01 proposed refinement. |

### BR-52 Milestone obligations

| Field | Detail |
| --- | --- |
| Actor | Creator, Editor and Payments |
| Preconditions | Approved milestone feature and agreed signed terms |
| Trigger | Define, submit or fund a milestone |
| Expected behavior | Distinguish delivery tracking from a funded milestone and bind any approved obligation to scoped amounts |
| Restrictions | No per-milestone charge, release or payout without approved settings and capability |
| Exceptions | Project-level funding applies only if chosen in D-007 |
| Related requirements | FR-MLT-001 |
| Approval status and source | [PROPOSED] · Live implementation requires approved payment model where applicable. Phase 01 proposed refinement. |

### BR-53 Qualifying outcome measurement

| Field | Detail |
| --- | --- |
| Actor | Product and Operations |
| Preconditions | Approved KPI definitions and trusted lifecycle facts |
| Trigger | Generate marketplace report |
| Expected behavior | Deduplicate by project and apply eligibility, cutoff and adjustment policy |
| Restrictions | Do not count attempted payment or duplicate provider events as completed paid projects |
| Exceptions | Show policy-defined handling for later refunds, disputes and corrections |
| Related requirements | FR-MET-001 |
| Approval status and source | [PROPOSED]. Phase 01 proposed refinement. |

## Change history

| Version | Date | Change |
| --- | --- | --- |
| 0.2.0 | 2026-11-24 | Expanded the 44 source rules and added nine proposed rules |
