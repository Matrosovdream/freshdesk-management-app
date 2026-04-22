# Freshdesk API — Request/Response Payloads

Concrete JSON shapes for every endpoint listed in [api-methods.md](api-methods.md). Field types are Freshdesk-declared; ✓ = required.

Common headers:
```
Authorization: Basic base64(API_KEY:X)
Content-Type: application/json
```

Attachments use `multipart/form-data` (field `attachments[]`) instead of JSON.

---

## 1. Tickets

### 1.1 Create Ticket — `POST /tickets`

**Request**
```json
{
  "subject": "string ✓",
  "description": "string ✓ (HTML allowed)",
  "status": 2,
  "priority": 1,
  "source": 2,
  "type": "Question",
  "requester_id": 0,
  "email": "jane@acme.com",
  "phone": "string",
  "name": "string",
  "unique_external_id": "string",
  "facebook_id": "string",
  "twitter_id": "string",
  "responder_id": 0,
  "group_id": 0,
  "company_id": 0,
  "product_id": 0,
  "email_config_id": 0,
  "parent_id": 0,
  "cc_emails": ["cc@acme.com"],
  "tags": ["billing"],
  "due_by": "2026-04-30T10:00:00Z",
  "fr_due_by": "2026-04-22T10:00:00Z",
  "custom_fields": { "category": "Primary" },
  "attachments": []
}
```

One requester identifier is required: `requester_id` OR `email` OR `phone` OR `twitter_id` OR `facebook_id` OR `unique_external_id`.

**Enums**
- `status`: 2 Open, 3 Pending, 4 Resolved, 5 Closed
- `priority`: 1 Low, 2 Medium, 3 High, 4 Urgent
- `source`: 1 Email, 2 Portal, 3 Phone, 7 Chat, 9 Feedback Widget, 10 Outbound Email

**Response `201`**
```json
{
  "id": 1,
  "subject": "...",
  "description": "<div>...</div>",
  "description_text": "...",
  "status": 2,
  "priority": 1,
  "source": 2,
  "type": "Question",
  "requester_id": 100,
  "responder_id": null,
  "group_id": null,
  "company_id": null,
  "product_id": null,
  "email_config_id": null,
  "to_emails": [],
  "cc_emails": [],
  "fwd_emails": [],
  "reply_cc_emails": [],
  "tags": [],
  "spam": false,
  "is_escalated": false,
  "fr_escalated": false,
  "due_by": "2026-04-30T10:00:00Z",
  "fr_due_by": "2026-04-22T10:00:00Z",
  "created_at": "2026-04-21T09:00:00Z",
  "updated_at": "2026-04-21T09:00:00Z",
  "custom_fields": {},
  "attachments": []
}
```

### 1.2 View Ticket — `GET /tickets/{id}`

Query params: `include=conversations,requester,company,stats`

**Response `200`** — same shape as Create response (+ included relations when requested).

### 1.3 List Tickets — `GET /tickets`

Query params: `filter=new_and_my_open|watching|spam|deleted`, `requester_id=`, `email=`, `company_id=`, `updated_since=ISO8601`, `order_by=created_at|due_by|updated_at|status`, `order_type=asc|desc`, `include=requester,stats,description`, `page=`, `per_page=` (≤100).

**Response `200`** — array of ticket objects (without `description` by default).

### 1.4 Update Ticket — `PUT /tickets/{id}`

**Request** — any subset of create fields.
**Response `200`** — full ticket object.

### 1.5 Delete / Restore

- `DELETE /tickets/{id}` → `204`
- `POST /tickets/{id}/restore` → `204`

### 1.6 Filter Tickets — `GET /search/tickets?query="..."`

Query is URL-encoded and quoted:
```
query="status:2 AND priority:>2 AND updated_at:>'2026-04-01'"
```

**Response `200`**
```json
{ "total": 42, "results": [ /* ticket objects */ ] }
```

### 1.7 Bulk Update — `PUT /tickets/bulk_update`

**Request**
```json
{
  "bulk_action": {
    "ids": [1, 2, 3],
    "properties": { "status": 5, "responder_id": 17, "tags": ["resolved"] },
    "reply": { "body": "Closing as resolved" }
  }
}
```
**Response `202`** → job id (poll via Jobs API).

### 1.8 Bulk Delete — `DELETE /tickets/bulk_delete`

```json
{ "bulk_action": { "ids": [1, 2, 3] } }
```

### 1.9 Forward — `POST /tickets/{id}/forward`

```json
{
  "body": "<p>Forwarding FYI</p>",
  "to_emails": ["ops@acme.com"],
  "cc_emails": [],
  "bcc_emails": [],
  "attachments": []
}
```

### 1.10 Merge — `POST /tickets/merge`

```json
{
  "primary_id": 10,
  "ticket_ids": [11, 12],
  "convert_recepients_to_cc": true,
  "note": { "body": "Merged duplicates", "private": true }
}
```

### 1.11 Outbound Email — `POST /tickets/outbound_email`

```json
{
  "email": "jane@acme.com",
  "subject": "Follow-up",
  "description": "<p>Hi Jane…</p>",
  "email_config_id": 5,
  "group_id": 2,
  "priority": 1,
  "status": 2,
  "tags": [],
  "custom_fields": {}
}
```

---

## 2. Conversations

### 2.1 Create Reply — `POST /tickets/{id}/reply`

**Request**
```json
{
  "body": "<p>Thanks for reporting…</p>",
  "from_email": "support@acme.freshdesk.com",
  "user_id": 17,
  "cc_emails": [],
  "bcc_emails": [],
  "attachments": []
}
```

**Response `201`**
```json
{
  "id": 1001,
  "body": "<p>…</p>",
  "body_text": "…",
  "from_email": "support@acme.freshdesk.com",
  "to_emails": ["jane@acme.com"],
  "cc_emails": [],
  "bcc_emails": [],
  "ticket_id": 20,
  "user_id": 17,
  "incoming": false,
  "private": false,
  "source": 0,
  "created_at": "2026-04-21T09:30:00Z",
  "updated_at": "2026-04-21T09:30:00Z",
  "attachments": []
}
```

### 2.2 Create Note — `POST /tickets/{ticket_id}/notes`

**Request**
```json
{
  "body": "<p>Internal note</p>",
  "private": true,
  "incoming": false,
  "notify_emails": [],
  "user_id": 17,
  "attachments": []
}
```

**Response `201`** — conversation object with `private: true`, `source: 2` (Note).

### 2.3 List Conversations — `GET /tickets/{id}/conversations`

Query params: `page=`, `per_page=`.
**Response `200`** — array of conversation objects.

### 2.4 Reply to Forward — `POST /tickets/{id}/reply_to_forward`

Same shape as Forward.

### 2.5 Update / Delete Conversation

- `PUT /conversations/{id}` → `{ "body": "...", "attachments": [] }`
- `DELETE /conversations/{id}` → `204`
- `DELETE /attachments/{id}` → `204`

---

## 3. Contacts

### 3.1 Create — `POST /contacts`

**Request**
```json
{
  "name": "string ✓",
  "email": "jane@acme.com",
  "phone": "string",
  "mobile": "string",
  "twitter_id": "string",
  "unique_external_id": "string",
  "company_id": 0,
  "view_all_tickets": false,
  "other_emails": ["alt@acme.com"],
  "other_companies": [{ "company_id": 0, "view_all_tickets": false }],
  "address": "string",
  "job_title": "string",
  "language": "en",
  "time_zone": "Eastern Time (US & Canada)",
  "tags": [],
  "custom_fields": {}
}
```
At least one of: `email`, `phone`, `mobile`, `twitter_id`, `unique_external_id`.

**Response `201`**
```json
{
  "id": 100,
  "name": "Jane Doe",
  "email": "jane@acme.com",
  "phone": null,
  "mobile": null,
  "company_id": null,
  "view_all_tickets": false,
  "active": false,
  "deleted": false,
  "other_emails": [],
  "other_companies": [],
  "address": null,
  "job_title": null,
  "language": "en",
  "time_zone": "Eastern Time (US & Canada)",
  "tags": [],
  "twitter_id": null,
  "unique_external_id": null,
  "custom_fields": {},
  "created_at": "2026-04-21T09:00:00Z",
  "updated_at": "2026-04-21T09:00:00Z"
}
```

### 3.2 View / List / Update / Delete

- `GET /contacts/{id}` → contact object (+ `avatar` when set).
- `GET /contacts` — filters: `email=`, `mobile=`, `phone=`, `company_id=`, `state=verified|unverified|blocked|deleted`, `updated_since=`.
- `PUT /contacts/{id}` — any subset of create fields.
- `DELETE /contacts/{id}` (soft) → `204`
- `DELETE /contacts/{id}/hard_delete?force=true` → `204`
- `PUT /contacts/{id}/restore` → `204`

### 3.3 Autocomplete / Search

- `GET /contacts/autocomplete?term=jan` → `[{ "id", "name", "email" }, …]`
- `GET /search/contacts?query="email:'jane@acme.com'"` → `{ "total", "results" }`

### 3.4 Merge — `POST /contacts/merge`

```json
{
  "primary_contact_id": 100,
  "secondary_contact_ids": [101, 102],
  "contact": { "email": "jane@acme.com", "phone": "...", "company_id": 5 }
}
```

### 3.5 Send Invite / Make Agent

- `POST /contacts/{id}/send_invite` → `204`
- `POST /contacts/{id}/make_agent` → agent object

---

## 4. Companies

### 4.1 Create — `POST /companies`

**Request**
```json
{
  "name": "string ✓ (unique)",
  "description": "string",
  "domains": ["acme.com"],
  "note": "string",
  "health_score": "string",
  "account_tier": "string",
  "renewal_date": "2027-01-01",
  "industry": "string",
  "custom_fields": {}
}
```

**Response `201`**
```json
{
  "id": 5,
  "name": "Acme",
  "description": "…",
  "domains": ["acme.com"],
  "note": null,
  "health_score": null,
  "account_tier": null,
  "renewal_date": null,
  "industry": null,
  "custom_fields": {},
  "created_at": "2026-04-21T09:00:00Z",
  "updated_at": "2026-04-21T09:00:00Z"
}
```

### 4.2 View / List / Update / Delete

- `GET /companies/{id}` → company object.
- `GET /companies?updated_since=` — list.
- `PUT /companies/{id}` — subset of create fields.
- `DELETE /companies/{id}` → `204`

### 4.3 Autocomplete / Search

- `GET /companies/autocomplete?name=ac` → `{ "companies": [...] }`
- `GET /search/companies?query="domain:'acme.com'"` → `{ "total", "results" }`

---

## 5. Agents

### 5.1 View / Me / List

- `GET /agents/{id}` / `GET /agents/me`

**Response `200`**
```json
{
  "id": 17,
  "available": true,
  "occasional": false,
  "signature": "<p>…</p>",
  "ticket_scope": 1,
  "type": "support_agent",
  "group_ids": [2, 3],
  "role_ids": [1],
  "skill_ids": [],
  "created_at": "2026-01-01T00:00:00Z",
  "updated_at": "2026-04-20T10:00:00Z",
  "contact": {
    "name": "Agent A",
    "email": "agent@acme.com",
    "active": true,
    "job_title": "Support Lead",
    "language": "en",
    "time_zone": "Eastern Time (US & Canada)"
  }
}
```
`ticket_scope`: 1 Global, 2 Group, 3 Restricted.
`type`: `support_agent | field_agent | collaborator`.

### 5.2 Create / Update / Delete

- `POST /agents` — `{ "email" ✓, "ticket_scope", "occasional", "signature", "skill_ids", "group_ids", "role_ids" ✓ }`
- `PUT /agents/{id}` — subset.
- `DELETE /agents/{id}` — downgrades to contact.

### 5.3 Bulk Create — `POST /agents/bulk`

```json
{ "agents": [ { "email": "a@x.com", "role_ids": [1] }, { "email": "b@x.com", "role_ids": [1] } ] }
```

### 5.4 Autocomplete — `GET /agents/autocomplete?term=al`

`[{ "id", "contact": { "name", "email" } }, …]`

---

## 6. Groups

### 6.1 Create — `POST /groups`

```json
{
  "name": "string ✓",
  "description": "string",
  "unassigned_for": "30m",
  "business_hour_id": 0,
  "escalate_to": 17,
  "agent_ids": [17, 18],
  "auto_ticket_assign": false
}
```
`unassigned_for`: `30m | 1h | 2h | 4h | 8h | 12h | 1d | 2d | 3d`.

**Response `201`** — group object with same fields + `id`, timestamps.

### 6.2 View / List / Update / Delete

- `GET /groups/{id}`, `GET /groups`, `PUT /groups/{id}`, `DELETE /groups/{id}`.

---

## 7. Roles

- `GET /roles/{id}` and `GET /roles`

```json
{
  "id": 1,
  "name": "Agent",
  "description": "Can view and reply to tickets",
  "default": true,
  "agent_type": 1,
  "created_at": "2026-01-01T00:00:00Z",
  "updated_at": "2026-01-01T00:00:00Z"
}
```

---

## 8. Time Entries

### 8.1 Create — `POST /tickets/{id}/time_entries`

**Request**
```json
{
  "time_spent": "02:00",
  "note": "Investigated and resolved issue",
  "billable": true,
  "agent_id": 17,
  "executed_at": "2026-04-21T09:00:00Z",
  "timer_running": false
}
```
`time_spent` format: `HH:MM`. If omitted with `timer_running: true`, a timer starts.

**Response `201`**
```json
{
  "id": 5001,
  "time_spent": "02:00",
  "note": "…",
  "billable": true,
  "timer_running": false,
  "agent_id": 17,
  "ticket_id": 20,
  "executed_at": "2026-04-21T09:00:00Z",
  "start_time": "2026-04-21T09:00:00Z",
  "created_at": "2026-04-21T11:00:00Z",
  "updated_at": "2026-04-21T11:00:00Z"
}
```

### 8.2 List / Update / Delete

- `GET /tickets/{id}/time_entries`
- `PUT /time_entries/{id}` — subset of create fields.
- `DELETE /time_entries/{id}` → `204`

---

## 9. Config endpoints (read-only usage)

### 9.1 Ticket Fields — `GET /ticket_fields`

```json
[
  {
    "id": 1,
    "name": "status",
    "label": "Status",
    "description": "Ticket status",
    "position": 1,
    "required_for_closure": false,
    "required_for_agents": true,
    "type": "default_status",
    "default": true,
    "customers_can_edit": false,
    "label_for_customers": "Status",
    "required_for_customers": false,
    "displayed_to_customers": true,
    "choices": { "2": ["Open", "Open"], "3": ["Pending", "Pending"] },
    "created_at": "…",
    "updated_at": "…"
  }
]
```

### 9.2 Products — `GET /products`

```json
[
  { "id": 1, "name": "Acme Cloud", "description": "", "primary_email": "cloud@acme.com", "created_at": "…", "updated_at": "…" }
]
```

### 9.3 Business Hours — `GET /business_hours`

```json
[
  {
    "id": 1,
    "name": "Default",
    "description": "",
    "time_zone": "Eastern Time (US & Canada)",
    "service_desk_availability": {
      "monday": { "beginning_of_workday": "09:00", "end_of_workday": "17:00" }
    },
    "list_of_holidays": [],
    "created_at": "…",
    "updated_at": "…"
  }
]
```

### 9.4 SLA Policies — `GET /sla_policies`

```json
[
  {
    "id": 1,
    "name": "Default SLA Policy",
    "description": "",
    "active": true,
    "is_default": true,
    "position": 1,
    "applicable_to": { "company_ids": [], "group_ids": [], "product_ids": [], "sources": [] },
    "sla_target": {
      "priority_1": { "escalation_enabled": true, "respond_within": 3600, "resolve_within": 86400 }
    },
    "created_at": "…",
    "updated_at": "…"
  }
]
```

### 9.5 Automations — `GET /automations/{type}/rules`

`{type}`: `1` Ticket creation, `3` Time triggers, `4` On-ticket updates.
Returns array of rule objects; each rule has `name`, `active`, `position`, `performer`, `events`, `conditions`, `operator`, `actions`.

---

## Error shape

Non-2xx responses:
```json
{
  "description": "Validation failed",
  "errors": [
    { "field": "email", "message": "email is invalid", "code": "invalid_value" }
  ]
}
```
Status codes in use: 400, 401, 403, 404, 405, 409, 415, 429, 500.
