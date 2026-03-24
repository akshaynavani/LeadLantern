# LeadLantern — Implementation Plan

**LLM-Routed CRM Task Triage.** Inbound CRM tasks (emails, call notes, form submissions,
support requests) are classified by an LLM (category, priority, sentiment) and routed to the
right team queue. A Laravel REST API with role-based access control owns the data in MySQL;
a Node.js/TypeScript LangChain service performs classification; a React + TypeScript dashboard
surfaces interaction history and routing results. Everything deploys serverless on AWS Lambda.

## Architecture decisions

| Concern | Decision |
|---|---|
| Core API | Laravel 11 (PHP 8.2), Sanctum bearer tokens, MySQL 8 |
| RBAC | `roles` / `permissions` tables + pivot; `admin`, `manager`, `agent` seeded; enforced via middleware + policies |
| Triage | Separate Node.js 20 / TypeScript service using LangChain (`@langchain/openai`) with Zod structured output; deterministic keyword fallback when no API key / LLM failure |
| Laravel ↔ triage | Laravel calls triage over HTTP (Lambda Function URL) with a shared secret header; result persisted as a `routing_decision` row (auditable, re-routable) |
| Routing | LLM returns category + priority + confidence; Laravel maps category to a `team` via `routing_rules`; low confidence goes to a `triage` review queue |
| Frontend | React 18 + TypeScript + Vite, react-router, plain fetch client |
| Deploy | Serverless Framework: Laravel via Bref (`php-82-fpm` layer), triage as native Node Lambda, MySQL on RDS; frontend as static build (S3) |
| Local dev | docker-compose MySQL; `php artisan serve`, `npm run dev` for triage + web |

## Phase 1 — Laravel foundation: schema, models, RBAC
Deliverables: Laravel 11 skeleton, migrations, Eloquent models, RBAC model + middleware, seeders.
Files: `backend/composer.json`, `backend/artisan`, `backend/bootstrap/*`, `backend/public/index.php`,
`backend/config/*`, `backend/database/migrations/*`, `backend/app/Models/*`,
`backend/app/Http/Middleware/EnsurePermission.php`, `backend/database/seeders/*`, `backend/.env.example`.

## Phase 2 — Laravel REST API + triage integration
Deliverables: auth endpoints (login/logout/me), CRUD for contacts, tasks, interactions, teams,
routing rules, users; `TriageClient` + `TaskRouter` service; re-route endpoint; dashboard stats;
policies; API resources; feature tests.
Files: `backend/routes/api.php`, `backend/app/Http/Controllers/*`, `backend/app/Http/Requests/*`,
`backend/app/Http/Resources/*`, `backend/app/Policies/*`, `backend/app/Services/*`,
`backend/tests/*`, `backend/phpunit.xml`.

## Phase 3 — LangChain triage service (Node.js / TypeScript)
Deliverables: Zod schema for triage output, LangChain prompt + structured output chain, keyword
fallback classifier, Lambda handler + local HTTP server, shared-secret auth, unit tests (vitest).
Files: `triage-service/package.json`, `triage-service/tsconfig.json`, `triage-service/src/*`,
`triage-service/test/*`, `triage-service/.env.example`.

## Phase 4 — React + TypeScript dashboard
Deliverables: Vite app, API client with token auth, login page, dashboard (stats + queue
breakdown), task list with filters, task detail with routing history + re-route, contact
interaction timeline, admin routing-rules page (permission-gated UI).
Files: `frontend/package.json`, `frontend/vite.config.ts`, `frontend/index.html`, `frontend/src/**`.

## Phase 5 — Serverless deployment + local dev tooling
Deliverables: `serverless.yml` for Laravel (Bref) and for triage service, Lambda-friendly
Laravel config (stderr logs, /tmp storage), docker-compose for MySQL, Makefile, `.gitignore`, README
with architecture diagram, run and test instructions.
Files: `backend/serverless.yml`, `triage-service/serverless.yml`, `docker-compose.yml`, `Makefile`,
`.gitignore`, `README.md`.
