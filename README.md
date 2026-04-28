# Creative Agency Hub

> A modern work management platform for creative agencies. Built with PHP & MySQL. It streamlines task collaboration, content approval workflows, and client communication through Kanban-style tracking and a dedicated client portal.

---

## Overview
**Creative Agency Hub** is a specialized management system for creative agencies. The project provides comprehensive digital solutions ranging from human resource management (HRM) and workflow tracking (Kanban/Gantt) to an interactive client portal (Client Portal).

## Tech Stack

### Frontend:
Core:
  - HTML5, CSS3, Vanilla JavaScript

UI Framework:
  - Bootstrap 5 (Customized Base Template)

Features:
  - Dynamic Kanban Board (Drag & Drop UI)
  - Gantt Charts
  - Advanced Data Filtering (project, assignee, status, deadline)

### Backend:
Core: 
  - PHP (Custom MVC Architecture), OOP

Database:
  - MySQL (Relational schema with strict Foreign Keys, utf8mb4)

Architecture & Security:
  - Custom Routing & Role-Based Middleware (RBAC)
  - Session Management & JWT Authentication
  - Protection against SQL Injection, XSS, and CSRF

---

## RBAC - Role Based Access Control
The system operates with four main role groups:
1. **Admin:** Manages accounts, department categories, job titles, and establishes the organizational structure.
2. **Manager:** Creates projects, assigns tasks, approves requests (leave, business trips), and monitors the Dashboard.
3. **Employee:** Updates profiles, records attendance, receives tasks, reports progress, and submits requests.
4. **Client:** Accesses a separate Client Portal to track project progress.

---

## Core Features

### 1. Human Resources Management (HRM)
- **Electronic Records:** Manages employee information, employment contracts, and insurance (CRUD).
- **Timekeeping & Payroll:** Daily web-check-in; system automatically calculates payroll based on working days and KPIs.
- **Leave Management:** Submits requests, approves them, and automatically deducts leave from the payroll fund.

### 2. Work & Project Management
- **Dashboard:** Visually track tasks through Kanban and Gantt Chart interfaces.
- **Task Management:** CRUD tasks, assign assigners and deadlines.
- **Interaction Flow:** Change status (To do -> Doing -> Review -> Done), comment threads, and attach documents to each task.
- **Approval Flow:** Submit -> Review -> Approve/Reject.

---

## Getting Started

### 1. Clone the repository
```bash
git clone https://github.com/Minhhieu3012/creative-agency-hub.git
cd creative-agency-hub
```

### 2. Environment Setup
```bash
cp .env.example .env
# Update DB credentials in .env (DB_HOST, DB_USER, DB_PASS, DB_NAME)
```

### 3. Database Setup
Create a new MySQL database and import the core schema:
- Import database/schema.sql via phpMyAdmin, MySQL Workbench, or CLI.
- Run any subsequent migration files if available.

### 4. Run the Application
- Start your local web server (XAMPP / Laragon / MAMP).
- Ensure the document root points to the public/ directory (or the folder containing index.php).
- Access the app via: http://localhost/creative-agency-hub/

---

## Future Improvements
- [ ] Implement Real-time notifications for Task assignments and Approval statuses via WebSocket.

- [ ] Develop RESTful APIs for future Mobile App integration.

- [ ] Add system Audit Logs to track sensitive data changes.

- [ ] Implement Soft Deletes for data recovery and compliance.

---

*This project was developed for academic reporting purposes. Please refer to the attached source code and documentation.*
