# MetaFramework Support

[![Tests](https://github.com/aboleon/metaframework-support/actions/workflows/tests.yml/badge.svg)](https://github.com/aboleon/metaframework-support/actions)
[![codecov](https://codecov.io/gh/aboleon/metaframework-support/graph/badge.svg)](https://codecov.io/gh/aboleon/metaframework-support)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/aboleon/metaframework-support.svg?style=flat-square)](https://packagist.org/packages/aboleon/metaframework-support)
[![Total Downloads](https://img.shields.io/packagist/dt/aboleon/metaframework-support.svg?style=flat-square)](https://packagist.org/packages/aboleon/metaframework-support)
[![PHP Version](https://img.shields.io/packagist/php-v/aboleon/metaframework-support.svg?style=flat-square)](https://packagist.org/packages/aboleon/metaframework-support)
[![License](https://img.shields.io/packagist/l/aboleon/metaframework-support.svg?style=flat-square)](https://packagist.org/packages/aboleon/metaframework-support)

A Laravel package providing essential utilities for debugging, AJAX handling, and standardized response management.

## Features

- Enhanced debugging helpers with beautiful output formatting
- AJAX request handling with automatic action dispatching
- Comprehensive response/message management system
- Flash message support
- Blade alert components
- SQL query debugging tools

## Requirements

- PHP ^8.3
- Laravel ^11.0 or ^12.0

## Installation

Install the package via Composer:

```bash
composer require aboleon/metaframework-support
```

The package will automatically register its service provider through Laravel's package discovery.

### Publishing Assets

Publish the JavaScript assets to your public directory:

```bash
php artisan mfw-support:publish
```

Or use Laravel's vendor:publish command:

```bash
php artisan vendor:publish --tag=mfw-support-assets
```

This will copy the `mfw-ajax.js` file to `public/vendor/mfw-support/js/`.

**Include in your layout:**

```blade
{{-- In your layout file (e.g., resources/views/layouts/app.blade.php) --}}
<script src="{{ asset('vendor/mfw-support/js/mfw-ajax.js') }}"></script>
```

**Publish assets with translations:**

```bash
php artisan mfw-support:publish --with-translations
```

**Force overwrite existing files:**

```bash
php artisan mfw-support:publish --force
```

### Publishing Translations

The package includes translations for **English (en)**, **French (fr)**, and **Bulgarian (bg)**.

**Publish all translation files:**

```bash
php artisan mfw-support:publish-translations
```

**Publish specific language(s):**

```bash
php artisan mfw-support:publish-translations --lang=en --lang=fr
```

**Using Laravel's vendor:publish:**

```bash
# Publish only translations
php artisan vendor:publish --tag=mfw-support-translations

# Publish everything (assets + translations)
php artisan vendor:publish --tag=mfw-support
```

**Force overwrite existing translation files:**

```bash
php artisan mfw-support:publish-translations --force
```

Translations will be published to `lang/vendor/mfw-support/{locale}/mfw-support.php`.

## Usage

### Debugging Helpers

The package provides global debugging functions with enhanced formatting:

#### `d($var, $varname = null)`

Pretty-print a variable with styled output:

```php
$user = User::find(1);
d($user, 'User Data');
```

#### `de($var, $varname = null)`

Debug and exit:

```php
de($posts, 'All Posts');
// Script execution stops here
```

#### `dSql($query)`

Debug Eloquent query with bindings:

```php
$query = User::where('active', true)->where('role', 'admin');
dSql($query);
// Outputs: SELECT * FROM users WHERE active = 1 AND role = 'admin'
```

#### `deSql($query)`

Debug SQL query and exit:

```php
deSql($query);
```

### Translations

The package provides built-in translations for common error messages and AJAX responses.

#### Available Languages

- **English (en)**
- **French (fr)**
- **Bulgarian (bg)**

#### Available Translation Keys

```php
// AJAX error messages
__('mfw-support::mfw-support.ajax.request_cannot_be_interpreted')
__('mfw-support::mfw-support.ajax.request_cannot_be_processed')

// Generic error message
__('mfw-support::mfw-support.errors.error')
```

#### Using Translations

The package automatically uses translations based on your application's locale. Set the locale in `config/app.php`:

```php
'locale' => 'fr', // or 'en', 'bg'
```

Or change it dynamically:

```php
app()->setLocale('fr');
```

#### Customizing Translations

After publishing the translation files, you can customize them in:

```
lang/vendor/mfw-support/
├── en/
│   └── mfw-support.php
├── fr/
│   └── mfw-support.php
└── bg/
    └── mfw-support.php
```

**Example customization:**

```php
// lang/vendor/mfw-support/en/mfw-support.php
return [
    'ajax' => [
        'request_cannot_be_interpreted' => 'Invalid request format.',
        'request_cannot_be_processed' => 'Unable to process this request.',
    ],
    'errors' => [
        'error' => 'Something went wrong!',
    ],
];
```

#### Adding New Languages

To add a new language, create a new directory in `lang/vendor/mfw-support/` with the locale code:

```bash
mkdir -p lang/vendor/mfw-support/es
```

Copy an existing translation file and translate:

```bash
cp lang/vendor/mfw-support/en/mfw-support.php lang/vendor/mfw-support/es/mfw-support.php
```

### AJAX Handling

The `Ajax` trait provides powerful AJAX request handling with automatic action dispatching. The trait automatically enables AJAX mode, captures input, and routes requests to specific methods.

#### Setting Up an AJAX Controller

Create a dedicated AJAX controller using the `Ajax` trait:

```php
use MetaFramework\Support\Traits\Ajax;

class AjaxController extends Controller
{
    use Ajax;

    // Each public method can be called via AJAX
    // The distribute() method from the trait handles routing automatically
}
```

#### Method Implementation Patterns

**Pattern 1: Delegating to Action Classes**

```php
public function calculateTotal(): array
{
    return new PriceCalculator()
        ->ajaxMode()
        ->calculate()
        ->fetchResponse();
}

public function removeItem(): array
{
    return new CartController()
        ->ajaxMode()
        ->destroy((int)request('id'))
        ->fetchResponse();
}
```

**Pattern 2: Direct Implementation**

```php
public function updateProfile(): array
{
    $user = User::find(request('user_id'));
    $user->update(request()->only(['name', 'email']));

    $this->responseSuccess('Profile updated successfully');
    $this->responseElement('user', $user);

    return $this->fetchResponse();
}
```

#### Frontend Integration

The `distribute()` method automatically routes requests based on the `action` parameter:

```javascript
// Route setup in web.php or api.php
Route::post('/ajax', [AjaxController::class, 'distribute']);

// Frontend AJAX call
fetch('/ajax', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({
        action: 'updateProfile',  // Maps to updateProfile() method
        user_id: 123,
        name: 'John Doe',
        email: 'john@example.com'
    })
})
.then(response => response.json())
.then(data => {
    if (data.error) {
        // Handle errors
        console.error(data.mfw_ajax_messages);
    } else {
        // Handle success
        console.log('Success:', data.mfw_ajax_messages);
    }
});
```

#### How It Works

1. Frontend sends POST request with `action` parameter
2. `distribute()` validates the action exists as a public method
3. Request is routed to the corresponding method
4. Method returns array response (automatically converted to JSON)
5. AJAX mode is automatically enabled with message handling

### Frontend JavaScript Integration

The package provides a powerful `MfwAjax` JavaScript class and `mfwAjax()` helper function for seamless frontend-backend communication.
This MetaFramework AJAX module handles requests with automatic message display and callback execution.

#### Setup

The AJAX functionality requires jQuery. Make sure you've published the assets (see Installation section above), then include the file in your layout:

```blade
{{-- In your layout file --}}
<script src="{{ asset('vendor/mfw-support/js/mfw-ajax.js') }}"></script>
```

Ensure you have the CSRF token meta tag in your `<head>`:

```blade
<meta name="csrf-token" content="{{ csrf_token() }}">
```

Define the default AJAX route (optional):

```blade
<meta name="ajax-route" content="{{ route('ajax.handle') }}">
```

#### The MfwAjax Class

The `MfwAjax` class handles all AJAX operations with automatic message display and callback execution.

**Requirements:** jQuery (uses `$.ajax`, `$.ajaxSetup`, and jQuery selectors)

**Static Properties:**
- `MfwAjax.dev` (boolean): Enable/disable debug mode (default: `true`)
- `MfwAjax.timerDefault` (number): Default animation timer in ms (default: `200`)

**Static Methods:**
- `MfwAjax.setVeil(container)`: Show loading overlay on a container
- `MfwAjax.removeVeil()`: Remove loading overlay
- `MfwAjax.spinout()`: Fade out and remove spinner elements

#### The mfwAjax() Helper Function

**Signature:**
```javascript
mfwAjax(formData, selector, options)
```

**Parameters:**
- `formData` (string): Serialized data in query string format (`action=method&param=value`)
- `selector` (jQuery): Element used to find `.messages` container and `data-ajax` attribute
- `options` (object): Optional configuration

**Available Options:**
- `spinner` (boolean): Show/hide loading spinner
- `successHandler` (function): Custom success callback (return `false` to suppress messages)
- `errorHandler` (function): Custom error callback (return `false` to suppress messages)
- `keepMessages` (boolean): Keep previous messages instead of clearing them
- `printerOptions` (object):
  - `isDismissable` (boolean): Make alerts dismissable (default: `true`)
- `messagePrinter` (function): Custom message printer function

#### Primary Examples

```javascript
// Basic usage
mfwAjax('action=deleteItem&id=123', $('#my-container'));
```

```javascript
// With callback
mfwAjax('action=updateProfile&name=John&callback=refreshProfile', $('#profile-section'));
```

```javascript
// With custom handlers
mfwAjax('action=saveData', $('#form'), {
    successHandler: function(result) {
        console.log('Success!', result);
        return true; // Show messages
    },
    errorHandler: function(result) {
        console.error('Error!', result);
        return false; // Suppress messages
    }
});
```

#### Secondary Example (Quick Usage)

```javascript
// Simple AJAX call
mfwAjax('action=deleteItem&id=123', $('#container'));

// With callback
mfwAjax('action=updateProfile&name=John&callback=refreshProfile', $('#profile-section'));

function refreshProfile(result) {
    console.log('Profile updated!', result);
    // Update UI
    $('#username').text(result.name);
}
```

#### Using data-ajax Attribute

The `mfwAjax()` function automatically detects the AJAX endpoint from the `data-ajax` attribute:

```blade
<div id="notes" data-ajax="{{ route('ajax.handle') }}">
    {{-- Content --}}
</div>
```

```javascript
// AJAX URL is automatically picked from data-ajax attribute
mfwAjax('action=deleteNote&id=456', $('#notes'));
```

#### Complete Real-World Example

**Blade Template:**
```blade
@extends('layouts.app')

@section('content')
    {{-- Flash messages from session --}}
    <x-mfw::response-messages/>

    <div id="notes-section" data-ajax="{{ route('ajax.handle') }}">
        <div id="note-messages"></div>

        <div id="notes">
            @foreach($notes as $note)
                <div class="note" data-id="{{ $note->id }}">
                    <p>{{ $note->content }}</p>
                    <button class="btn btn-danger delete-note"
                            data-note-id="{{ $note->id }}">
                        Delete
                    </button>
                </div>
            @endforeach
        </div>

        <button id="add-note" class="btn btn-primary">Add Note</button>
    </div>
@endsection

@push('js')
<script>
    function deleteNoteCallback(result) {
        if (!result.error) {
            // Remove the note from DOM
            $('.note[data-id="' + result.input.id + '"]').fadeOut(function() {
                $(this).remove();
            });
        }
    }

    function addNoteCallback(result) {
        if (!result.error) {
            // Prepend new note to the list
            $('#notes').prepend(result.html);
        }
    }

    $(document).ready(function() {
        // Delete note
        $(document).on('click', '.delete-note', function() {
            const noteId = $(this).data('note-id');
            mfwAjax(
                'action=deleteNote&id=' + noteId + '&callback=deleteNoteCallback',
                $('#note-messages')
            );
        });

        // Add note
        $('#add-note').on('click', function() {
            const content = prompt('Enter note content:');
            if (content) {
                mfwAjax(
                    'action=addNote&content=' + encodeURIComponent(content) + '&callback=addNoteCallback',
                    $('#note-messages')
                );
            }
        });
    });
</script>
@endpush
```

**Controller:**
```php
use MetaFramework\Support\Traits\Ajax;

class AjaxController extends Controller
{
    use Ajax;

    public function deleteNote(): array
    {
        $note = Note::findOrFail(request('id'));
        $note->delete();

        $this->responseSuccess('Note deleted successfully');

        return $this->fetchResponse();
    }

    public function addNote(): array
    {
        $note = Note::create([
            'content' => request('content'),
            'user_id' => auth()->id()
        ]);

        $this->responseSuccess('Note added successfully');
        $this->responseElement('html', view('partials.note', compact('note'))->render());

        return $this->fetchResponse();
    }
}
```

#### Advanced Options Example

```javascript
mfwAjax('action=processData&id=123', $('#form'), {
    successHandler: function(result) {
        console.log('Custom success handling', result);
        // Return false to suppress automatic message display
        return false;
    },
    errorHandler: function(result) {
        console.error('Custom error handling', result);
        // Return true to show automatic messages
        return true;
    },
    keepMessages: true, // Don't clear previous messages
    printerOptions: {
        isDismissable: false // Make alerts non-dismissable
    }
});
```

#### Response Structure

The AJAX response from your controller methods includes:

```javascript
{
    "error": false,                    // true if there's an error
    "mfw_ajax_messages": [             // Messages array
        {"success": "Operation successful"},
        {"info": "Additional information"}
    ],
    "callback": "callbackFunctionName", // Auto-captured from request
    "input": {                          // Original request data
        "action": "deleteNote",
        "id": 123
    },
    // ... any custom data you added with responseElement()
    "custom_field": "custom_value"
}
```

#### Helper Methods

**MfwAjax.setVeil(container)** - Show loading overlay:
```javascript
MfwAjax.setVeil($('#my-container'));
```

**MfwAjax.removeVeil()** - Remove loading overlay:
```javascript
MfwAjax.removeVeil();
```

### Response Management

The `Responses` trait provides a powerful system for managing messages and responses:

```php
use MetaFramework\Support\Traits\Responses;

class OrderController extends Controller
{
    use Responses;

    public function store(Request $request)
    {
        try {
            // Create order
            $order = Order::create($request->all());

            $this->responseSuccess('Order created successfully');
            $this->responseElement('order_id', $order->id);

            $this->redirectRoute('orders.show', $order->id);

        } catch (\Exception $e) {
            $this->responseException($e, 'Failed to create order');
        }

        return $this->sendResponse();
    }
}
```

#### Available Response Methods

```php
// Success messages
$this->responseSuccess('Operation completed');

// Error messages
$this->responseError('Something went wrong');

// Warning messages
$this->responseWarning('This action cannot be undone');

// Info messages
$this->responseNotice('FYI: This is informational');

// Debug messages (only visible to developers)
$this->responseDebug($data, 'Debug Info');

// Add custom data to response
$this->responseElement('user', $user);
$this->responseElement('total', 150);

// Exception handling with auto-reporting
$this->responseException($exception, 'Custom error message');
```

#### Flash Messages

```php
// Flash response to session
$this->flashResponse();

// Retrieve in view
@if(session('session_response'))
    {!! MetaFramework\Support\Responses\ResponseMessages::parseResponse(session('session_response')) !!}
@endif
```

#### Response Modes

```php
// Enable AJAX mode
$this->enableAjaxMode();

// Disable messages
$this->disableMessages();

// Keep errors even in debug mode
$this->keepErrors();

// Console logging mode
$this->consoleLog();
```

### Alert Component

Use the Blade alert component to display messages:

```blade
<x-mfw-support::alert
    message="Your changes have been saved"
    type="success"
/>

<x-mfw-support::alert
    message="Please check your input"
    type="danger"
    class="mb-4"
/>
```

Available types: `success`, `danger`, `warning`, `info`

### Response & Validation Components

Use the Blade components to display session responses and validation feedback:

```blade
{{-- Flash/session response messages --}}
<x-mfw-support::response-messages />

{{-- Validation error list --}}
<x-mfw-support::validation-errors />

{{-- Validation banner notice --}}
<x-mfw-support::validation-banner />
```

You can also customize the response messages container:

```blade
<x-mfw-support::response-messages id="custom-messages" ajax="{{ route('ajax.handle') }}" />
```

### Response Message Parsing

Parse and display response arrays automatically:

```php
use MetaFramework\Support\Responses\ResponseMessages;

// In your view
{!! ResponseMessages::parseResponse(session('session_response')) !!}

// Display validation errors
{!! ResponseMessages::validationErrors($errors) !!}

// Individual alert types
{!! ResponseMessages::successNotice('Success!') !!}
{!! ResponseMessages::criticalNotice('Error!') !!}
{!! ResponseMessages::warningNotice('Warning!') !!}
{!! ResponseMessages::infoNotice('Info') !!}
```

## Advanced Examples

### Complete CRUD Controller Example

```php
use MetaFramework\Support\Traits\Responses;

class PostController extends Controller
{
    use Responses;

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|max:255',
            'content' => 'required'
        ]);

        try {
            $post = Post::create($validated);

            $this->responseSuccess('Post created successfully');
            $this->redirectRoute('posts.show', $post->id);

            return $this->sendResponse();

        } catch (\Exception $e) {
            $this->responseException($e);
            $this->redirectRoute('posts.index');

            return $this->sendResponse();
        }
    }

    public function destroy(Post $post)
    {
        if ($post->published) {
            $this->responseWarning('Cannot delete published posts');
            return $this->sendResponse();
        }

        $post->delete();

        $this->responseSuccess('Post deleted successfully');
        $this->redirectRoute('posts.index');

        return $this->sendResponse();
    }
}
```

### AJAX with Callbacks

The Ajax trait automatically captures callback parameters sent from the frontend:

```php
use MetaFramework\Support\Traits\Ajax;

class AjaxController extends Controller
{
    use Ajax;

    public function markAsRead(): array
    {
        $notification = Notification::find(request('id'));
        $notification->markAsRead();

        $this->responseSuccess('Notification marked as read');
        $this->responseElement('unread_count', auth()->user()->unreadNotifications->count());

        // callback is automatically included if sent from frontend
        return $this->fetchResponse();
    }
}
```

```javascript
// Route: Route::post('/ajax', [AjaxController::class, 'distribute']);

fetch('/ajax', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': token
    },
    body: JSON.stringify({
        action: 'markAsRead',
        id: 123,
        callback: 'updateBadge'  // Automatically captured by Ajax trait
    })
})
.then(response => response.json())
.then(data => {
    // Execute callback if provided
    if (data.callback && typeof window[data.callback] === 'function') {
        window[data.callback](data);
    }

    // Or handle messages
    if (data.mfw_ajax_messages) {
        data.mfw_ajax_messages.forEach(msg => {
            Object.entries(msg).forEach(([type, message]) => {
                console.log(`${type}: ${message}`);
            });
        });
    }
});

function updateBadge(data) {
    document.querySelector('.badge').textContent = data.unread_count;
}
```

## License

MIT License

## Support

For issues, questions, or contributions, please visit the [GitHub repository](https://github.com/aboleon/metaframework-support).
