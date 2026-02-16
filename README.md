## Bitmesh PHP SDK

PHP SDK for calling the Bitmesh AI `/chat` API using OAuth 1.0 authentication.

### Installation

Install via Composer:

```bash
composer require bitmeshai/bitmesh-php-sdk
```

Or, if you are developing locally in this repo:

```bash
composer install
```

### Basic Usage

```php
<?php

require 'vendor/autoload.php';

use BitmeshAI\BitmeshClient;

$consumerKey = 'YOUR_CONSUMER_KEY';
$consumerSecret = 'YOUR_CONSUMER_SECRET';

// Production client (default base URL)
$client = new BitmeshClient($consumerKey, $consumerSecret);

// Simple one-shot chat with a string prompt
$response = $client->chat('What are some fun things to do with AI?');

print_r($response);
```

### Using a Local / Dev Server

If you are running the Bitmesh AI API locally (for example at `http://localhost:8003`), pass the base URL explicitly:

```php
$client = new BitmeshClient(
    $consumerKey,
    $consumerSecret,
    'http://localhost:8003'
);
```

The SDK will call `POST http://localhost:8003/chat`.

### Advanced `chat()` Options

The `chat()` method signature is:

```php
public function chat(
    string|array $messages,
    ?string $model = null,
    array $extraPayload = []
): array
```

- **messages**
  - `string`: convenience form; wrapped as a single `user` message:
    `[['role' => 'user', 'content' => $messages]]`
  - `array`: full messages array, e.g.:

    ```php
    $messages = [
        ['role' => 'system', 'content' => 'You are a helpful assistant.'],
        ['role' => 'user', 'content' => 'What are some fun things to do with AI?'],
    ];

    $response = $client->chat($messages);
    ```

- **model**
  - Optional model id string.
  - Defaults to `meta-llama/Llama-3.2-3B-Instruct-Turbo` if not provided.

- **extraPayload**
  - Extra JSON fields to merge into the request payload, for example:

    ```php
    $response = $client->chat(
        'Test request',
        null,
        [
            'test' => true,
        ]
    );
    ```

### What the SDK Handles

- **OAuth 1.0 signing** using your consumer key and secret.
- **Authorization header** construction (matching your standalone sample script).
- **`X-Payload-Signature` header**:
  - `hash('sha256', $jsonBody . $consumerKey . $oauth_signature)`.
- **HTTP POST** to `/chat` with a JSON body using `curl`.
- **JSON decoding & error handling**:
  - Throws `RuntimeException` on HTTP errors, transport errors, or invalid JSON.

### Testing

This repo includes a basic PHPUnit test for the `BitmeshClient::chat()` method.

Tests use mock requests. Credentials are configured in `phpunit.xml.dist` via `BITMESH_TEST_CONSUMER_KEY` and `BITMESH_TEST_CONSUMER_SECRET`. Copy to `phpunit.xml` and customize if needed.

Run all tests:

```bash
./vendor/bin/phpunit
```

Or run a single test file:

```bash
./vendor/bin/phpunit tests/BitmeshClientChatTest.php
```


