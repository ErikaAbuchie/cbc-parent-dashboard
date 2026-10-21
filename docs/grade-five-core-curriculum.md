# Grade 5 core curriculum expansion — 19 October 2026

## Scope and sources

`GradeFiveCoreCurriculumSeeder` adds the remaining core Grade 5 learning areas and three religious education alternatives. It also calls the existing Mathematics seeder. Source: [KICD Grade Five Designs](https://kicd.ac.ke/cbc-materials/curriculum-designs/grade-five-designs/), accessed 19 October 2026. The data file records the individual public design URL, printed summary pages and scope notes for each added subject.

Only curriculum names are imported. This is a selectable reference catalogue, not a complete import of lesson outcomes, themes, assessment rubrics or lesson allocations. No learner assessments, access codes, phone numbers or SMS jobs are generated. No schema migration or frontend change is required: the existing class-grade curriculum API supplies the selectors.

| Learning area | Strands | Selectable sub-strands |
| --- | ---: | ---: |
| Mathematics (existing seeder) | 4 | 19 |
| Agriculture | 4 | 13 |
| Creative Arts | 3 | 14 |
| Christian Religious Education | 5 | 30 |
| English | 4 | 12 |
| Hindu Religious Education | 8 | 11 |
| Islamic Religious Education | 7 | 24 |
| Kiswahili | 4 | 52 |
| Science and Technology | 3 | 8 |
| Social Studies | 5 | 22 |
| **Total in this catalogue** | **47** | **205** |

These counts include alternative options, not requirements for every learner. CRE, IRE and HRE are alternatives; choose the applicable religious education subject. Creative Arts includes both optional alternatives, Swimming and Indigenous Kenyan Games. Do not select both merely because both appear in the catalogue. The application currently lets the teacher define the finalisation scope and does not enforce religious subject enrolment or optional-topic selection.

English uses the summary's twelve skill categories across four strands, rather than creating a separate rating for every theme lesson. Kiswahili retains unit codes on repeated skills, such as `1.2.1 Kusoma kwa Ufahamu` and `5.2.1 Kusoma kwa Ufahamu`, to prevent their records being merged. Minor source spacing, capitalisation, punctuation and obvious summary numbering/typing inconsistencies are normalised. IRE nested headings are combined with their focus into selectable labels. Use the linked design when checking the curriculum interpretation; this is not KICD certification of the application.

## Populate the database

From the backend folder:

```powershell
cd C:\Users\eryka\OneDrive\Documents\cbc-parent-dashboard\backend
php artisan db:seed --class=GradeFiveCoreCurriculumSeeder
```

The seeder uses a transaction and `firstOrCreate` keyed by grade/name and parent/name. It preserves current IDs, assessments, revision history and unrelated grade records. It can be run again without duplicating the same catalogue. It does not reconcile differently named manual curriculum entries or remove obsolete entries. Avoid `migrate:fresh`, which would destroy existing data.

## Use the additional subjects

1. Refresh **Assessment entry** and select the Grade 5 class.
2. Select a new learning area, strand and sub-strand, then choose the same term/year you want in the parent report.
3. Enter competency ratings for active learners and save. Repeat for other desired subjects. Use simulated learner data for demonstrations.
4. Open **Finalise term reports** and select the desired completed sub-strands across subjects. Only choose the applicable religious education alternative and optional topics.
5. Check completeness, then issue and confirm a replacement code for the learner. The report scope must be updated to include the newly assessed subjects; an existing Mathematics-only report does not automatically expand when curriculum is seeded.
6. Use the newest SMS code to open the parent report. Earlier codes for that learner/term are invalidated. The competency overview counts all included results, and each detailed result identifies its learning area. The overview is currently combined across subjects, not a subject-by-subject chart or an overall numerical grade.

The same demonstration teacher currently has class-level access to all learning areas in their assigned class. Subject-specific teacher assignments remain a separate feature.

## Verification

Two new isolated database tests passed with 17 assertions: repeat seeding preserves IDs/assessments and other grades; the assigned teacher API returns all ten learning areas and accepts a Science assessment; repeated Kiswahili units remain distinct. The full backend suite passed on 19 October 2026: **44 tests, 309 assertions**, in 5.18 seconds. This is test execution time, not a user-facing response time measurement. Curriculum table text was checked from the public official previews, with visual checks of the Kiswahili and IRE summary structure. Manual browser verification of the expanded selectors and a parent report with several subjects is the next demonstration check.

## Focused commits

From the project root:

```powershell
git add -- backend/database/data/grade-five-core-curriculum.json backend/database/seeders/GradeFiveCoreCurriculumSeeder.php
git commit -m "Seed Grade 5 core subjects and religious education alternatives"

git add -- backend/tests/Feature/GradeFiveCoreCurriculumSeederTest.php docs/grade-five-core-curriculum.md
git commit -m "Verify curriculum expansion preserves existing assessment data"

git push origin main
git status
```

These commands exclude the earlier untracked chapter drafts, other evidence files and temporary source extracts.
