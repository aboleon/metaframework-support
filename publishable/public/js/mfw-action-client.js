class MfwActionError extends Error {
    constructor(message, status, payload, response) {
        super(message);
        this.name = 'MfwActionError';
        this.status = status;
        this.payload = payload;
        this.response = response;
    }
}

class MfwActionFeedback {
    static alertType(type) {
        return {
            danger: 'danger',
            error: 'danger',
            warning: 'warning',
            success: 'success',
            info: 'info',
            status: 'info',
        }[type] ?? 'info';
    }

    static alertsFromMfwMessages(messages) {
        if (!Array.isArray(messages)) {
            return [];
        }

        return messages.flatMap((messageGroup) => Object.entries(messageGroup ?? {}).map(([type, message]) => ({
            type: MfwActionFeedback.alertType(type),
            message,
        })));
    }

    static alertsFromError(error, fallbackMessage = 'La requête n’a pas pu être traitée.') {
        const payload = error?.payload ?? error?.responseJSON ?? {};

        if (payload.errors) {
            return Object.values(payload.errors)
                .flat()
                .map((message) => ({ type: 'danger', message }));
        }

        if (payload.mfw_ajax_messages) {
            return MfwActionFeedback.alertsFromMfwMessages(payload.mfw_ajax_messages);
        }

        return [
            {
                type: 'danger',
                message: payload.message ?? error?.message ?? fallbackMessage,
            },
        ];
    }
}

class MfwActionClient {
    static callbacks = {};

    constructor(action, data = {}, options = {}) {
        this.action = action;
        this.data = data;
        this.options = options;
        this.url = this.resolveUrl();
    }

    async execute() {
        const response = await fetch(this.url, {
            method: this.options.method ?? 'POST',
            headers: this.headers(),
            body: this.body(),
            credentials: this.options.credentials ?? 'same-origin',
        });

        const payload = await this.parseResponse(response);

        if (!response.ok) {
            throw new MfwActionError(payload.message ?? response.statusText, response.status, payload, response);
        }

        this.dispatchCallbacks(payload);

        return payload;
    }

    resolveUrl() {
        return this.options.url
            ?? document.querySelector('meta[name="ajax-route"]')?.content
            ?? document.querySelector('[data-ajax]')?.getAttribute('data-ajax')
            ?? window.location.href;
    }

    headers() {
        const headers = new Headers(this.options.headers ?? {});

        headers.set('Accept', 'application/json');
        headers.set('X-Requested-With', 'XMLHttpRequest');

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        if (csrfToken && !headers.has('X-CSRF-TOKEN')) {
            headers.set('X-CSRF-TOKEN', csrfToken);
        }

        if (!(this.data instanceof FormData) && !headers.has('Content-Type')) {
            headers.set('Content-Type', 'application/x-www-form-urlencoded;charset=UTF-8');
        }

        return headers;
    }

    body() {
        if (this.data instanceof FormData) {
            const body = new FormData();

            this.data.forEach((value, key) => {
                body.append(key, value);
            });

            if (!body.has('action')) {
                body.append('action', this.action);
            }

            return body;
        }

        if (this.data instanceof URLSearchParams) {
            const body = new URLSearchParams(this.data);

            if (!body.has('action')) {
                body.set('action', this.action);
            }

            return body;
        }

        if (typeof this.data === 'string') {
            const body = new URLSearchParams(this.data);

            if (!body.has('action')) {
                body.set('action', this.action);
            }

            return body;
        }

        const data = this.data && typeof this.data === 'object' ? this.data : {};

        return new URLSearchParams({
            ...data,
            action: data.action ?? this.action,
        });
    }

    async parseResponse(response) {
        const contentType = response.headers.get('content-type') ?? '';
        const text = await response.text();

        if (contentType.includes('application/json')) {
            return text ? JSON.parse(text) : {};
        }

        return {
            message: text,
        };
    }

    dispatchCallbacks(payload) {
        const callbacks = [];

        if (payload.callback) {
            callbacks.push(payload.callback);
        }

        if (Array.isArray(payload.callbacks)) {
            callbacks.push(...payload.callbacks);
        }

        callbacks.forEach((callback) => {
            if (!callback) {
                return;
            }

            if (typeof window[callback] === 'function') {
                window[callback](payload);
                return;
            }

            if (typeof MfwActionClient.callbacks[callback] === 'function') {
                MfwActionClient.callbacks[callback](payload);
            }

            if (window.MfwAjax?.callbacks && typeof window.MfwAjax.callbacks[callback] === 'function') {
                window.MfwAjax.callbacks[callback](payload);
            }
        });
    }

    static registerCallback(name, handler) {
        if (typeof name !== 'string' || typeof handler !== 'function') {
            return;
        }

        MfwActionClient.callbacks[name] = handler;
    }

    static request(action, data = {}, options = {}) {
        return new MfwActionClient(action, data, options).execute();
    }

    static alertType(type) {
        return MfwActionFeedback.alertType(type);
    }

    static alertsFromMfwMessages(messages) {
        return MfwActionFeedback.alertsFromMfwMessages(messages);
    }

    static alertsFromError(error, fallbackMessage = 'La requête n’a pas pu être traitée.') {
        return MfwActionFeedback.alertsFromError(error, fallbackMessage);
    }
}

function mfwAction(action, data = {}, options = {}) {
    return MfwActionClient.request(action, data, options);
}

window.MfwActionError = MfwActionError;
window.MfwActionFeedback = MfwActionFeedback;
window.MfwActionClient = MfwActionClient;
window.mfwActionFeedback = {
    alertType: MfwActionFeedback.alertType,
    alertsFromMfwMessages: MfwActionFeedback.alertsFromMfwMessages,
    alertsFromError: MfwActionFeedback.alertsFromError,
};
window.mfwAction = mfwAction;
