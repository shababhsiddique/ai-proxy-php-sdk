## Bitmesh PHP SDK

PHP SDK for calling the Bitmesh AI API (chat, image, video) using OAuth 1.0 authentication. Browse [all available models](https://bitmesh.ai/models) on Bitmesh.

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

$client = new BitmeshClient($consumerKey, $consumerSecret);

// Simple one-shot chat: pass a string; the SDK wraps it as a "user" message
$response = $client->chat('What are some fun things to do with AI?');

// Response shape depends on provider; typically has 'choices' and optionally 'usage'
echo $response['choices'][0]['message']['content'] ?? json_encode($response);
```

With an explicit model and options (e.g. `max_tokens`, `temperature`):

```php
$response = $client->chat(
    'Explain quantum computing in one sentence.',
    'meta-llama/Llama-3.2-3B-Instruct-Turbo',
    [
        'max_tokens' => 150,
        'temperature' => 0.7,
    ]
);
```

### Chat with previous chat history

Pass a full `messages` array to keep context (e.g. system prompt + prior user/assistant turns). Each item must have `role` and `content`.

```php
$messages = [
    ['role' => 'system', 'content' => 'You are a helpful assistant that answers briefly.'],
    ['role' => 'user', 'content' => 'What is the capital of France?'],
    ['role' => 'assistant', 'content' => 'The capital of France is Paris.'],
    ['role' => 'user', 'content' => 'What is one famous landmark there?'],
];

$response = $client->chat($messages);

$reply = $response['choices'][0]['message']['content'] ?? '';
echo $reply;
```

More examples (image, video, video status) are in [doc/code-examples.md](doc/code-examples.md). Full API details are in [doc/api-reference.md](doc/api-reference.md).

### Using a Local / Dev Server

If you are running the Bitmesh AI API locally (for example at `http://localhost:8003`), pass the base URL explicitly:

```php
$client = new BitmeshClient(
    $consumerKey,
    $consumerSecret,
    'http://localhost:8003'
);
```

The SDK will call `POST http://localhost:8003/chat` (and same base for `/image`, `/video`, etc.).

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


