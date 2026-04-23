import { defineStore } from 'pinia';
import { http, ensureCsrf } from '@shared/http';

// Response envelopes we accept:
//   [a, b]                              — bare array
//   { data: [a, b] }                    — single-wrapped
//   { data: [a, b], meta: {...} }       — paginated (no outer data)
//   { data: { data: [...], meta: {} } } — double-wrapped (controller + ApiQuery::page)
function unwrapList(body) {
    if (Array.isArray(body)) return { items: body, meta: null };
    if (body && Array.isArray(body.data)) return { items: body.data, meta: body.meta ?? null };
    if (body && body.data && Array.isArray(body.data.data)) return { items: body.data.data, meta: body.data.meta ?? null };
    return { items: [], meta: null };
}

/**
 * Factory for list-style stores — shared pagination/selection/CRUD pattern.
 * Each domain store adds its own specialised actions on top.
 */
export function createListStore(id, basePath) {
    return defineStore(id, {
        state: () => ({
            items: [],
            byId: {},
            filters: {},
            cursor: null,
            hasMore: false,
            loading: false,
            error: null,
            selectedIds: [],
            currentItem: null,
        }),

        getters: {
            count: (state) => state.items.length,
            isSelected: (state) => (id) => state.selectedIds.includes(id),
        },

        actions: {
            setFilters(f) {
                this.filters = { ...this.filters, ...f };
            },
            resetFilters() {
                this.filters = {};
            },
            select(id) {
                if (!this.selectedIds.includes(id)) this.selectedIds.push(id);
            },
            deselect(id) {
                this.selectedIds = this.selectedIds.filter((x) => x !== id);
            },
            clearSelection() {
                this.selectedIds = [];
            },

            async fetch(filters = {}) {
                this.loading = true;
                this.error = null;
                try {
                    this.filters = { ...this.filters, ...filters };
                    const { data } = await http.get(basePath, { params: this.filters });
                    const { items, meta } = unwrapList(data);
                    this.items = items;
                    this.byId = Object.fromEntries(items.map((r) => [r.id, r]));
                    this.cursor = meta?.next_cursor ?? null;
                    this.hasMore = !!this.cursor;
                } catch (e) {
                    this.error = e;
                    throw e;
                } finally {
                    this.loading = false;
                }
            },

            async fetchNextPage() {
                if (!this.hasMore || this.loading) return;
                this.loading = true;
                try {
                    const { data } = await http.get(basePath, {
                        params: { ...this.filters, cursor: this.cursor },
                    });
                    const { items, meta } = unwrapList(data);
                    for (const item of items) {
                        if (!this.byId[item.id]) this.items.push(item);
                        this.byId[item.id] = item;
                    }
                    this.cursor = meta?.next_cursor ?? null;
                    this.hasMore = !!this.cursor;
                } finally {
                    this.loading = false;
                }
            },

            async refresh() {
                return this.fetch();
            },

            async show(id) {
                this.loading = true;
                try {
                    const { data } = await http.get(`${basePath}/${id}`);
                    this.currentItem = data?.data ?? data ?? null;
                    if (this.currentItem) this.byId[this.currentItem.id] = this.currentItem;
                    return this.currentItem;
                } finally {
                    this.loading = false;
                }
            },

            async create(payload, opts = {}) {
                await ensureCsrf();
                const config = opts.multipart ? { headers: { 'Content-Type': 'multipart/form-data' } } : {};
                const { data } = await http.post(basePath, payload, config);
                const item = data?.data ?? data;
                if (item?.id != null) {
                    if (!Array.isArray(this.items)) this.items = [];
                    this.items.unshift(item);
                    this.byId[item.id] = item;
                }
                return item;
            },

            async update(id, patch) {
                await ensureCsrf();
                const { data } = await http.put(`${basePath}/${id}`, patch);
                const item = data?.data ?? data;
                if (item?.id != null) {
                    this.byId[item.id] = item;
                    if (Array.isArray(this.items)) {
                        this.items = this.items.map((r) => (r.id === item.id ? item : r));
                    }
                    if (this.currentItem?.id === item.id) this.currentItem = item;
                }
                return item;
            },

            async destroy(id) {
                await ensureCsrf();
                await http.delete(`${basePath}/${id}`);
                this.items = this.items.filter((r) => r.id !== id);
                delete this.byId[id];
                this.selectedIds = this.selectedIds.filter((x) => x !== id);
            },
        },
    });
}
