# Freshdesk API — Methods We Use

Source: https://developers.freshdesk.com/api/

Base URL: `https://{domain}.freshdesk.com/api/v2`
Auth: HTTP Basic — `Authorization: Basic base64(api_key:X)`

## Platform basics

- **Rate limit:** account-wide, 3k–5k calls/hr by plan (50/min on trial). Respect `X-RateLimit-Remaining`; on 429 wait per `Retry-After`.
- **Pagination:** `?page=&per_page=` (max 100; avoid page > 500). Next URL in `Link` header.
- **Search:** `/search/{resource}?query=...` with AND/OR and `:>`/`:<` operators; URL-encoded; max 512 chars.
- **Webhooks:** not documented in public API — we poll with `updated_since`.

---

## 1. Tickets

| Op | Method | Path |
|---|---|---|
| Create | POST | `/tickets` |
| View | GET | `/tickets/{id}` |
| List | GET | `/tickets` |
| Update | PUT | `/tickets/{id}` |
| Delete | DELETE | `/tickets/{id}` |
| Filter (search) | GET | `/search/tickets?query=...` |
| Bulk update | PUT | `/tickets/bulk_update` |
| Bulk delete | DELETE | `/tickets/bulk_delete` |
| Forward | POST | `/tickets/{id}/forward` |
| Merge | POST | `/tickets/merge` |
| Restore | POST | `/tickets/{id}/restore` |
| Outbound email | POST | `/tickets/outbound_email` |
| List conversations | GET | `/tickets/{id}/conversations` |
| List time entries | GET | `/tickets/{id}/time_entries` |
| List satisfaction ratings | GET | `/tickets/{ticket_id}/satisfaction_ratings` |
| Associated tickets | GET | `/tickets/{id}/associated_tickets` |

## 2. Conversations (replies & notes)

| Op | Method | Path |
|---|---|---|
| Create reply | POST | `/tickets/{id}/reply` |
| Create note | POST | `/tickets/{ticket_id}/notes` |
| Reply to forward | POST | `/tickets/{id}/reply_to_forward` |
| Update conversation | PUT | `/conversations/{id}` |
| Delete conversation | DELETE | `/conversations/{id}` |
| Delete attachment | DELETE | `/attachments/{id}` |

## 3. Contacts

| Op | Method | Path |
|---|---|---|
| Create | POST | `/contacts` |
| View | GET | `/contacts/{id}` |
| List | GET | `/contacts` |
| Update | PUT | `/contacts/{id}` |
| Soft delete | DELETE | `/contacts/{id}` |
| Hard delete | DELETE | `/contacts/{id}/hard_delete` |
| Restore | PUT | `/contacts/{id}/restore` |
| Autocomplete | GET | `/contacts/autocomplete?term=...` |
| Search | GET | `/search/contacts?query=...` |
| Send invite | POST | `/contacts/{id}/send_invite` |
| Merge | POST | `/contacts/merge` |
| Make agent | POST | `/contacts/{id}/make_agent` |

## 4. Companies

| Op | Method | Path |
|---|---|---|
| Create | POST | `/companies` |
| View | GET | `/companies/{id}` |
| List | GET | `/companies` |
| Update | PUT | `/companies/{id}` |
| Delete | DELETE | `/companies/{id}` |
| Autocomplete | GET | `/companies/autocomplete?name=...` |
| Search | GET | `/search/companies?query=...` |

## 5. Agents

| Op | Method | Path |
|---|---|---|
| Create | POST | `/agents` |
| View | GET | `/agents/{id}` |
| List | GET | `/agents` |
| Update | PUT | `/agents/{id}` |
| Delete | DELETE | `/agents/{id}` |
| Me | GET | `/agents/me` |
| Bulk create | POST | `/agents/bulk` |
| Autocomplete | GET | `/agents/autocomplete?term=...` |

## 6. Groups

| Op | Method | Path |
|---|---|---|
| Create | POST | `/groups` |
| View | GET | `/groups/{id}` |
| List | GET | `/groups` |
| Update | PUT | `/groups/{id}` |
| Delete | DELETE | `/groups/{id}` |

## 7. Roles (read-only)

| Op | Method | Path |
|---|---|---|
| View | GET | `/roles/{id}` |
| List | GET | `/roles` |

## 8. Time Entries

| Op | Method | Path |
|---|---|---|
| Create | POST | `/tickets/{id}/time_entries` |
| List per ticket | GET | `/tickets/{id}/time_entries` |
| Update | PUT | `/time_entries/{id}` |
| Delete | DELETE | `/time_entries/{id}` |

## 9. Config (read-mostly)

| Resource | Method | Path |
|---|---|---|
| Ticket fields | GET | `/ticket_fields` |
| Products | GET | `/products` |
| Business hours | GET | `/business_hours` |
| SLA policies | GET | `/sla_policies` |
| Automations | GET | `/automations/{type}/rules` |

---

## Deferred (not in v1)

Solutions, Discussions, Canned Responses, Field Service Management, Custom Objects, Omnichannel Activities, Email Configs, Email Mailboxes, Skills, Jobs, Settings, CSAT survey admin, Contact/Company field definitions.
