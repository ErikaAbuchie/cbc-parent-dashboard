# Sprint II GitHub Projects update

Use the existing CBC Dashboard Sprints project. Update existing cards when they already cover the work; create new cards only for missing tasks. Keep Sprint I completion cards as history.

## Update the board

1. Open the repository on GitHub, then its Projects tab. If the project is not linked there, open your profile's Projects tab and select CBC Dashboard Sprints.
2. Open each matching card and update its description with the scope and evidence below.
3. Set its Status to Done, In Progress, or Backlog. In a board grouped by Status, moving the card to another column updates that field.
4. For missing cards, use Add item. A draft item is sufficient for planning; repository issues are useful when you want discussions and commit references tied to a specific task.
5. Add a Sprint field if useful and set these cards to Sprint II. Set due dates to the actual review date confirmed by the supervisor, not to next week. Do not invent a deadline.
6. Push the commits before linking the evidence files from GitHub. Use Copy permalink when you want an evidence link pinned to the reviewed version.

## Suggested card status and evidence

| Card | Status | Evidence or acceptance condition |
| --- | --- | --- |
| Teacher login and logout with Sanctum | Done | TeacherAuthTest; login and logout routes; browser login verified |
| Link demo teacher to school and prepare simulated class | Done | SprintTwoDemoSeeder; repeatability and assignment protection tests |
| Seed grade-specific Mathematics curriculum | Done | GradeFiveMathematicsSeeder; grade-isolation test |
| Save and retrieve teacher assessments | Done | TeacherAssessmentController; assessment feature tests |
| Assessment entry form and saved results | Done | Assessment page; saved results screenshot; save and reload verified |
| Initial automated testing evidence | Done after evidence commit is pushed | docs/testing/sprint-2-test-record.md and captured test output |
| Manual Addition workflow rehearsal | In Progress | Complete MAN-01 through MAN-04 and attach the two screenshots |
| Update Chapter 5 implementation and initial testing | Backlog | Actual versions, workflow, integration, measured results, and limitations documented |
| Reconcile Chapter 4 diagrams with implementation | Backlog | Grade-specific curriculum, token flow, uniqueness rule, and access scope reflected accurately |
| Write project-specific setup instructions | Backlog | A reader can configure, migrate, seed, start both services, and run tests |
| Prepare review demo and confirm supervisor priorities | Backlog | Rehearsed demo, objectives mapping, challenges, and agreed next priorities |

Keep Chart.js visualisations, parent SMS access, local-model integration, and polish in their later sprint backlog unless the supervisor changes the priorities. Add a separate open task for teacher-to-class assignment; current school-level access does not complete that requirement.

## Example description for the assessment workflow card

```text
Implemented: teacher selects a school class, grade-specific learning area,
strand, sub-strand and term; records EE/ME/AE/BE and remarks; saves to
PostgreSQL through Laravel; and retrieves the persisted records in Nuxt.

Validation: school/class/grade checks, allowed competency levels,
duplicate-save handling, and teacher record isolation are covered by tests.

Evidence: docs/testing/sprint-2-test-record.md
Screenshot: docs/testing/evidence/assessment-entry-preview.png

Limitation: access is school-scoped; specific class assignments remain open.
```

## References

- [Adding items to Projects](https://docs.github.com/en/issues/planning-and-tracking-with-projects/managing-items-in-your-project/adding-items-to-your-project)
- [Customizing the table layout and status](https://docs.github.com/en/issues/planning-and-tracking-with-projects/customizing-views-in-your-project/customizing-the-table-layout)
- [Adding a date field](https://docs.github.com/en/issues/planning-and-tracking-with-projects/understanding-fields/about-date-fields)
