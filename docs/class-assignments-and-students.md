# Class assignments and student management

Teachers can read and manage only classes explicitly assigned to their account and belonging to their school. Linking a teacher to a school alone grants no class access. Assignment is at class level; learning-area-specific teaching assignments remain separate future work.

## Apply the database change

In PowerShell, from backend:

```powershell
php artisan migrate
```

The migration adds class_teacher, with a unique teacher/class pair, and students.deleted_at. It does not automatically grant every teacher access to all school classes.

## Assign or revoke a class

Until an admin interface is implemented, the trusted local operator uses the Artisan command. Teachers cannot assign themselves classes through the web API.

List class identifiers:

```powershell
php artisan tinker
```

At the Tinker prompt:

```php
App\Models\SchoolClass::get(['id', 'name', 'school_id']);
exit
```

Back in PowerShell, replace 1 with the intended class identifier:

```powershell
php artisan teacher:assign-class teacher@example.com 1
```

To revoke that one class:

```powershell
php artisan teacher:assign-class teacher@example.com 1 --remove
```

The command requires an existing teacher account and a class in the same school. Repeating an assignment does not create duplicate rows. The demo seeder also assigns Demo Teacher to Grade 5 Demo; rerunning that explicit setup will recreate that demo assignment, but will not restore removed learners.

## Use the student controls

1. Start Laravel on port 8000 and Nuxt on port 3000.
2. Log in, choose Manage students, then select an assigned class.
3. Add a joining learner using their name, globally unique admission number and parent phone in +2547XXXXXXXX or +2541XXXXXXXX format.
4. Choose Edit to correct the learner's details. Class identifiers cannot be changed through this form.
5. Choose Remove when a learner leaves. Confirm the displayed message.
6. A removed learner appears in Removed learners. Choose Restore if the same learner rejoins the same class.
7. Return to assessment entry and load the class roster. Added/restored learners appear; removed learners do not.

Removal is a soft deletion: the learner's record, admission number and assessment history remain stored. Existing parent access codes are deactivated and are not automatically reactivated on restoration. Creating a second learner with an archived admission number is rejected; restore the original record instead.

This is not an annual promotion or inter-class transfer workflow. Those need explicit enrolment history so old assessments remain linked to the correct class context. There is no permanent-delete endpoint.

## API routes

All require Sanctum authentication and an explicit same-school class assignment:

| Method | Path | Behaviour |
| --- | --- | --- |
| GET | /api/teacher/classes | List assigned classes |
| GET | /api/teacher/classes/{classId}/students | Active and removed learners, including contacts for authorised roster management |
| POST | /api/teacher/classes/{classId}/students | Add a learner |
| PATCH | /api/teacher/classes/{classId}/students/{studentId} | Edit an active learner |
| DELETE | /api/teacher/classes/{classId}/students/{studentId} | Remove from active roster, retain history and revoke parent access |
| POST | /api/teacher/classes/{classId}/students/{studentId}/restore | Restore to the same class |

Assessment routes use the same assignment restriction. Assessment entry responses continue to omit parent phone numbers. Removed learners cannot receive new assessment saves; stale forms are rejected and must reload.

## Testing and documentation

Verification on 15 October 2026: the backend suite passed 26 tests with 156 assertions. The final frontend production build passed. Browser verification confirmed adding, editing, removing and restoring a simulated learner. Removal and restoration use an in-page confirmation panel. The test learner DEMO-JOIN-001 was left in Removed learners; the original three demo learners remain active. The verification screenshot is docs/testing/evidence/student-roster-management.png.

Run `php artisan test --no-ansi` in backend and `npm run build` in frontend. TeacherStudentTest covers student CRUD, reversible removal, history preservation, inactive access codes, duplicate admissions, assignment revocation, same-school unassigned classes, cross-school/class identifiers, and role restrictions.

Earlier Sprint II test records and screenshots describe their dated baseline. They should not be overwritten as if these assignment controls existed at that earlier point. Add this implementation as subsequent progress in Chapter 5 and revise the current-design account in Chapter 4:

- User and SchoolClass now have a many-to-many relationship through class_teacher.
- Student now supports a removed state through deleted_at.
- Teachers may manage enrolment details within assigned classes; this extends the earlier admin-only student-entry scope and should be validated with the supervisor.
- Class-specific access is now enforced; assessment revision history and subject-specific assignments remain outstanding.

Keep GitHub Projects cards for Class-level teacher access and Student roster CRUD in progress until the implementation is tested, demonstrated and pushed. Mark assessment revision history separately; retaining records after student removal does not create a rating revision audit trail.
