# CRUD query and mutation contract

Gate 2 item: this contract was implicit in the Users admin module (`app/Http/Controllers/Api/Admin/UserAdminController.php` + `resources/js/app/pages/admin/users.vue`) but never written down, so every new admin/CRUD screen either copied Users by eye or reinvented a slightly different shape. This is that write-up — Users is the reference implementation, not a separate thing to keep in sync with it.

Nothing here is new behavior. If this doc and the Users module ever disagree, the code is right and this doc is stale — fix the doc.

## When this applies

A resource with a paginated list (search/filter/sort), create, update, and a soft delete (archive/restore). Not every CRUD screen needs all of this — a settings-style single-record form, for instance, just needs the validation-error piece. Use what fits.

## Backend

### Response envelope

Every `/api/*` response — success, validation failure, or exception — uses `App\Http\Responses\Envelope`:

```json
{ "success": true, "message": "", "data": ..., "meta": {}, "errors": {} }
```

`Envelope::success($data, $message, $meta, $status)` and `Envelope::error($message, $errors, $status, $meta)`. Never build a raw `response()->json([...])` for an endpoint a store will consume — the frontend's `normalizeErrorMessage`/`validationErrors` helpers (below) assume this shape.

Passing a `LengthAwarePaginator` (or a `ResourceCollection` wrapping one) as `$data` automatically folds pagination into `meta.pagination`:

```json
{ "current_page": 1, "per_page": 10, "last_page": 4, "total": 37 }
```

### List endpoint (`GET`)

Query params, all optional except `page`/`per_page` (which have defaults):

| Param                         | Meaning                                                                                                                   |
| ----------------------------- | ------------------------------------------------------------------------------------------------------------------------- |
| `page`                        | 1-indexed page number                                                                                                     |
| `per_page`                    | clamp server-side — `max(1, min((int) $request->integer('per_page', 10), 100))`, never trust the client's number directly |
| `search`                      | free-text filter, resource-defined which columns it touches                                                               |
| `sort_by`                     | a resource-defined allow-list of sortable columns, never a raw column name from the client                                |
| `sort_direction`              | `asc` (default) or `desc`                                                                                                 |
| _(resource-specific filters)_ | e.g. Users' `role`/`status` — same "read, default to null if absent" treatment                                            |

Controller responsibility is thin — parse/clamp query params, delegate to the service, wrap the result:

```php
public function index(Request $request): JsonResponse
{
    $perPage = max(1, min((int) $request->integer('per_page', 10), 100));
    $search = $request->string('search')->toString() ?: null;
    // ...resource-specific filters, same pattern...

    $rows = $this->service->paginate($perPage, $search, /* ... */, $sortBy, $sortDirection);

    return Envelope::success(ResourceCollection::collection($rows), '', [
        'options' => [/* dropdown option sets the list screen's filter bar needs, e.g. 'roles' => [...] */],
    ]);
}
```

`meta.options` is where a list screen's filter dropdowns get their choices — computed once per request from the service (`availableRoles()`, etc.), not hardcoded in the frontend.

### Export endpoint (`GET .../export`)

Same filters as the list endpoint, unpaginated (the service's `filtered()` method, not `paginate()`), streamed through `Maatwebsite\Excel` via the shared `App\Exports\CollectionExport` — never hand-roll `fputcsv`. See `feedback_excel_exports` — one generic export class, not one per resource. `filtered()` caps its result (10,000 rows) — export is a synchronous request/response, not a queued job, so an unfiltered export on a huge table shouldn't be able to tie up a worker indefinitely.

### Permissions

An export endpoint sits behind the _same_ permission as its index counterpart (`can:users.view` for both `GET /users` and `GET /users/export`) — exporting isn't a separate capability from viewing. `store`/`update`/`destroy` each get their own permission (`users.create`, `users.update`, `users.delete`) rather than sharing one — a role that can view and edit shouldn't automatically be able to delete. `restore` shares the `destroy` permission (`users.delete`) rather than getting its own — undoing a delete is the same capability as doing it, not a separate one.

### Create / update (`POST` / `PATCH`)

A dedicated `FormRequest` per action (`StoreXRequest`/`UpdateXRequest`), not one request class branching on method. `authorize()` returns `$this->user() !== null` — the route's own `auth:sanctum` + `can:` middleware is the real authorization boundary, this is just "must be logged in to even validate."

Update rules use `sometimes` so partial updates don't force every field:

```php
'name' => ['sometimes', 'required', 'string', 'max:255'],
'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
```

If a rule depends on an optional table that might not exist yet in a fresh/partial install (roles/permissions tables, say), guard it rather than letting validation itself 500:

```php
$hasRolesTable = Schema::hasTable('roles');
'roles.*' => $hasRolesTable ? ['string', 'exists:roles,name'] : ['string'],
```

Controller stays thin: validate via the FormRequest, delegate to the service, wrap the result.

```php
public function store(StoreUserRequest $request): JsonResponse
{
    $user = $this->service->create($request->validated());

    return Envelope::success(new UserAdminResource($user), 'User created.', [], 201);
}
```

### Archive / restore (`DELETE` / `POST .../restore`)

Soft delete (`SoftDeletes` on the model), not a hard `delete()` — an admin-facing "archive" almost always needs to be reversible. Guard self-inflicted and integrity-breaking actions in the controller _before_ calling the service, with a plain `422`, not a 500 or a silent no-op:

```php
if ($user->is($request->user())) {
    return Envelope::error('You cannot archive your own account.', [], 422);
}
```

Domain-level guards (e.g. "can't archive the last super-admin") belong in the service, surfaced as a `RuntimeException` the controller catches and turns into the same `422` shape:

```php
try {
    $archived = $this->service->archive($user);
} catch (RuntimeException $exception) {
    return Envelope::error($exception->getMessage(), [], 422);
}
```

### Service layer

One interface (`App\Contracts\XServiceInterface`), one implementation, bound in a `ServiceProvider::register()` — never call Eloquent directly from a controller for anything beyond the trivial. This is what makes `paginate()`/`filtered()` share filtering logic instead of duplicating query-building between the list and export endpoints, and it's the seam integration tests mock against (see `SubscriptionTest` mocking `PayFastCheckoutServiceInterface`).

```php
interface UserAdminServiceInterface
{
    public function paginate(int $perPage = 15, ?string $search = null, /* ...filters... */, ?string $sortBy = null, string $sortDirection = 'asc'): LengthAwarePaginator;
    public function filtered(/* same filters, unpaginated */): Collection;
    public function create(array $data): User;
    public function update(User $user, array $data): User;
    public function archive(User $user): User; // @throws RuntimeException on a domain guard failure
    public function restore(User $user): User;
    public function availableRoles(): array; // feeds meta.options
}
```

### API Resource

Plain `JsonResource`, no surprises — but guard optional relations the same way the FormRequest guards optional tables, so a resource with roles doesn't 500 on an install that hasn't seeded `permissions` yet:

```php
$roles = $canReadRoles && method_exists($this->resource, 'getRoleNames')
    ? $this->resource->getRoleNames()->values()->all()
    : [];
```

Expose the soft-delete timestamp under a domain name (`archived_at`), not the raw column (`deleted_at`) — the frontend shouldn't know or care that archiving is implemented as a soft delete.

## Frontend

### Pinia store shape

```js
state: () => ({
    rows: [],
    meta: { current_page: 1, last_page: 1, per_page: 10, total: 0 },
    options: { roles: [], /* ... */ },
    loading: false,
}),
actions: {
    async fetch({ page = 1, perPage = 10, search = '', /* ...filters... */, sortBy = '', sortDirection = 'asc' } = {}) {
        this.loading = true;
        try {
            const params = new URLSearchParams({ page, per_page: perPage });
            if (search) params.set('search', search);
            if (sortBy) { params.set('sort_by', sortBy); params.set('sort_direction', sortDirection); }

            const response = await v1(`users?${params}`);
            this.rows = response?.data ?? [];
            this.meta = response?.meta?.pagination ?? this.meta;
            this.options = response?.meta?.options ?? this.options;
            return response;
        } finally {
            this.loading = false;
        }
    },
    async archive(id) { /* DELETE, return response?.data ?? response */ },
    async restore(id) { /* POST .../restore */ },
    // No dedicated bulk endpoint for a light, idempotent-ish write like archive —
    // fan out to the single-item endpoint with Promise.allSettled and report
    // succeeded/failed counts. A resource with heavier bulk semantics (partial-
    // failure detail, needs a transaction) earns a real POST /bulk endpoint instead
    // — don't build that until something actually needs it.
    async bulkArchive(ids) {
        const results = await Promise.allSettled(ids.map((id) => this.archive(id)));
        return {
            succeeded: results.filter((r) => r.status === 'fulfilled').length,
            failed: results.filter((r) => r.status === 'rejected').length,
        };
    },
    async create(payload) { /* POST, return response?.data ?? response */ },
    async update(id, payload) { /* PATCH, return response?.data ?? response */ },
},
```

Use `v1(...)` (the `/api/v1`-scoped `ofetch` client from `resources/js/app/utils/api.js`), not a bare `fetch`.

### List screen: `AppFilterBar` + `AppDataTable`

```html
<AppFilterBar>
    <AppTextField v-model="filters.search" label="Search" @update:model-value="onSearch" />
    <AppSelect v-model="filters.role" :items="roleOptions" @update:model-value="onFilterChange" />
</AppFilterBar>

<AppDataTable
    table-id="admin-users"
    :columns="columns"
    :rows="store.rows"
    :meta="store.meta"
    :loading="store.loading"
    selectable
    clickable-rows
    :selected="selected"
    :sort-by="filters.sortBy"
    :sort-direction="filters.sortDirection"
    :export-href="exportHref"
    @update:selected="selected = $event"
    @sort="onSort"
    @page-change="onPage"
    @row-click="openEdit"
>
    <template #bulk-actions="{ selected: selectedIds, clear }">…</template>
    <template #row="{ row }">…custom cells…</template>
</AppDataTable>
```

`columns`: `{ key, label, sortable?, sortKey?, hideable?, class?, srLabel? }[]`. Set `srLabel` on any visually-empty header (an actions column, typically) — axe's empty-table-header accessibility rule catches this if omitted.

Sort/page/filter state is **parent-owned**, not internal to `AppDataTable` — the table is a dumb renderer of whatever page the store already fetched. All four handlers follow the same shape: mutate the local `filters` reactive object, then call `load()`:

```js
const filters = reactive({ search: '', role: '', status: '', page: 1, sortBy: '', sortDirection: 'asc' });

const onSearch = (val) => {
    filters.search = val;
    filters.page = 1;
    load();
};
const onFilterChange = () => {
    filters.page = 1;
    selected.value = [];
    load();
};
const onSort = ({ sortBy, sortDirection }) => {
    filters.sortBy = sortBy;
    filters.sortDirection = sortDirection;
    load();
};
const onPage = (page) => {
    filters.page = page;
    load();
};
```

`exportHref` is a plain computed URL string built from the same `filters` — `AppDataTable` renders it as a real `<a href>` so the browser handles the download (cookies included automatically), not a fetch-then-blob dance.

`usePersistedFilters(storageKey, filters, { exclude: ['page'] })`, called once before the initial `load()`, remembers a user's last filter/sort state per table in `localStorage`. Optional — every list screen doesn't need it, but it's a one-line add when a screen has more than a search box.

### Validation errors

`resources/js/app/stores/auth-shared.js` exports the two helpers every form uses against the envelope's `errors`/`message` fields:

```js
normalizeErrorMessage(error, fallback); // -> error?.data?.message || error?.data?.error || error?.message || fallback
validationErrors(error); // -> error?.data?.errors || {}
```

```js
const submitDialog = async () => {
    dialog.errors = {};
    dialog.message = '';
    try {
        await store.create(dialog.form);
        closeDialog();
        await load();
    } catch (error) {
        dialog.errors = validationErrors(error); // field-level, feeds FormField's aria-invalid state
        dialog.message = normalizeErrorMessage(error); // top-of-form banner
    }
};
```

For an action with no inline form to show errors against (archive, restore, bulk actions), skip the field-level mapping and just surface the message globally: `useAppErrorsStore().show({ message: normalizeErrorMessage(error, 'Unable to archive that user.') })`.

## What this contract deliberately doesn't cover

- **Nested/relational create-in-one-request forms** (e.g. an order with line items) — Users' roles/permissions are flat arrays of strings, not a real nested-resource problem. Design that separately if a module needs it.
- **Optimistic UI / client-side cache invalidation** — every mutation here re-`load()`s from the server. Fine at this app's scale; revisit if a screen's list is ever expensive enough that a full reload after every edit becomes the bottleneck.
- **A dedicated bulk-mutation endpoint** — only build one when a resource's bulk semantics genuinely need it (partial-failure detail, a transaction). Fanning out to the single-item endpoint is the default, not a placeholder for something better.
- **Real-time/websocket updates to a list** — nothing here pushes; every screen pulls on its own actions (search, sort, page, after a mutation).
