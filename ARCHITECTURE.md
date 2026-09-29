# SIWES360 — Actual System Architecture & Workflow

## 1. System purpose

SIWES360 is a Student Industrial Training Management & Monitoring System. It manages the complete school-to-industry training lifecycle:

**Student account → placement → approval/activation → daily attendance → daily logbook → weekly report → supervisor review → school monitoring → evaluation → completion.**

The system is intentionally built with a simple, defense-friendly stack:

- HTML5
- CSS3
- Vanilla JavaScript
- PHP 8+
- MySQL
- PDO prepared statements
- PHP sessions
- XAMPP/Apache for local development

There is **no React, TypeScript, Vite, Node.js, localStorage mock database, or client-side demo authentication** in the final architecture.

## 2. Architectural layers

```text
Browser
   │
   ▼
PHP role pages (HTML + CSS + Vanilla JS)
   │
   ├── Authentication / RBAC
   ├── Form validation
   ├── Business rules
   └── User feedback / notifications
   │
   ▼
PHP API/action endpoints
   │
   ├── Prepared SQL
   ├── Ownership checks
   ├── CSRF checks
   └── Audit logging
   │
   ▼
MySQL: siwes360
```

## 3. Core database relationship

`placements` is the central relationship table.

```text
users
 ├── students
 ├── supervisors
 └── coordinators

students ───────────────┐
organizations ──────────┤
supervisors ────────────┼──> placements
it_sessions ────────────┘        │
                                 ├── attendance
                                 ├── daily_activities
                                 ├── weekly_reports ──> report_reviews
                                 └── evaluations

users ──> notifications
users ──> audit_logs
departments ──> students
departments ──> coordinators
organizations ──> supervisors
```

This means attendance, activities, reports and evaluations belong to a specific placement, not simply to a student account. That keeps different SIWES sessions separate.

## 4. Real operational workflow

### Phase A — Setup
1. Administrator creates departments.
2. Administrator creates the SIWES training session.
3. Administrator registers host organizations.
4. Administrator creates student, supervisor and coordinator accounts.
5. Supervisor is linked to an organization.
6. Coordinator is linked to a department.

### Phase B — Placement
1. Administrator assigns a student to an organization.
2. The placement connects:
   - student
   - organization
   - supervisor
   - SIWES session
   - position
   - start/end dates
3. Placement moves through `pending → approved → active → completed` where applicable.
4. Student can view the official placement.

### Phase C — Daily attendance
1. Student opens Attendance.
2. Student clocks in once per day.
3. The system prevents duplicate attendance for the same placement/date.
4. The system records the time and determines the attendance status.
5. Student clocks out later the same day.
6. Supervisors and coordinators can monitor attendance.
7. Admin can view the central attendance log.

### Phase D — Daily logbook
1. Student records what was done each day.
2. Student records skills learned, tools used, challenges and lessons.
3. Entry can be saved as a draft.
4. Student submits the entry.
5. Supervisor can review and comment.
6. Activity status becomes reviewed or rejected.

### Phase E — Weekly report
1. Student compiles the week's activities into one weekly report.
2. Student saves/submits the report.
3. Supervisor receives an in-app notification.
4. Supervisor reviews the report.
5. Supervisor can approve, reject or request revision.
6. The student receives the decision and feedback.
7. Every review is stored in `report_reviews`.

### Phase F — Evaluation
1. Supervisor evaluates the student's technical and professional performance.
2. Coordinator can also evaluate students within the coordinator's department.
3. The system calculates the overall score.
4. Student receives a notification.
5. Admin can view evaluation records.

### Phase G — Monitoring and completion
1. Coordinator monitors students, placements, attendance, reports and evaluations.
2. Admin monitors system-wide records and audit logs.
3. When training ends, the placement can be marked completed.
4. The system retains the historical records for reporting and defense.

## 5. Role boundaries

### Student
Can:
- view own dashboard/profile/placement
- clock in/out for own active placement
- create and submit own daily activities
- create and submit own weekly reports
- view own evaluations
- read own notifications

Cannot:
- access another student's data
- create placements
- create system accounts
- review supervisor reports
- modify system-wide records

### Supervisor
Can:
- see students assigned to the supervisor
- monitor their attendance
- review their activities
- review weekly reports
- submit evaluations for assigned placements
- receive notifications

Cannot:
- manage system users
- access unrelated students' private training records

### Coordinator
Can:
- monitor students in the coordinator's department
- monitor placements
- monitor attendance
- monitor reports
- submit evaluations for students in the coordinator's department
- receive notifications

Cannot:
- administer the whole system

### Administrator
Can:
- manage users
- manage students/supervisors/coordinators
- manage departments
- manage organizations
- manage SIWES sessions
- assign placements
- monitor attendance
- view reports/evaluations
- view audit logs

## 6. Security architecture

- `password_hash()` / `password_verify()` for passwords.
- PHP sessions for authentication.
- Session ID regeneration after login.
- Role-based access control.
- Prepared SQL statements through PDO.
- HTML escaping with `htmlspecialchars`.
- CSRF tokens for state-changing forms.
- Ownership checks before students/supervisors/coordinators can change records.
- Audit logs for important actions.
- No passwords stored in plain text.
- No client-side localStorage used as the database.

## 7. QR attendance

QR attendance was found in the Google AI Studio React prototype. It is **not part of the current database-backed v1 architecture**.

Therefore it is intentionally not allowed to drive the core workflow. It can be added later as Phase 2 after the normal attendance workflow is stable and a proper QR/location design is added to the database.

## 8. Source-of-truth rule

The user's already-created `siwes360` database in phpMyAdmin is the source of truth.

The generated Google AI Studio SQL file had schema differences and demo data. It has therefore been removed from the application and replaced with `database/siwes360_reference.sql`.

**Do not import a generated SQL file over the existing working database.**

## 9. Deployment

For local XAMPP:

1. Put the `siwes360` folder inside `htdocs`.
2. Start Apache and MySQL.
3. Confirm the database name is `siwes360`.
4. Check `config/database.php`.
5. Open `/siwes360/` in the browser.
6. Log in with an account that already exists in the database.

For production deployment, PHP + MySQL hosting is required. Vercel is not the correct target for this PHP/XAMPP architecture without introducing a different backend architecture.

