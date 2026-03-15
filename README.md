# Template Service (Port 8004)

Stateless Laravel 12 JSON API responsible for **notification template management** and **template rendering**. It stores versioned, multi-channel templates and provides a rendering endpoint that other services use to compile final notification content with variable substitution.

## Responsibilities

- Template CRUD with automatic version bumping on updates.
- Multi-channel support: `email`, `whatsapp`, `push`.
- Variable schema definition per template (`required`, `optional`, `rules`).
- Template rendering: accepts variables, validates against schema, returns compiled output.
- Soft deletes for audit trail.
- Super-admin authorization for all management operations; rendering available to any authenticated admin.

## Database

**Database:** `np_template_service`

| Table | Purpose |
|-------|---------|
| `templates` | Template definitions with key, name, channel, subject, body, variables_schema, version, is_active |
| `jobs` | Laravel queue jobs (standard) |

## API Endpoints

All routes are prefixed with `/api/v1` and require JWT authentication (`Authorization: Bearer <token>`).

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `GET` | `/health` | Public | Service health check |
| `GET` | `/templates` | Super Admin | List templates (filterable by key, channel, is_active) |
| `POST` | `/templates` | Super Admin | Create a new template |
| `GET` | `/templates/{key}` | Super Admin | Get template by key |
| `PUT` | `/templates/{key}` | Super Admin | Update template (auto-increments version) |
| `DELETE` | `/templates/{key}` | Super Admin | Soft-delete template |
| `POST` | `/templates/{key}/render` | Admin | Render template with variables |

## Architecture

- **Tech**: Laravel 12, PHP 8.2, MySQL.
- **Auth**: RS256 JWT validation via `JwtAdminAuthMiddleware`. Tokens are issued by User Service.
- **Middleware**:
  - `CorrelationIdMiddleware` — propagates `X-Correlation-Id` on every request/response.
  - `RequestTimingMiddleware` — logs method, route, status, latency, actor in structured JSON.
  - `JwtAdminAuthMiddleware` — validates Bearer token; returns standardized error envelope on 401.
  - `RequireSuperAdminMiddleware` — gates CRUD operations to `super_admin` role.
- **Responses**: Standardized API envelope (`success`, `message`, `data`, `meta`, `correlation_id`).
- **Rendering**: `TemplateRenderService` validates variables against the template's `variables_schema`, then performs substitution on subject and body.

## Local Setup

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
php artisan serve --port=8004
```

Requires MySQL with database `np_template_service` created.

## Testing

```bash
php artisan test
```

Tests run against MySQL database `np_template_service_test` (configured in `phpunit.xml`). Uses `RefreshDatabase` for isolation.

**Test coverage:** 22 tests, 179 assertions — covers CRUD operations, validation, version bumping, rendering, soft deletes, auth, and authorization.

## Notes

- This is a **leaf service** — it does not make outbound calls to other services.
- Templates are routed by their `key` field (not UUID), which must be unique and use `alpha_dash` format.
- Inactive templates cannot be rendered (returns 409 Conflict).
