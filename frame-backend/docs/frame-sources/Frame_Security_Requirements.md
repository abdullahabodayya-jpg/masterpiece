# Frame Security Requirements

**Version 0.5.0 · 24 November 2026 · Phase 04 review draft**

Sources: masteroiece.pdf (approved schema reference), Frame Business Documentation, Phase 01 requirements and Phase 02 analysis subject to the schema reconciliation.


| Boundary | Required defensive behavior and evidence |
| --- | --- |
| Accounts | Use framework password hashing, bounded recovery proof, login throttling, generic recovery responses and session regeneration. Configure Secure, HttpOnly and applicable SameSite cookies. Revoke access after suspension and privileged grant changes |
| First-party API | Apply authentication, CSRF for cookie-backed mutations and origin allow-listing where cross-origin hosting is needed. Separate provider ingress from browser session authentication |
| Staff | Require MFA for privileged access, narrow case assignments and independent grants. Separate verification, moderation, financial execution and administration. Audit privilege changes. Recheck revocation on reads and commands |
| Resource policies | Enforce ownership or membership on detail, lists, nested resources, exports and storage grants. Apply default deny and explicit scope. Test unrelated and suspended identities and revoked staff grants |
| Input and output | Validate approved types, lengths, state and values. Use parameterized queries, escaped views, allowlisted writable attributes and explicit JSON serialization. Avoid rendering raw user HTML |
| Private content | Restrict upload purpose, size and allowed formats. Use storage-generated keys and quarantine until inspected. Authorize downloads at issuance, use short expiry and keep evidence separate from public portfolio media |
| Financial events | Use verified provider authentication as specified, trusted source evidence, event deduplication and matching amount/currency/reference. Persist receipt before acknowledged processing. Log no full card data. Keep operation and signed evidence access narrowly scoped |
| Secrets | Use environment-scoped secrets and rotate provider and application credentials under tested procedures. Redact credentials, headers, evidence and raw payment payload from application logs |
| Audit and privacy | Record actor, action, target, time, outcome and correlation. Limit access and protect history. Define retention, approved deletion and legal holds. Reject unapproved production evidence until the access model and storage policy are tested |
| Abuse and incidents | Apply per-resource quotas and rate limits, monitor suspicious activity and queue/provider exceptions. Document who can contain incident, revoke sessions, rotate credentials and authorize reconciliation |

[OWASP Authorization](https://cheatsheetseries.owasp.org/cheatsheets/Authorization_Cheat_Sheet.html) · [OWASP File Upload](https://cheatsheetseries.owasp.org/cheatsheets/File_Upload_Cheat_Sheet.html) · [OWASP Logging](https://cheatsheetseries.owasp.org/cheatsheets/Logging_Cheat_Sheet.html)
