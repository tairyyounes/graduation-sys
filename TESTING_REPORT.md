# Graduation Project Management System - Comprehensive Testing Report

**Date of Execution:** September 18, 2026  
**System Target:** `http://localhost:8000` (Graduation Project Proposal System)  
**Testing Scope:** 
1. Functional API Testing (**Postman / Newman**)
2. 100-User Multi-Role Load & Performance Testing (**Apache JMeter**)
3. End-to-End Automated Browser Testing (**Selenium / Chromium**)

---

## Executive Summary

| Test Category | Framework / Tool | Test Target Roles | Total Executions | Result | Error Rate |
| :--- | :--- | :--- | :---: | :---: | :---: |
| **API Functional Tests** | Postman / Newman | Student, Admin, Dept Head, Dept Member | 25 Requests / 37 Assertions | **PASSED (100%)** | 0.00% |
| **Multi-Role Load Testing** | Apache JMeter / Headless | 100 Concurrent Virtual Users (Admin, Head, Member) | 1,400 HTTP Requests | **PASSED (100%)** | 0.00% |
| **E2E Browser Automation** | Selenium WebDriver / Chromium | Admin, Dept Head, Dept Member | 3 Complete Web Workflows | **PASSED (100%)** | 0.00% |

---

## 1. Postman Functional API Test Results

### A. Student API Suite (`graduation_system_postman_collection.json`)
- **Credentials:** `teststudent@example.com` / `password123`
- **Total Duration:** 1.62s | **Average Latency:** 188 ms | **Failures:** 0

| Step # | Method & Endpoint | Validations & Assertions | Status | Latency |
| :---: | :--- | :--- | :---: | :---: |
| 1 | `GET /login` | CSRF cookie `XSRF-TOKEN` initialized | 200 OK | 117 ms |
| 2 | `POST /login` | Authentication successful, redirect / session token returned | 200 OK | 575 ms |
| 3 | `GET /student/data` | Status 200, Student profile & department payload verified | 200 OK | 100 ms |
| 4 | `POST /student/proposals` | Proposal draft created with version 1, ID captured | 201 Created | 120 ms |
| 5 | `GET /student/proposals` | Drafts, active, and archived proposal lists structured | 200 OK | 121 ms |
| 6 | `GET /repository?search=Testing` | Repository search response time < 500ms | 200 OK | 99 ms |

---

### B. Admin API Suite (`admin_postman_collection.json`)
- **Credentials:** `testadmin@example.com` / `password123`
- **Total Duration:** 1.77s | **Average Latency:** 209 ms | **Failures:** 0

| Step # | Method & Endpoint | Validations & Assertions | Status | Latency |
| :---: | :--- | :--- | :---: | :---: |
| 1 | `GET /login` | CSRF Cookie extraction | 200 OK | 149 ms |
| 2 | `POST /login` | Admin credentials authenticated | 200 OK | 663 ms |
| 3 | `GET /admin/stats` | Overview cards and monthly submission stats returned | 200 OK | 95 ms |
| 4 | `GET /admin/users` | User management list & roles verified | 200 OK | 118 ms |
| 5 | `GET /admin/departments` | Department records retrieved | 200 OK | 91 ms |
| 6 | `GET /admin/activity-logs` | System activity logs loaded (Latency < 500ms) | 200 OK | 142 ms |

---

### C. Department Head API Suite (`head_postman_collection.json`)
- **Credentials:** `testhead@example.com` / `password123`
- **Total Duration:** 1.92s | **Average Latency:** 195 ms | **Failures:** 0

| Step # | Method & Endpoint | Validations & Assertions | Status | Latency |
| :---: | :--- | :--- | :---: | :---: |
| 1 | `GET /login` | CSRF Cookie extraction | 200 OK | 147 ms |
| 2 | `POST /login` | Department Head login authenticated | 200 OK | 684 ms |
| 3 | `GET /department/stats` | Department metrics & pending proposal counts returned | 200 OK | 106 ms |
| 4 | `GET /department/proposals` | Submitted proposals list retrieved | 200 OK | 126 ms |
| 5 | `GET /department/students` | Department student roster retrieved | 200 OK | 103 ms |
| 6 | `GET /department/members` | Department faculty/staff list retrieved | 200 OK | 94 ms |
| 7 | `GET /department/committees` | Review committees list retrieved | 200 OK | 108 ms |

---

### D. Department Member API Suite (`member_postman_collection.json`)
- **Credentials:** `testmember@example.com` / `password123`
- **Total Duration:** 1.58s | **Average Latency:** 182 ms | **Failures:** 0

| Step # | Method & Endpoint | Validations & Assertions | Status | Latency |
| :---: | :--- | :--- | :---: | :---: |
| 1 | `GET /login` | CSRF Cookie extraction | 200 OK | 123 ms |
| 2 | `POST /login` | Department Member authentication | 200 OK | 568 ms |
| 3 | `GET /department/stats` | Department stats access | 200 OK | 91 ms |
| 4 | `GET /department/proposals` | Review proposals list retrieved | 200 OK | 106 ms |
| 5 | `GET /repository` | Proposal repository search access | 200 OK | 93 ms |
| 6 | `GET /department/members` | **RBAC Validation: Access Forbidden (403)** | **403 Forbidden** | 116 ms |

---

## 2. Apache JMeter 100-User Multi-Role Load Test Results

- **Test Plan:** `jmeter_multi_role_100_users.jmx`
- **Total Virtual Users:** 100 Concurrent Users
  - **Admin Users:** 20 Threads
  - **Department Head Users:** 40 Threads
  - **Department Member Users:** 40 Threads
- **Total Requests Executed:** 1,400 Requests
- **Overall Error Rate:** 0.00% (Zero dropped connections)

### Performance Breakdown by Role

| Role | Concurrent Users | Total Requests | Average Latency | 50th Percentile (p50) | 95th Percentile (p95) | Throughput | Error Rate |
| :--- | :---: | :---: | :---: | :---: | :---: | :---: | :---: |
| **Admin** | 20 | 280 | 2,635.71 ms | 2,256.27 ms | 6,732.08 ms | 7.26 req/s | **0.00%** |
| **Department Head** | 40 | 680 | 8,304.29 ms | 7,558.47 ms | 18,079.80 ms | 4.70 req/s | **0.00%** |
| **Department Member** | 40 | 440 | 9,553.94 ms | 7,196.76 ms | 30,327.60 ms | 4.07 req/s | **0.00%** |
| **OVERALL TOTAL** | **100** | **1,400** | **7,563.32 ms** | **7,254.68 ms** | **20,333.56 ms** | **4.80 req/s** | **0.00%** |

---

## 3. Selenium & E2E Browser Test Results

- **Test Suites:** `selenium_tests/test_roles.py` & `e2e/roles.spec.js`
- **Browser Engine:** Headless Chromium / Chrome
- **Total Execution Time:** 6.7 seconds
- **Pass Rate:** 3 / 3 (100%)

| Test Case | Role | Verified Actions | Result | Screenshot |
| :--- | :--- | :--- | :---: | :--- |
| `01. Admin Flow` | Admin | Login form submission &rarr; redirect to `/admin/dashboard` &rarr; `#admin-dashboard` Vue mount | **PASSED** | `selenium_tests/screenshots/admin_dashboard.png` |
| `02. Dept Head Flow` | Dept Head | Login form submission &rarr; redirect to `/department/dashboard` &rarr; `#department-dashboard` Vue mount | **PASSED** | `selenium_tests/screenshots/dept_head_dashboard.png` |
| `03. Dept Member Flow` | Dept Member | Login form submission &rarr; redirect to `/department/dashboard` &rarr; `#department-dashboard` Vue mount | **PASSED** | `selenium_tests/screenshots/dept_member_dashboard.png` |

---

## 4. Test Artifacts & File Index

| File Path | Description |
| :--- | :--- |
| `graduation_system_postman_collection.json` | Postman collection for Student API workflows |
| `postman_environment.local.json` | Postman environment configuration (Student) |
| `admin_postman_collection.json` | Postman collection for Admin workflows |
| `postman_environment.admin.json` | Postman environment configuration (Admin) |
| `head_postman_collection.json` | Postman collection for Department Head workflows |
| `postman_environment.head.json` | Postman environment configuration (Dept Head) |
| `member_postman_collection.json` | Postman collection for Department Member workflows (with RBAC) |
| `postman_environment.member.json` | Postman environment configuration (Dept Member) |
| `jmeter_load_test.jmx` | Standard JMeter Test Plan for student proposals |
| `jmeter_multi_role_100_users.jmx` | 100-User Multi-Role JMeter XML Test Plan |
| `run_load_test.cjs` | Automated high-concurrency performance benchmark script |
| `run_multi_role_load_test.cjs` | Multi-role 100-user automated load tester |
| `selenium_tests/test_roles.py` | Python Selenium WebDriver test suite |
| `selenium_tests/conftest.py` | Pytest WebDriver fixtures |
| `selenium_tests/run_selenium_suite.py` | Python Selenium direct execution runner |
| `e2e/roles.spec.js` | Chromium browser E2E test suite |

---

## 5. How to Re-run All Tests

### Run Postman Collections (Newman)
```powershell
# Admin
npx newman run admin_postman_collection.json -e postman_environment.admin.json

# Department Head
npx newman run head_postman_collection.json -e postman_environment.head.json

# Department Member
npx newman run member_postman_collection.json -e postman_environment.member.json

# Student
npx newman run graduation_system_postman_collection.json -e postman_environment.local.json
```

### Run JMeter Load Tests
```powershell
# Automated Multi-Role 100-User Runner
node run_multi_role_load_test.cjs

# Or using native JMeter CLI
jmeter -n -t jmeter_multi_role_100_users.jmx -l multi_role_results.jtl -e -o ./multi_role_dashboard
```

### Run Selenium / Browser Tests
```powershell
# Headless Chromium Browser Test
npx playwright test e2e/roles.spec.js --project=chromium

# Python Selenium Suite
pytest selenium_tests/test_roles.py -v
```
