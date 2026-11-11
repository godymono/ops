window.DashboardApi = (function () {
    const cfg = () => window.OpsControl || {};

    function headers(json = true) {
        const h = {
            Accept: 'application/json',
            'X-CSRF-TOKEN': cfg().csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
        };
        if (json) h['Content-Type'] = 'application/json';
        return h;
    }

    async function request(url, options = {}) {
        const response = await fetch(url, options);
        let payload = null;
        const text = await response.text();
        try {
            payload = text ? JSON.parse(text) : null;
        } catch (e) {
            payload = { message: text };
        }

        if (!response.ok) {
            const message = payload?.message
                || (payload?.errors ? Object.values(payload.errors).flat().join(' ') : null)
                || `Request failed (HTTP ${response.status})`;
            throw new Error(message);
        }

        return payload;
    }

    return {
        async loadState() {
            return request(cfg().routes.state, { headers: headers(false) });
        },
        async loadOptions() {
            return request(cfg().routes.options, { headers: headers(false) });
        },
        async saveFullState(state) {
            return request(cfg().routes.saveState, {
                method: 'PUT',
                headers: headers(true),
                body: JSON.stringify({ state }),
            });
        },
        async resetDefaults() {
            return request(cfg().routes.reset, {
                method: 'POST',
                headers: headers(true),
                body: JSON.stringify({}),
            });
        },
        async createRecord(module, payload) {
            return request(`${cfg().routes.records}/${module}`, {
                method: 'POST',
                headers: headers(true),
                body: JSON.stringify(payload),
            });
        },
        async updateRecord(module, id, payload) {
            return request(`${cfg().routes.records}/${module}/${id}`, {
                method: 'PUT',
                headers: headers(true),
                body: JSON.stringify(payload),
            });
        },
        async updateStatus(module, id, status) {
            return request(`${cfg().routes.records}/${module}/${id}/status`, {
                method: 'PATCH',
                headers: headers(true),
                body: JSON.stringify({ status }),
            });
        },
        async deleteRecord(module, id) {
            return request(`${cfg().routes.records}/${module}/${id}`, {
                method: 'DELETE',
                headers: headers(false),
            });
        },
    };
})();
