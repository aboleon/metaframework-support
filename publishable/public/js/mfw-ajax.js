class MfwAjax {
    static dev = true;
    static timerDefault = 200;
    static callbacks = {};

    constructor(formData, selector, options = {}) {
        this.formData = formData;
        this.selector = selector && selector.jquery ? selector : $(selector ?? []);
        this.options = options;

        this.ajaxUrl = document.querySelector('meta[name="ajax-route"]')?.content ?? null;
        this.ajaxUrlOrigin = 'meta tag';
        this.formTag = this.selector.length ? this.selector.closest('.form') : $();
        this.messages = null;
        this.spinner = null;
        this.lockForm = this.options.lockForm ?? false;
        this.scrollToMessages = this.options.scrollToMessages ?? false;
        this.lockedElements = $();

        this.init();
    }

    init() {
        this.resolveAjaxUrl();
        this.resolveSpinner();
        this.ensureMessagesContainer();
        this.execute();
    }

    resolveAjaxUrl() {
        if (!this.selector || this.selector.length < 1) {
            return;
        }

        if (this.selector[0].hasAttribute('data-ajax')) {
            this.ajaxUrl = this.selector.attr('data-ajax');
            this.ajaxUrlOrigin = 'selector data-ajax';
        } else if (this.formTag.length && this.formTag[0].hasAttribute('data-ajax')) {
            this.ajaxUrl = this.formTag.attr('data-ajax');
            this.ajaxUrlOrigin = 'form data-ajax';
        }
    }

    resolveSpinner() {
        if (this.options.spinner) {
            const spinner = this.selector.find('.ajax-spinner');
            this.spinner = spinner.length ? spinner : null;
        }
    }

    ensureMessagesContainer() {
        if (this.selector.find('.messages').length < 1) {
            this.selector.append('<div class="messages"></div>');
        }
        this.messages = this.selector.find('.messages');
    }

    execute() {
        const self = this;
        const keepMessages = this.options.keepMessages ?? false;
        const printerOptions = this.options.printerOptions ?? {};
        const successHandler = this.options.successHandler ?? null;
        const errorHandler = this.options.errorHandler ?? null;

        const messagePrinter = this.options.messagePrinter ?? function (status, ajaxMessages, messages, keepMessages, printerOptions) {
            return ajaxMessages.length > 0 ? MfwAjax.notificator(status, ajaxMessages, messages, keepMessages, printerOptions) : null;
        };

        if (MfwAjax.dev) {
            console.log('Ajax started on ' + this.ajaxUrl + ' with origin ' + this.ajaxUrlOrigin);
        }

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            },
            url: this.ajaxUrl,
            type: 'POST',
            dataType: 'json',
        });

        if (this.lockForm) {
            this.lockFormElements();
        }

        if (this.spinner) {
            this.spinner.show();
        }

        $.ajax({
            data: this.formData,
            done: function () {
                self.messages.html('');
            },
            success: function (result) {
                const hasError = result.error || false;
                let showMessages = true;

                if (hasError && errorHandler) {
                    showMessages = errorHandler(result);
                } else if (!hasError && successHandler) {
                    showMessages = successHandler(result);
                }

                if (showMessages && result.hasOwnProperty('mfw_ajax_messages')) {
                    messagePrinter(200, result.mfw_ajax_messages, self.messages, keepMessages, printerOptions);
                    if (self.scrollToMessages) {
                        self.scrollToMessageContainer();
                    }
                }

                ['mfw_ajax_messages_log', 'mfw_messages_log'].forEach(function (logKey) {
                    if (result.hasOwnProperty(logKey)) {
                        console.log(logKey + ':', result[logKey]);
                        result[logKey].forEach(function (logEntry) {
                            Object.keys(logEntry).forEach(function (logType) {
                                console.log('MFW LOG [' + logType.toUpperCase() + ']', logEntry[logType]);
                            });
                        });
                    }
                });
            },
            error: function (xhr) {
                if (MfwAjax.dev) {
                    console.log(xhr);
                }
                MfwAjax.notificator(xhr.status, xhr, self.messages, keepMessages, printerOptions);
            },
        }).always(function (result) {
            const callbacks = [];
            if (result.hasOwnProperty('callback')) {
                callbacks.push(result.callback);
            }
            if (Array.isArray(result.callbacks)) {
                callbacks.push(...result.callbacks);
            }

            if (MfwAjax.dev) {
                console.log(result, 'Result');
            }

            callbacks.forEach(function (callback) {
                if (!callback) {
                    return;
                }
                if (typeof window[callback] === 'function') {
                    window[callback](result);
                    return;
                }
                if (typeof MfwAjax.callbacks[callback] === 'function') {
                    MfwAjax.callbacks[callback](result);
                }
            });

            if (self.spinner) {
                self.spinner.hide();
            }
            if (self.lockForm) {
                self.unlockFormElements();
            }
            MfwAjax.spinout();
        });
    }

    // Static utility methods

    static spinout() {
        setTimeout(function () {
            $('.spinner').fadeOut(function () {
                $(this).remove();
            });
        }, MfwAjax.timerDefault * 2);
    }

    static notificationQueue(messages) {
        return messages.find(' > div').length;
    }

    static dismissable() {
        $('.alert-dismissible button').off().on('click', function () {
            $(this).parent().remove();
        });
    }

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
            type: MfwAjax.alertType(type),
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
            return MfwAjax.alertsFromMfwMessages(payload.mfw_ajax_messages);
        }

        return [
            {
                type: 'danger',
                message: payload.message ?? error?.message ?? fallbackMessage,
            },
        ];
    }

    static alertDispatcher(message, messages, messageType, isDismissable) {
        const alertHtml = isDismissable
            ? '<div style="opacity: 0; transition: opacity 0.5s;" class="alert alert-dismissible alert-' + messageType + '">' +
              '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
              message + '</div>'
            : '<div style="opacity: 0; transition: opacity 0.5s;" class="alert alert-' + messageType + '">' + message + '</div>';

        const $newMessage = $(alertHtml).appendTo(messages);
        const currentTimer = MfwAjax.timerDefault * MfwAjax.notificationQueue(messages);

        setTimeout(() => {
            $newMessage.css('opacity', 1);
        }, currentTimer);
    }

    static notificator(status, data, messages, keepMessages, printerOptions) {
        const isDismissable = printerOptions.isDismissable ?? true;
        const $data = $(data);

        if (!keepMessages) {
            messages.empty();
        }

        switch (status) {
            case 422:
                if (data.responseJSON?.errors) {
                    $.each(data.responseJSON.errors, function (key, errorMessages) {
                        MfwAjax.alertDispatcher(errorMessages[0], messages, 'danger', isDismissable);
                    });
                }
                break;

            default:
                if (!$data.length) return false;

                $data.each(function (index, message) {
                    $.each(message, function (key, value) {
                        MfwAjax.alertDispatcher(value, messages, MfwAjax.alertType(key), isDismissable);
                    });
                });
        }

        MfwAjax.dismissable();
    }

    static setVeil(container) {
        container.prepend('<div class="veil" style="border-radius:25px"><img class="loading" src="/system/loading.svg" width="40" alt="..."></div>');
    }

    static removeVeil() {
        $('.veil').remove();
    }

    static registerCallback(name, handler) {
        if (typeof name !== 'string' || typeof handler !== 'function') {
            return;
        }

        MfwAjax.callbacks[name] = handler;
    }

    lockFormElements() {
        if (!this.formTag.length) {
            return;
        }

        this.lockedElements = this.formTag
            .find('input, select, textarea, button')
            .filter(':enabled');

        this.lockedElements.prop('disabled', true).addClass('mfw-ajax-locked');
    }

    unlockFormElements() {
        if (!this.lockedElements.length) {
            return;
        }

        this.lockedElements.prop('disabled', false).removeClass('mfw-ajax-locked');
        this.lockedElements = $();
    }

    scrollToMessageContainer() {
        if (!this.messages || !this.messages.length) {
            return;
        }

        const top = this.messages.offset()?.top;
        if (typeof top === 'number') {
            $('html, body').animate({ scrollTop: top - 120 }, 200);
        }
    }
}

function mfwAjax(formData, selector, options = {}) {
    return new MfwAjax(formData, selector, options);
}

window.MfwAjax = MfwAjax;
window.mfwAjax = mfwAjax;
