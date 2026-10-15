# Sprint II implementation and initial testing record

Prepared 15 October 2026 (Africa/Nairobi). Review is due this week; confirm the exact review date with the supervisor.

## Scope and environment

The implemented workflow is teacher login, school-scoped class selection, grade-specific curriculum selection, competency entry, validation, database persistence, and display of saved results. Assessment records can be edited by the teacher who recorded them.

The application uses Nuxt 4.5.2, Vue 3, Laravel 12.69.2, PHP 8.2.12, Sanctum API tokens, and PostgreSQL locally. Automated feature tests use an isolated SQLite in-memory database. Passing these tests does not establish PostgreSQL concurrency performance or real-person usability.

Implementation baseline: commit `9749fb2`; this evidence step adds a full-class workflow regression test. Test data consists of CBC Demo Primary School, Grade 5 Demo, and three simulated learners. No real learner records or parent phone numbers are used.

## Evidence files

- [Backend test execution output](evidence/2026-10-15-backend-tests.txt): fresh execution on 15 October 2026.
- [Assessment entry browser screenshot](evidence/assessment-entry-preview.png): captured during the earlier implementation verification, before this evidence step. It shows Whole Numbers, Term 1 2026, and the three persisted ratings ME, AE, EE.
- Automated checks: `backend/tests/Feature/TeacherAuthTest.php`, `TeacherAssessmentTest.php`, `SprintTwoDemoSeederTest.php`, and `GradeFiveMathematicsSeederTest.php`.

## Executed workflow checks

Fresh automated run on 15 October 2026: **18 tests passed, 98 assertions**. The captured runner duration was 21.68 seconds; this is suite execution time, not an application response-time measurement.

| ID | Check | Expected result | Observed result | Evidence |
| --- | --- | --- | --- | --- |
| WF-01 | Sign in with Demo Teacher | Authenticated welcome page | Welcome page displayed in earlier browser verification | Browser verification recorded during implementation; no separate screenshot retained |
| WF-02 | Choose Grade 5 Demo, Mathematics, Numbers, Whole Numbers | Correct curriculum and three learner rows | Correct choices and all three learners displayed | Browser screenshot |
| WF-03 | Enter ME, AE, EE and a remark, then save | Save confirmation and persisted results | Three assessments saved and reloaded; results displayed | Browser verification and screenshot |
| WF-04 | Load the same selection again | Previously saved values return | ME, AE, EE and the remark returned from a fresh request | Browser verification and screenshot |
| WF-05 | Save a full class, change one rating, save again | Three records remain; updated rating returned | Passed: three records remained and learner 1 returned EE after update | `test_full_class_workflow_persists_updates_and_separates_terms` |
| WF-06 | Request another term | Earlier term's results are excluded | Passed: Term 2 returned zero records while Term 1 records remained | Same automated test as WF-05 |

WF-01 through WF-04 were developer browser checks against the local Nuxt/Laravel/PostgreSQL integration. WF-05 and WF-06 are automated API/database checks, not browser observations. A new manual Addition scenario is pending below.

## Validation and access-control coverage

The saved execution output records the actual pass/fail result for each automated test. Coverage includes invalid credentials, expired tokens, logout revocation, login throttling, invalid and duplicate learner entries, invalid terms, cross-school reads/writes, cross-class learners, wrong-grade curriculum, teacher record isolation, and rejection of admin or unassigned accounts. Tests verify that invalid batches create no partial records.

## Corrections made during implementation

| Issue | Cause | Correction | Verification |
| --- | --- | --- | --- |
| Creating Demo Teacher failed because `users.role` did not exist | An already-run migration had been edited; the database was unchanged | Added a separate migration for `school_id` and `role`; restored the original users migration | Local migration succeeded and teacher login worked |
| Opening the login API URL produced HTTP 405 | Browser navigation sent GET to a POST-only endpoint | Used POST for API testing and implemented the browser login form | Successful PowerShell login and browser login |
| Browser login returned HTTP 502 | Laravel was not listening on port 8000 | Started Laravel alongside Nuxt on the configured port | Developer checked connection refusal; Erika subsequently confirmed login worked |

## Manual review rehearsal to complete

Keep Laravel on port 8000 and Nuxt on port 3000 running. Log in, open Enter assessments, and choose Grade 5 Demo / Mathematics / Numbers / Addition / Term 1 / 2026. Use only the simulated learners.

| ID | Action | Expected result | Actual result | Status |
| --- | --- | --- | --- | --- |
| MAN-01 | Load learners | Three learners appear; any previously saved Addition ratings load | Not yet recorded | Pending |
| MAN-02 | Enter ME, AE, EE and optional remarks; save | Confirmation and three saved results | Not yet recorded | Pending |
| MAN-03 | Return to dashboard, reopen assessment entry, and load the same selection | Same ratings and remarks return | Not yet recorded | Pending |
| MAN-04 | Change learner 1 from ME to EE and save | Learner 1 updates; other ratings remain | Not yet recorded | Pending |

Save screenshots as `evidence/manual-addition-saved.png` and `evidence/manual-addition-updated.png` after completing the matching actions. Replace pending results with the actual observation and date; do not mark a check as passed solely because its expected result is written here. Do not include passwords, tokens, or environment files in screenshots.

## Limits and remaining work

- Teachers currently access classes within their school. Specific teacher-to-class/learning-area assignment is not implemented.
- Only Grade 5 Mathematics curriculum names are seeded. Other grades and learning areas remain pending.
- The assessment form currently requires a rating for every displayed learner before a class save. Report finalisation and term-completeness checks are not implemented.
- Charts, parent access, SMS, and local-model explanations are later work. Existing AIService code is not evidence of a working parent explanation integration.
- No measured response-time evaluation, concurrent PostgreSQL load test, real-person usability study, or SUS result is claimed.
- Chapter 5 updates, Chapter 4 reconciliation, clear setup documentation, and supervisor priority validation remain open.

## Repeat the automated checks

From PowerShell in `backend`:

```powershell
php artisan test --no-ansi
```

The test configuration uses SQLite `:memory:`. Do not change the test connection to the development PostgreSQL database; the tests refresh their schema.
