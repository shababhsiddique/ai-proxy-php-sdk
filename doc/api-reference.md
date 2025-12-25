# API Reference

Complete reference documentation for the AI Proxy PHP SDK.

## Client Class

### `AiProxy\Client`

The main client class for interacting with the AI Proxy API.

#### Constructor

```php
public function __construct(
    string $consumerKey,
    string $consumerSecret,
    string $apiBaseUrl = 'https://aiproxyapi-production.up.railway.app',
    string $userAgent = 'AiProxyPhpSdk/1.0'
)
```

Creates a new AI Proxy client instance.

**Parameters:**

- `$consumerKey` (string, required): OAuth consumer key provided by AI Proxy
- `$consumerSecret` (string, required): OAuth consumer secret provided by AI Proxy
- `$apiBaseUrl` (string, optional): Base URL of the AI Proxy API. Defaults to production URL.
- `$userAgent` (string, optional): User-Agent header value. Defaults to `'AiProxyPhpSdk/1.0'`.

**Example:**

```php
$client = new Client(
    'your-consumer-key',
    'your-consumer-secret',
    'https://aiproxyapi-production.up.railway.app',
    'MyApp/1.0'
);
```

---

### `chat()`

Sends a chat request to the AI Proxy API.

```php
public function chat(
    string|array $messages,
    ?string $model = null,
    array $extraPayload = []
): array
```

**Parameters:**

- `$messages` (string|array, required): 
  - **String**: A simple message that will be wrapped as a single user message
  - **Array**: Full messages array with role and content:
    ```php
    [
        ['role' => 'system', 'content' => 'You are a helpful assistant.'],
        ['role' => 'user', 'content' => 'Hello!'],
    ]
    ```

- `$model` (string|null, optional): Model identifier. If `null`, defaults to `'meta-llama/Llama-3.2-3B-Instruct-Turbo'`.

- `$extraPayload` (array, optional): Additional fields to merge into the request payload. Useful for API-specific options.

**Returns:**

- `array`: Decoded JSON response as an associative array.

**Throws:**

- `\RuntimeException`: On HTTP errors, transport errors, or JSON decode failures.

**Examples:**

**Simple string message:**

```php
$response = $client->chat('What is PHP?');
```

**Full messages array:**

```php
$messages = [
    ['role' => 'system', 'content' => 'You are a coding assistant.'],
    ['role' => 'user', 'content' => 'Explain OAuth 1.0'],
];

$response = $client->chat($messages);
```

**Custom model:**

```php
$response = $client->chat(
    'Hello!',
    'gpt-4'
);
```

**With extra payload:**

```php
$response = $client->chat(
    'Test message',
    null,
    ['test' => true, 'temperature' => 0.7]
);
```

---

## Request/Response Format

### Request Payload

The SDK automatically constructs the request payload:

```json
{
    "model": "meta-llama/Llama-3.2-3B-Instruct-Turbo",
    "messages": [
        {
            "role": "user",
            "content": "Your message here"
        }
    ]
}
```

Additional fields from `$extraPayload` are merged into this structure.

### Response Format

The response is returned as a PHP associative array, decoded from the JSON response:

```php
[
    'id' => 'chatcmpl-...',
    'object' => 'chat.completion',
    'created' => 1234567890,
    'choices' => [
        [
            'index' => 0,
            'message' => [
                'role' => 'assistant',
                'content' => 'Response text here'
            ],
            'finish_reason' => 'stop'
        ]
    ],
    'usage' => [
        'prompt_tokens' => 10,
        'completion_tokens' => 20,
        'total_tokens' => 30
    ]
]
```

The exact structure depends on the AI Proxy API response format.

---

## HTTP Headers

The SDK automatically sets the following headers:

- `Authorization`: OAuth 1.0 authorization header
- `Accept`: `application/json`
- `Content-Type`: `application/json`
- `User-Agent`: Customizable via constructor (default: `AiProxyPhpSdk/1.0`)
- `X-Payload-Signature`: SHA-256 hash of the payload signature

See [Authentication](authentication.md) for details on how these headers are constructed.

