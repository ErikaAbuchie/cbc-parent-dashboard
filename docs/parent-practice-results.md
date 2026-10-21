# Practice marks on the parent report — 21 October 2026

## Implemented module

The main parent report now displays **Recent learner practice results**. Each entry shows the topic, correct answers out of total questions, completion date/time in Africa/Nairobi (EAT), and a **Review answers** link. The link opens the saved attempt on the practice page, subject to the existing parent access checks. Invalid review links show an explanatory message; unavailable attempts return a controlled error.

The section reuses the protected practice API. No extra database tables, migrations, SMS messages or model calls are required. Existing completed attempts appear automatically. Parents can refresh the practice section independently. Empty and unavailable states distinguish no completed attempts from a failed request. The report's teacher results heading explicitly identifies the teacher competency results. Quiz marks remain separate from EE/ME/AE/BE ratings and do not affect the competency overview or AI prompt.

The API shows up to five completed attempts, ordered by completion timestamp descending with ID as a tie-breaker. This corrects the previous ordering by attempt creation ID. Attempts are restricted to the authenticated learner, active report and currently eligible report topics; unfinished attempts and legacy rows without question snapshots are excluded. Changing the report scope can remove a topic's results from this view. The present pilot supports Whole Numbers and Addition only. It is a recent-results list, not a complete archive or progress trend.

Practice marks and dates are included in the print layout when loaded; action links and buttons are hidden. Print-preview layout inspection of this new section remains pending. No quiz-derived competency conversion or teacher marks dashboard is added.

## Verification

The full backend suite passed **54 tests / 445 assertions**. The added regression test covers five-result limit, completion-time ordering, correct-count/total calculation, incomplete/legacy exclusion, other learner/report/topic exclusion, and removal after the report scope changes. Existing practice tests continue to confirm teacher ratings are unchanged.

The user previously confirmed the practice quiz worked and the teacher rating remained unchanged. That was verification of the earlier practice module, not visual verification of this new report section. The Nuxt production build passed after retrying outside the sandbox; the initial run hit a Windows EPERM while creating a generated dependency folder. Browser visual and direct-link verification remain manual checks.

## Manual check

1. Open the parent report used for the completed quiz. No replacement code is needed if access is still valid.
2. Find **Recent learner practice results**. Confirm the mark matches the result on the practice page (for example, 2 of 3 correct).
3. Select **Review answers** and confirm the matching saved attempt opens directly with its feedback.
4. Return to the report. Complete another attempt if needed, then use **Refresh practice results** to see the new entry first.
5. Confirm the teacher competency rating is unchanged and the practice mark is clearly separate.
6. Check print preview includes loaded marks and dates but no practice action controls.

## Focused commits

```powershell
cd C:\Users\eryka\OneDrive\Documents\cbc-parent-dashboard

git add -- backend/app/Http/Controllers/ParentPracticeController.php backend/tests/Feature/ParentPracticeTest.php
git commit -m "Order recent practice marks by completion and verify report isolation"

git add -- frontend/app/components/ParentPracticeResults.vue frontend/app/pages/parent/report.vue frontend/app/pages/parent/practice.vue
git commit -m "Show recent practice marks and answer review links on parent reports"

git add -- docs/parent-practice-results.md
git commit -m "Document parent practice results module and checks"

git push origin main
git status
```
