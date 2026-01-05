/**
 * MetaFramework AJAX Module
 * Handles AJAX requests with automatic message display and callback execution
 */

let dev = true,
    timerDefault = function () {
        return 200;
    },
    timer = timerDefault(),
    spinout = function () {
        setTimeout(function () {
            $('.spinner').fadeOut(function () {
                $(this).remove();
            });
        }, timer + timerDefault());
    },
    notificationQueue = function (messages) {
        return messages.find(' > div').length;
    },
    dismissable = function () {
        $('.alert-dismissible button').off().on('click', function () {
            $(this).parent().remove();
        });
    },
    alertDispatcher = function (message, messages, messageType, isDismissable) {
        const alertHtml = isDismissable
            ? '<div style="opacity: 0; transition: opacity 0.5s;" class="alert alert-dismissible alert-' + messageType + '">' +
            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
            message + '</div>'
            : '<div style="opacity: 0; transition: opacity 0.5s;" class="alert alert-' + messageType + '">' + message + '</div>';

        // Append the message to the container
        const $newMessage = $(alertHtml).appendTo(messages);

        // Use a timeout to apply fade-in effect via CSS transition
        const currentTimer = timerDefault() * (notificationQueue(messages));
        setTimeout(() => {
            $newMessage.css('opacity', 1); // Triggers the CSS transition
        }, currentTimer);
    },
    notificator = function (status, data, messages, keepMessages, printerOptions) {
        const isDismissable = printerOptions.isDismissable ?? true;
        const $data = $(data);

        if (!keepMessages) {
            messages.empty(); // Clear all previous messages
        }

        switch (status) {
            case 422: // Laravel JSON Validator Messages
                if (data.responseJSON?.errors) {
                    $.each(data.responseJSON.errors, function (key, errorMessages) {
                        alertDispatcher(errorMessages[0], messages, 'danger', isDismissable);
                    });
                }
                break;

            default:
                if (!$data.length) return false; // Exit if no data

                // Append and process each message sequentially
                $data.each(function (index, message) {
                    $.each(message, function (key, value) {
                        alertDispatcher(value, messages, key, isDismissable);
                    });
                });
        }

        dismissable(); // Reapply dismissible behavior
    },
    setVeil = function (c) {
        c.prepend('<div class="veil" style="border-radius:25px"><img class="loading" src="/system/loading.svg" width="40" alt="..."></div>');
    },
    removeVeil = function () {
        $('.veil').remove();
    },
    /**
     * AJAX Helper Function
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
     * ajax('action=deleteItem&id=123', $('#my-container'));
     *
     * @example
     * // With callback
     * ajax('action=updateProfile&name=John&callback=refreshProfile', $('#profile-section'));
     *
     * @example
     * // With custom handlers
     * ajax('action=saveData', $('#form'), {
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
    ajax = function (formData, selector, options = {}) {
        let ajax_url = document.querySelector('meta[name="ajax-route"]').content ?? null,
            ajax_url_origin,
            formTag = selector.closest('.form');

        let spinner = options.spinner ?? null;
        if (spinner) {
            spinner = selector.find('.ajax-spinner');
            if (!spinner.length) {
                spinner = null;
            }
        }

        let successHandler = options.successHandler ?? null;
        let errorHandler = options.errorHandler ?? null;
        let printerOptions = options.printerOptions ?? false;

        if (selector[0].hasAttribute('data-ajax')) {
            ajax_url = selector.attr('data-ajax');
            ajax_url_origin = 'selector data-ajax';
        } else if (formTag.length) {
            if (formTag[0].hasAttribute('data-ajax')) {
                ajax_url = formTag.attr('data-ajax');
            }
        }

        dev ? console.log('Ajax started on ' + ajax_url + ' with origin ' + ajax_url_origin) : null;

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            },
            url: ajax_url,
            type: 'POST',
            dataType: 'json',
        });

        selector = (typeof selector == 'undefined' ? $(this).closest('.form') : selector);
        selector.find('.messages').length < 1 ? selector.append('<div class="messages"></div>') : '';

        let messages = selector.find('.messages'), keepMessages = options.keepMessages ?? false;

        if (spinner) {
            $(spinner).show();
        }

        let messagePrinter = options.messagePrinter ?? function (status, ajax_messages, messages, keepMessages, printerOptions) {
            return ajax_messages.length > 0 ? notificator(status, ajax_messages, messages, keepMessages, printerOptions) : null;
        };

        $.ajax({
            data: formData,
            done: function () {
                messages.html('');
            },
            success: function (result) {

                let hasError = result.error || false;
                let showMessages = true;

                if (hasError && errorHandler) {
                    showMessages = errorHandler(result);
                } else if (!hasError && successHandler) {
                    showMessages = successHandler(result);
                }

                if (showMessages && result.hasOwnProperty('mfw_ajax_messages')) {
                    messagePrinter(200, result.mfw_ajax_messages, messages, keepMessages, printerOptions);
                }

                // Handle console logging for messages_log
                ['mfw_ajax_messages_log', 'mfw_messages_log'].forEach(function(logKey) {
                    if (result.hasOwnProperty(logKey)) {
                        console.log(logKey + ':', result[logKey]);
                        result[logKey].forEach(function(logEntry) {
                            Object.keys(logEntry).forEach(function(logType) {
                                console.log('MFW LOG [' + logType.toUpperCase() + ']', logEntry[logType]);
                            });
                        });
                    }
                });
            },
            error: function (xhr) {
                dev ? console.log(xhr) : null;
                notificator(xhr.status, xhr, messages, keepMessages, printerOptions);
            },

        }).always(function (result) {

            let callback = result.hasOwnProperty('callback') ? result.callback : false;
            dev ? console.log(result, 'Result') : null;
            typeof window[callback] === 'function' ? window[callback](result) : null;
            console.log(callback, typeof window[callback] === 'function');

            if (spinner) {
                $(spinner).hide();
            }
            spinout();
        });
    };
