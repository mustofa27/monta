# Sprint Board - SIPTAKHIR POLTERA

## Planning Assumptions
- Sprint length: 2 weeks
- Team capacity baseline: 24-30 story points per sprint
- Stack: Laravel + Blade + MySQL/SQLite + Vite
- Existing baseline already done: SSO authorization flow, callback, token refresh endpoint, backchannel logout handling, landing page redesign

## Definition of Ready (DoR)
- User story has actor, business outcome, and scope boundary
- Acceptance criteria are testable
- API contracts or UI wireframe references exist
- Dependencies are identified

## Definition of Done (DoD)
- Code merged and reviewed
- Feature tests added or updated
- Authorization policy validated
- Logs/audit events added for critical actions
- UI responsive on mobile and desktop
- Documentation updated

## Sprint 1 - Identity, Roles, and TA Core Data
Goal: Stabilize access control and establish TA domain schema.
Target: 28 SP

### S1-01 Role and Permission Matrix
- Type: Story
- Priority: P0
- Estimate: 3 SP
- Description: Create role model and permissions for mahasiswa, dosen pembimbing, koordinator TA, admin prodi.
- Acceptance Criteria:
  - Roles are seedable and assignable to users
  - Route-level authorization is enforced
  - Unauthorized access returns 403
- Dependencies: None

### S1-02 Sync Role Mapping from SSO Attributes
- Type: Story
- Priority: P0
- Estimate: 5 SP
- Description: Map SSO profile fields (user_type, employee_type) to local role defaults.
- Acceptance Criteria:
  - New user is assigned role based on SSO profile
  - Existing user role updates follow configurable mapping rules
  - Role mapping can be configured in app config
- Dependencies: S1-01

### S1-03 TA Domain Schema Migration
- Type: Story
- Priority: P0
- Estimate: 8 SP
- Description: Implement entities: ta_projects, ta_milestones, ta_supervisions, ta_documents, ta_reviews, ta_schedules.
- Acceptance Criteria:
  - Migrations and foreign keys are valid
  - Status enums support full TA lifecycle
  - Indexes are present for dashboard queries
- Dependencies: None

### S1-04 Seed Master Data for Lifecycle
- Type: Task
- Priority: P1
- Estimate: 3 SP
- Description: Seed milestone templates and status catalogs per semester.
- Acceptance Criteria:
  - Seeder creates stage templates
  - Seeder is idempotent
- Dependencies: S1-03

### S1-05 Audit Trail Foundation
- Type: Story
- Priority: P0
- Estimate: 5 SP
- Description: Add audit table and logging helper for status transitions and approval actions.
- Acceptance Criteria:
  - Any TA status change writes audit record
  - Audit includes actor, action, before, after, timestamp
- Dependencies: S1-03

### S1-06 Baseline Feature Tests
- Type: Task
- Priority: P0
- Estimate: 4 SP
- Description: Add tests for role access and TA schema integrity.
- Acceptance Criteria:
  - Tests cover role restrictions for key routes
  - Tests verify required relations and constraints
- Dependencies: S1-01, S1-03

## Sprint 2 - Student and Supervisor Workflow
Goal: Deliver end-to-end TA progress workflow for students and supervisors.
Target: 30 SP

### S2-01 Topic Submission Module
- Type: Story
- Priority: P0
- Estimate: 8 SP
- Description: Student can create, edit, and submit TA topic proposal with title, abstract, field, and attachments.
- Acceptance Criteria:
  - Draft and submitted states are supported
  - Validation errors are shown clearly
  - Attachment upload is secured and validated
- Dependencies: S1-03

### S2-02 Topic Review and Decision Flow
- Type: Story
- Priority: P0
- Estimate: 8 SP
- Description: Supervisor/coordinator can approve, reject, or request revision with comments.
- Acceptance Criteria:
  - Decision actions are role-protected
  - Student receives status update and notes
  - Audit record created per decision
- Dependencies: S2-01, S1-05

### S2-03 Supervision Log Module
- Type: Story
- Priority: P0
- Estimate: 5 SP
- Description: Student submits supervision logs; supervisor verifies each log.
- Acceptance Criteria:
  - Log includes date, summary, evidence file
  - Supervisor marks log as accepted or revision needed
- Dependencies: S1-03

### S2-04 Milestone Progress Engine
- Type: Story
- Priority: P0
- Estimate: 5 SP
- Description: Calculate progress percentage by completed milestones.
- Acceptance Criteria:
  - Progress shown on student and supervisor screens
  - Formula is deterministic and test-covered
- Dependencies: S1-03

### S2-05 Student Dashboard v1
- Type: Story
- Priority: P1
- Estimate: 4 SP
- Description: Dashboard showing active stage, pending tasks, and nearest deadlines.
- Acceptance Criteria:
  - Data shown matches backend status
  - Mobile responsive layout
- Dependencies: S2-01, S2-03, S2-04

## Sprint 3 - Scheduling, Program Dashboard, Notifications
Goal: Enable coordinator control with proactive monitoring.
Target: 29 SP

### S3-01 Seminar Proposal Scheduling
- Type: Story
- Priority: P0
- Estimate: 6 SP
- Description: Coordinator schedules seminar proposal with room, reviewers, and date.
- Acceptance Criteria:
  - Only eligible students can be scheduled
  - Schedule conflicts are blocked
- Dependencies: S2-02, S2-04

### S3-02 Final Defense Scheduling
- Type: Story
- Priority: P0
- Estimate: 6 SP
- Description: Coordinator schedules final defense for students with completed milestones.
- Acceptance Criteria:
  - Eligibility checks are enforced
  - Schedule and assigned panel are stored
- Dependencies: S3-01

### S3-03 Program Monitoring Dashboard
- Type: Story
- Priority: P0
- Estimate: 7 SP
- Description: Cohort-level KPI page with filters by prodi, batch, supervisor, and status.
- Acceptance Criteria:
  - KPI cards match query results
  - Filter combinations perform under acceptable response time
- Dependencies: S2-04

### S3-04 Notification Rules Engine
- Type: Story
- Priority: P0
- Estimate: 7 SP
- Description: Implement reminders and overdue escalation rules.
- Acceptance Criteria:
  - Reminder N days before due date
  - Overdue warning and escalation to coordinator
  - Notification history logged
- Dependencies: S2-03, S2-04

### S3-05 Queue + Scheduler Integration
- Type: Task
- Priority: P1
- Estimate: 3 SP
- Description: Configure scheduled jobs for notifications and escalation checks.
- Acceptance Criteria:
  - Scheduler task registered
  - Queue worker processes notification jobs reliably
- Dependencies: S3-04

## Sprint 4 - Hardening, Reporting, Release
Goal: Production readiness and governance reporting.
Target: 27 SP

### S4-01 Reporting Export (PDF/Excel)
- Type: Story
- Priority: P1
- Estimate: 5 SP
- Description: Export TA progress and completion report by cohort/program.
- Acceptance Criteria:
  - Exports support selected filters
  - File format is consistent and readable
- Dependencies: S3-03

### S4-02 Document Access and Security Hardening
- Type: Story
- Priority: P0
- Estimate: 5 SP
- Description: Restrict document access by role and ownership; signed links if needed.
- Acceptance Criteria:
  - Unauthorized users cannot fetch files
  - Security tests cover common bypass attempts
- Dependencies: S2-01

### S4-03 Performance Optimization
- Type: Task
- Priority: P1
- Estimate: 5 SP
- Description: Optimize heavy dashboard queries and add caching where safe.
- Acceptance Criteria:
  - Dashboard endpoints meet target response budget
  - No data staleness beyond accepted window
- Dependencies: S3-03

### S4-04 UAT and Bug Bash
- Type: Task
- Priority: P0
- Estimate: 5 SP
- Description: Run scenario testing with mahasiswa, dosen, koordinator, admin.
- Acceptance Criteria:
  - UAT issues triaged and resolved or accepted
  - UAT sign-off captured
- Dependencies: Sprint 1-3 completion

### S4-05 Deployment and Runbook
- Type: Task
- Priority: P0
- Estimate: 3 SP
- Description: Prepare production runbook (migrate, build, queue, scheduler, rollback).
- Acceptance Criteria:
  - Documented release steps verified on staging
  - Rollback procedure tested
- Dependencies: S4-03

### S4-06 Observability and Operational Alerts
- Type: Task
- Priority: P1
- Estimate: 4 SP
- Description: Add operational alerts for failed jobs, SSO failures, and error spikes.
- Acceptance Criteria:
  - Alerting channel configured
  - Key failure scenarios produce alerts
- Dependencies: S3-05

## Cross-Sprint Technical Standards
- Keep CSS in external stylesheets (no inline CSS in Blade views)
- Add feature tests for every P0 story
- Use policies/gates for all role-sensitive actions
- Log critical events: auth failures, approvals, status transitions, notification dispatch, and signature verification failures

## Risk Register
- SSO role attributes may not cover all local role decisions: mitigation -> manual override admin screen in Sprint 2
- Schedule conflict complexity can delay planning module: mitigation -> enforce basic conflict checks first, advanced constraints later
- Notification overload risk: mitigation -> throttle and digest options

## Suggested Milestones
- Milestone A (end Sprint 1): secure access + TA schema complete
- Milestone B (end Sprint 2): student-supervisor core workflow live
- Milestone C (end Sprint 3): coordinator dashboard + notifications live
- Milestone D (end Sprint 4): production release ready
