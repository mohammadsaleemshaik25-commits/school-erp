# ERP Role Permission Matrix

Roles:
- Administrator
- Principal
- Correspondent
- Clerk

## Responsibilities Mapping

| Module / Capability | Administrator | Principal | Correspondent | Clerk |
|---|---|---|---|---|
| User Management | Full | No | No | No |
| Backup Management | Full | No | No | No |
| Security Monitoring | Full | Read | Read | No |
| Audit Log Monitoring | Full | Read | Read | No |
| Academic Management | Full | Full | Full | Support only |
| Fee Approval | Full | Full | Full | No |
| Concession Approval | Full | Full | Full | No |
| Student Promotion | Full | Full | Full | No |
| Website Content Approval | Optional | Optional | Full | No |
| Student Admission Entry | Full | Full | Full | Full |
| Fee Collection | Full | Full | Full | Full |
| Receipt Generation | Full | Full | Full | Full |
| Student Record Updates | Full | Full | Full | Full |

## Validation Notes

- Run this matrix during UAT and verify each role with real logins.
- Denied operations must return a safe access-denied response.
- Keep this file in sync with request authorization and middleware rules.
