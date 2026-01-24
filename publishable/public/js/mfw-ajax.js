/**
 * MetaFramework AJAX Module
 * Handles AJAX requests with automatic message display and callback execution
 */

class MfwAjax {
    static dev = true;
    static timerDefault = 200;

    constructor(formData, selector, options = {}) {
        this.formData = formData;
        this.selector = selector && selector.jquery ? selector : $(selector ?? []);
        this.options = options;

        this.ajaxUrl = document.querySelector('meta[name="ajax-route"]')?.content ?? null;
        this.ajaxUrlOrigin = 'meta tag';
        this.formTag = this.selector.length ? this.selector.closest('.form') : $();
        this.messages = null;
        this.spinner = null;

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
            const callback = result.hasOwnProperty('callback') ? result.callback : false;

            if (MfwAjax.dev) {
                console.log(result, 'Result');
            }

            if (typeof window[callback] === 'function') {
                window[callback](result);
            }
            console.log(callback, typeof window[callback] === 'function');

            if (self.spinner) {
                self.spinner.hide();
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
                        MfwAjax.alertDispatcher(value, messages, key, isDismissable);
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
}

/**
 * Simple AJAX function wrapper
 *
 * @param {string} formData - Serialized form data (query string format: "action=method&param=value")
 * @param {jQuery} selector - jQuery element (used to find .messages container and data-ajax attribute)
 * @param {object} options - Optional configuration
 * @param {boolean} options.spinner - Show/hide loading spinner
 * @param {function} options.successHandler - Custom success callback (return false to suppress messages)
 * @param {function} options.errorHandler - Custom error callback (return false to suppress messages)
 * @param {boolean} options.keepMessages - Keep previous messages (default: false)
 * @param {object} options.printerOptions - Message printer options
 * @param {boolean} options.printerOptions.isDismissable - Make alerts dismissable (default: true)
 * @param {function} options.messagePrinter - Custom message printer function
 *
 * @example
 * // Basic usage
 * mfwAjax('action=deleteItem&id=123', $('#my-container'));
 *
 * @example
 * // With callback
 * mfwAjax('action=updateProfile&name=John&callback=refreshProfile', $('#profile-section'));
 *
 * @example
 * // With custom handlers
 * mfwAjax('action=saveData', $('#form'), {
 *     successHandler: function(result) {
 *         console.log('Success!', result);
 *         return true; // Show messages
 *     },
 *     errorHandler: function(result) {
 *         console.error('Error!', result);
 *         return false; // Suppress messages
 *     }
 * });
 */
function mfwAjax(formData, selector, options = {}) {
    return new MfwAjax(formData, selector, options);
}
