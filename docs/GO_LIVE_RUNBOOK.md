# School ERP Go-Live Runbook

This runbook is the final pre-production checklist for the school client.

## 1) Final UAT (End-to-End Workflows)

Run these in a staging environment with realistic sample data.

### Workflow A: Admission to Enrollment
- [ ] Login as `Clerk`
- [ ] Create a student from `Student Management`
- [ ] Upload at least one student document
- [ ] Enroll student into active academic year/class/section
- [ ] Verify student profile, history, and admission register entries

### Workflow B: Fee Collection to Receipt
- [ ] Login as `Clerk`
- [ ] Search student in fee collection desk
- [ ] Collect payment via `CASH`
- [ ] Verify receipt is generated and visible in receipt list
- [ ] Print thermal receipt
- [ ] Collect payment via `UPI` with transaction reference
- [ ] Verify totals and outstanding balance update correctly

### Workflow C: Concession Approval
- [ ] Login as `Clerk`, raise a concession request
- [ ] Login as `Principal` OR `Correspondent` OR `Administrator`
- [ ] Approve request and verify balance recalculation
- [ ] Create another request and reject it
- [ ] Verify status and audit log entries

### Workflow D: Academic Management
- [ ] Login as `Principal` or `Correspondent`
- [ ] Create academic year
- [ ] Run promotions
- [ ] Close academic year and verify next year creation

### Workflow E: Reports and Dashboard
- [ ] Dashboard cards show valid values
- [ ] Open `student-report`, `fee-report`, `pending-fees`, `daily-collection`
- [ ] Cross-check numbers against fee/receipt records

## 2) Role-by-Role Permission Testing

Use this matrix during UAT.

| Action | Administrator | Principal | Correspondent | Clerk |
|---|---|---|---|---|
| User Management (create/edit/deactivate users) | Yes | No | No | No |
| Student Admission Entry | Yes | Yes | Yes | Yes |
| Student Record Updates | Yes | Yes | Yes | Yes |
| Fee Collection | Yes | Yes | Yes | Yes |
| Receipt Generation | Yes | Yes | Yes | Yes |
| Concession Request | Yes | Yes | Yes | Yes |
| Concession Approval | Yes | Yes | Yes | No |
| Academic Year Management | Yes | Yes | Yes | Limited by process |
| Student Promotion | Yes | Yes | Yes | No |
| Audit Log Monitoring | Yes | Leadership view | Leadership view | No |

Notes:
- "Leadership view" means read access for oversight (implementation can be expanded as needed).
- If any blocked/allowed behavior differs from expected, log it as a UAT defect.

## 3) Backup/Restore Drill

Perform this at least once before production go-live.

1. Take backup:
   - Use `scripts/backup_mysql.ps1`
2. Restore into a separate database:
   - Use `scripts/restore_mysql.ps1`
3. Point staging `.env` to restored database.
4. Run smoke checks:
   - [ ] Login works
   - [ ] Student list loads
   - [ ] Fee collection page loads
   - [ ] Reports load

## 4) Deployment Hardening Checklist

- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] Strong `APP_KEY`, DB password, mail password
- [ ] HTTPS/SSL enabled
- [ ] Session and cookie secure flags enabled
- [ ] File permissions for `storage/` and `bootstrap/cache/`
- [ ] `php artisan config:cache`
- [ ] `php artisan route:cache`
- [ ] `php artisan view:cache`
- [ ] Daily database backup scheduler configured
- [ ] Log retention policy configured

## 5) Monitoring Checklist

- [ ] Server uptime monitoring
- [ ] HTTP health check endpoint monitoring (`/up`)
- [ ] Error log alerts (Laravel log + web server logs)
- [ ] Database disk usage alert
- [ ] Backup success/failure alert

## 6) Go/No-Go Decision

Go live only if all are true:
- [ ] All critical UAT flows passed
- [ ] No P0/P1 defects open
- [ ] Backup/restore drill passed
- [ ] Hardening and monitoring complete
- [ ] Client sign-off captured
