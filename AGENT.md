# AGENT.md - AI Development Rules for TaskFlow Application

## 📋 PROJECT OVERVIEW

**Application**: TaskFlow - To-Do List Application  
**Tech Stack**: PHP 8+, MySQL 8+, HTML5, CSS3, Vanilla JS  
**Architecture**: Single-file monolithic (index.php)  
**Database**: MySQL (todo_app database)  
**Tables**: `users`, `tasks`  
**User Roles**: User (authenticated), Guest (unauthenticated)

---

## 🤖 AGENT.py ANALYSIS - CORE PRINCIPLES

Based on AGENT.py analysis, all AI models MUST follow these principles:

### ✅ PRODUCTION-SAFE PRINCIPLES (from AGENT.py)
1. **Input Validation** - Block PII/credentials (emails, SSN, phones, credit cards, passwords, API keys, tokens, secrets)
2. **Tool Registry** - Whitelist only approved tools/actions
3. **Tool Execution** - Retry mechanism (max 2 attempts), error handling
4. **Output Sanitization** - Remove PII from results
5. **Audit Logging** - Full audit trail for compliance
6. **NO Hallucination Detection** - Unreliable, removed
6. **NO Confidence Scoring** - Fake uncertainty, removed
6. **NO Uncertainty Tracking** - Random numbers, removed
6. **NO Complex Planning** - Incomplete, removed
7. **Honest Limitations** - Admit what cannot be done

### 🎯 KEY PRINCIPLE
> **"Simple and honest beats sophisticated and broken"**
> - Simple = Fewer bugs
> - Honest = No fake metrics
> - Reliable = Deterministic behavior
> - Boring = Stable in production

---

## 🔄 MANDATORY DEVELOPMENT WORKFLOW

### ⚠️ ALL AI MODELS MUST FOLLOW THIS WORKFLOW FOR EVERY CODE CHANGE

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                    MANDATORY DEVELOPMENT WORKFLOW                           │
└─────────────────────────────────────────────────────────────────────────────┘

┌──────────────┐    ┌──────────────┐    ┌──────────────┐    ┌──────────────┐
│  1. LOCALHOST │───▶│ 2. LOCALHOST │───▶│  3. GITHUB   │───▶│ 4. GITHUB    │
│  DEVELOPMENT  │    │   TESTING    │    │  COMMIT &    │    │  CI/CD TEST  │
│               │    │  (MANDATORY) │    │  PUSH        │    │  (AUTOMATIC) │
└──────────────┘    └──────────────┘    └──────────────┘    └──────────────┘
                           │                    │                    │
                           ▼                    ▼                    ▼
                    ┌──────────────┐    ┌──────────────┐    ┌──────────────┐
                    │ • Unit tests │    │ • Commit msg │    │ • Lint       │
                    │ • Integration│    │   format:    │    │ • Typecheck  │
                    │ • All roles  │    │   "feat:     │    │ • Unit tests │
                    │ • Edge cases │    │    desc      │    │ • Integration│
                    │ • Error cases│    │   | test:    │    │ • E2E tests  │
                    │              │    │    results"  │    │ • All roles  │
                    └──────────────┘    └──────────────┘    └──────────────┘
                           │                    │                    │
                           ▼                    ▼                    ▼
                    ┌──────────────────────────────────────────────────────┐
                    │           5. FAILURE ANALYSIS & RE-TEST             │
                    │  ┌────────────────────────────────────────────────┐  │
                    │  │ • Analyze failed test logs                     │  │
                    │  │ • Identify root cause (backend/frontend/db/UI) │  │
                    │  │ • Fix locally                                  │  │
                    │  │ • Re-run localhost tests                       │  │
                    │  │ • Re-commit & push                             │  │
                    │  │ • Repeat until 100% GitHub CI/CD passes       │  │
                    │  └────────────────────────────────────────────────┘  │
                    └──────────────────────────────────────────────────────┘
                                    │
                                    ▼
                    ┌──────────────────────────────────────────────────────┐
                    │              6. PRODUCTION DEPLOYMENT                │
                    │  • Only after 100% GitHub CI/CD pass                │
                    │  • Verify production matches localhost              │
                    │  • Monitor 24h post-deploy                          │
                    └──────────────────────────────────────────────────────┘
```

---

## 📋 DETAILED WORKFLOW STEPS

### STEP 1: LOCALHOST DEVELOPMENT
```bash
# 1. Start local environment
# - Start XAMPP (Apache + MySQL)
# - Ensure database 'todo_app' exists
# - Access via http://localhost/web pk mujib/

# 2. Make code changes in index.php
# - Follow PHP best practices
# - Use prepared statements (already implemented)
# - Maintain single-file architecture
# - Keep CSS/JS in same file
```

### STEP 2: LOCALHOST TESTING (MANDATORY - NO SKIPPING)
```bash
# Run ALL tests before committing:

# 1. Database Tests
mysql -u root -e "USE todo_app; SHOW TABLES; DESCRIBE users; DESCRIBE tasks;"

# 2. Backend Tests (PHP)
php -l index.php  # Syntax check

# 3. Frontend Tests (Manual - Required for all roles)
# Test as GUEST (not logged in):
#   - Access http://localhost/web pk mujib/
#   - Verify login form shows
#   - Verify register form shows
#   - Verify NO task list visible
#   - Test login with valid credentials
#   - Test login with invalid credentials
#   - Test register with valid data
#   - Test register with duplicate email/username
#   - Test register with invalid email
#   - Test register with short password
#   - Test register with mismatched passwords

# Test as USER (logged in):
#   - Verify task list shows
#   - Verify stats cards show (total, completed, pending)
#   - Add task with each priority (high, medium, low)
#   - Add task with empty input (should not add)
#   - Toggle task status (pending ↔ completed)
#   - Delete task (with confirmation)
#   - Filter by priority (all, high, medium, low)
#   - Verify priority sorting (high → medium → low)
#   - Verify logout works
#   - Verify session persists on refresh

# 4. Database Integrity Tests
#   - Verify user_id foreign key constraint
#   - Verify tasks only show for logged-in user
#   - Verify password hashing (bcrypt)
#   - Verify SQL injection prevention (prepared statements)

# 5. UI/UX Tests
#   - Responsive design (mobile, tablet, desktop)
#   - Dark theme consistency
#   - Animations smooth
#   - Loading states
#   - Error messages clear
#   - Success messages clear
#   - Empty states handled
```

### STEP 3: GITHUB COMMIT & PUSH
```bash
# Commit message format (MANDATORY):
git commit -m "feat: <description> | test: <test-results>"

# Examples:
git commit -m "feat: add task priority filter | test: all 15 manual tests pass, php -l ok, db integrity ok"
git commit -m "fix: login validation error message | test: 5 auth tests pass, php -l ok"
git commit -m "refactor: extract CSS to variables | test: visual regression pass, php -l ok"

# Push to GitHub
git push origin main
```

### STEP 4: GITHUB CI/CD TESTING (AUTOMATIC)
```yaml
# .github/workflows/ci.yml (MUST BE CREATED)
# Runs automatically on every push
# Required checks:
# - php -l (syntax check)
# - composer validate (if composer used)
# - Database schema validation
# - Manual test checklist verification
```

### STEP 5: FAILURE ANALYSIS & RE-TEST (IF TESTS FAIL)
```
┌─────────────────────────────────────────────────────────────────┐
│                    FAILURE ANALYSIS PROCESS                     │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  1. READ GitHub Actions logs                                    │
│     └── Identify: backend / frontend / database / logic / UI   │
│                                                                 │
│  2. ROOT CAUSE ANALYSIS                                         │
│     ├── Backend: PHP errors, SQL errors, session issues        │
│     ├── Frontend: CSS/JS errors, responsive issues             │
│     ├── Database: Schema mismatch, constraint violations       │
│     ├── Logic: Auth bypass, data leakage, permission issues    │
│     └── UI/UX: Broken layout, missing states, accessibility    │
│                                                                 │
│  3. LOCAL FIX                                                    │
│     └── Fix in index.php on localhost                           │
│                                                                 │
│  4. RE-RUN LOCALHOST TESTS (FULL SUITE)                         │
│     └── Must pass 100%                                          │
│                                                                 │
│  5. RE-COMMIT & PUSH                                             │
│     └── git commit -m "fix: <root-cause> | test: all pass"     │
│                                                                 │
│  6. REPEAT until GitHub CI/CD 100% PASS                         │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🔍 APPLICATION ALIGNMENT ANALYSIS

### 📊 BACKEND-FRONTEND-DATABASE-LOGIC-UI/UX ALIGNMENT CHECKLIST

| Component | Status | Details |
|-----------|--------|---------|
| **Backend (PHP)** | ✅ Aligned | Prepared statements, session auth, CRUD complete |
| **Frontend (HTML/CSS/JS)** | ✅ Aligned | Single-file, responsive, dark theme, animations |
| **Database (MySQL)** | ✅ Aligned | Users + Tasks tables, FK constraints, indexes |
| **System Logic** | ✅ Aligned | Auth flow, task ownership, priority sorting |
| **UI/UX** | ✅ Aligned | Consistent design, all states handled |
| **User Roles** | ✅ Aligned | Guest (auth only), User (full CRUD) |

### 🎭 USER ROLES & FEATURES MATRIX

| Feature | Guest | User |
|---------|-------|------|
| View Login Page | ✅ | ✅ |
| View Register Page | ✅ | ✅ |
| Login | ✅ | ✅ |
| Register | ✅ | ✅ |
| View Tasks | ❌ | ✅ |
| Add Task | ❌ | ✅ |
| Edit Task (toggle) | ❌ | ✅ |
| Delete Task | ❌ | ✅ |
| Filter Tasks | ❌ | ✅ |
| View Stats | ❌ | ✅ |
| Logout | ❌ | ✅ |
| Session Persistence | N/A | ✅ |

### 🔗 BACKEND-FRONTEND CONTRACT

| Endpoint | Method | Auth | Params | Response |
|----------|--------|------|--------|----------|
| `?` | GET | Optional | `action`, `filter` | HTML page |
| `?` | POST | No | `login`, `username`, `password` | Redirect/Error |
| `?` | POST | No | `register`, `username`, `email`, `password`, `confirm_password` | Redirect/Error |
| `?logout=1` | GET | Yes | - | Redirect |
| `?` | POST | Yes | `add_task`, `task`, `priority` | Redirect |
| `?delete=X` | GET | Yes | `delete` (task ID) | Redirect |
| `?toggle=X` | GET | Yes | `toggle` (task ID) | Redirect |

### 🗄️ DATABASE SCHEMA ALIGNMENT

```sql
-- Users table (aligned with auth system)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,  -- Login field
    email VARCHAR(100) UNIQUE NOT NULL,    -- Login field + register
    password VARCHAR(255) NOT NULL,        -- bcrypt hash
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tasks table (aligned with CRUD features)
CREATE TABLE tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,                  -- FK to users, ownership
    task VARCHAR(255) NOT NULL,            -- Task content
    status ENUM('pending', 'completed') DEFAULT 'pending',  -- Toggle feature
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium', -- Filter/sort
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### ✅ ALIGNMENT VERIFICATION CHECKLIST

Run this checklist after EVERY change:

- [ ] **Backend**: All SQL uses prepared statements
- [ ] **Backend**: Session auth checked before task operations
- [ ] **Backend**: User ownership verified (user_id match)
- [ ] **Backend**: Passwords hashed with `password_hash()`
- [ ] **Frontend**: Forms use POST for mutations
- [ ] **Frontend**: CSRF protection (consider adding token)
- [ ] **Frontend**: XSS prevention (htmlspecialchars on output)
- [ ] **Database**: Foreign keys enforce referential integrity
- [ ] **Database**: Unique constraints on username/email
- [ ] **Logic**: Guest cannot access task features
- [ ] **Logic**: User only sees own tasks
- [ ] **Logic**: Priority sorting works (high > medium > low)
- [ ] **UI/UX**: Dark theme consistent across all pages
- [ ] **UI/UX**: Empty states handled (no tasks)
- [ ] **UI/UX**: Error/success messages clear
- [ ] **UI/UX**: Responsive on mobile/tablet/desktop
- [ ] **UI/UX**: Animations smooth, not janky
- [ ] **UI/UX**: Loading states for actions
- [ ] **UI/UX**: Confirmation for destructive actions (delete)

---

## 🚫 FORBIDDEN PRACTICES (FROM AGENT.py PRINCIPLES)

| Forbidden | Reason | Alternative |
|-----------|--------|-------------|
| ❌ Raw SQL concatenation | SQL Injection | Prepared statements (already used) |
| ❌ Plain text passwords | Security breach | `password_hash()` / `password_verify()` |
| ❌ Skip localhost testing | Production bugs | MANDATORY localhost test suite |
| ❌ Push without commit msg format | Untraceable changes | `feat: desc \| test: results` |
| ❌ Ignore failed CI/CD | Broken production | Fix locally, re-test, re-push |
| ❌ Add fake metrics (confidence) | Misleading | Honest "manual review needed" |
| ❌ Complex multi-file architecture | Over-engineering | Keep single-file simplicity |
| ❌ Skip user role testing | Permission bugs | Test ALL roles every time |
| ❌ Ignore empty/error states | Bad UX | Handle all states explicitly |

---

## 📝 AI MODEL COMPLIANCE CHECKLIST

Before EVERY response/code change, verify:

- [ ] Follows AGENT.py principles (simple, honest, validated, logged)
- [ ] Follows mandatory workflow (localhost → test → commit → CI → fix → deploy)
- [ ] Maintains backend-frontend-database-logic-UI alignment
- [ ] Tests all user roles (Guest, User)
- [ ] Handles all states (empty, error, loading, success)
- [ ] Uses prepared statements
- [ ] Hashes passwords
- [ ] Verifies ownership
- [ ] Keeps single-file architecture
- [ ] Maintains dark theme consistency
- [ ] Provides audit trail (commit messages with test results)

---

## 🎯 QUICK REFERENCE COMMANDS

```bash
# Local development
cd "C:\xampp\htdocs\web pk mujib"
# Edit index.php
# Test in browser: http://localhost/web pk mujib/

# Syntax check
php -l index.php

# Database check
mysql -u root -e "USE todo_app; SELECT * FROM users; SELECT * FROM tasks;"

# Git workflow
git add index.php AGENT.md
git commit -m "feat: <description> | test: <results>"
git push origin main

# View GitHub Actions
# https://github.com/<username>/<repo>/actions
```

---

## 📌 VERSION CONTROL

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | 2026-06-30 | Initial AGENT.md based on AGENT.py analysis + TaskFlow app analysis |

---

**⚠️ REMINDER**: This document is MANDATORY for all AI models working on this project. Any deviation from the workflow requires explicit human approval with documented justification.