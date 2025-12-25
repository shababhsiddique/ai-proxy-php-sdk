# Examples

Practical examples demonstrating various use cases of the AI Proxy PHP SDK.

## Basic Examples

### Simple Chat Request

The simplest way to use the SDK:

```php
<?php

require 'vendor/autoload.php';

use AiProxy\Client;

$client = new Client(
    'YOUR_CONSUMER_KEY',
    'YOUR_CONSUMER_SECRET'
);

$response = $client->chat('What is artificial intelligence?');

echo $response['choices'][0]['message']['content'] ?? 'No response';
```

### Using System Messages

Create a conversation with a system message to set the assistant's behavior:

```php
$messages = [
    ['role' => 'system', 'content' => 'You are a helpful coding assistant.'],
    ['role' => 'user', 'content' => 'How do I implement OAuth in PHP?'],
];

$response = $client->chat($messages);
```

### Multi-Turn Conversation

Build a conversation with multiple exchanges:

```php
$messages = [
    ['role' => 'user', 'content' => 'What is PHP?'],
];

$response = $client->chat($messages);
$assistantReply = $response['choices'][0]['message']['content'];

// Continue the conversation
$messages[] = ['role' => 'assistant', 'content' => $assistantReply];
$messages[] = ['role' => 'user', 'content' => 'Can you give me an example?'];

$response = $client->chat($messages);
```

## Advanced Examples

### Custom Model Selection

Specify a different model:

```php
$response = $client->chat(
    'Explain quantum computing',
    'gpt-4'  // Custom model
);
```

### Using Extra Payload Options

Pass additional parameters to the API:

```php
$response = $client->chat(
    'Generate creative writing',
    null,
    [
        'temperature' => 0.9,
        'max_tokens' => 500,
        'test' => true,  // If your API supports this
    ]
);
```

### Local Development Setup

Use the SDK with a local development server:

```php
$client = new Client(
    'YOUR_CONSUMER_KEY',
    'YOUR_CONSUMER_SECRET',
    'http://localhost:8003'  // Local API server
);

$response = $client->chat('Test message');
```

## Error Handling Examples

### Basic Error Handling

Handle errors gracefully:

```php
try {
    $response = $client->chat('Hello!');
    echo $response['choices'][0]['message']['content'];
} catch (\RuntimeException $e) {
    echo "Error: " . $e->getMessage();
}
```

### Detailed Error Handling

Handle different types of errors:

```php
try {
    $response = $client->chat('Hello!');
    // Process response
} catch (\RuntimeException $e) {
    $message = $e->getMessage();
    
    if (strpos($message, 'HTTP 401') !== false) {
        // Authentication error
        error_log('Authentication failed. Check your credentials.');
    } elseif (strpos($message, 'HTTP 4') !== false) {
        // Client error (400-499)
        error_log('Client error: ' . $message);
    } elseif (strpos($message, 'HTTP 5') !== false) {
        // Server error (500-599)
        error_log('Server error: ' . $message);
    } elseif (strpos($message, 'Curl error') !== false) {
        // Network/transport error
        error_log('Network error: ' . $message);
    } else {
        // Other errors
        error_log('Unexpected error: ' . $message);
    }
    
    throw $e;  // Re-throw or handle as needed
}
```

## Integration Examples

### Laravel Integration

Use the SDK in a Laravel controller:

```php
<?php

namespace App\Http\Controllers;

use AiProxy\Client;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client(
            config('services.ai_proxy.consumer_key'),
            config('services.ai_proxy.consumer_secret')
        );
    }

    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'model' => 'nullable|string',
        ]);

        try {
            $response = $this->client->chat(
                $request->input('message'),
                $request->input('model')
            );

            return response()->json($response);
        } catch (\RuntimeException $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
```

Add to `config/services.php`:

```php
'ai_proxy' => [
    'consumer_key' => env('AI_PROXY_CONSUMER_KEY'),
    'consumer_secret' => env('AI_PROXY_CONSUMER_SECRET'),
],
```

### Symfony Integration

Use the SDK in a Symfony service:

```php
<?php

namespace App\Service;

use AiProxy\Client;

class AiProxyService
{
    private Client $client;

    public function __construct(
        string $consumerKey,
        string $consumerSecret
    ) {
        $this->client = new Client($consumerKey, $consumerSecret);
    }

    public function sendMessage(string $message, ?string $model = null): array
    {
        return $this->client->chat($message, $model);
    }
}
```

Configure in `config/services.yaml`:

```yaml
services:
    App\Service\AiProxyService:
        arguments:
            $consumerKey: '%env(AI_PROXY_CONSUMER_KEY)%'
            $consumerSecret: '%env(AI_PROXY_CONSUMER_SECRET)%'
```

### WordPress Plugin Integration

Use the SDK in a WordPress plugin:

```php
<?php

require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

use AiProxy\Client;

class AiProxyPlugin
{
    private Client $client;

    public function __construct()
    {
        $consumerKey = get_option('ai_proxy_consumer_key');
        $consumerSecret = get_option('ai_proxy_consumer_secret');
        
        $this->client = new Client($consumerKey, $consumerSecret);
    }

    public function handleChatRequest($message)
    {
        try {
            $response = $this->client->chat($message);
            return $response['choices'][0]['message']['content'] ?? '';
        } catch (\RuntimeException $e) {
            error_log('AI Proxy error: ' . $e->getMessage());
            return 'Sorry, an error occurred.';
        }
    }
}
```

## Best Practices

### Reuse Client Instances

Create the client once and reuse it:

```php
// Good: Create once
$client = new Client($key, $secret);

// Reuse for multiple requests
$response1 = $client->chat('First message');
$response2 = $client->chat('Second message');
```

### Handle Timeouts

For long-running requests, consider implementing timeout handling:

```php
// Note: The SDK uses curl internally, which has default timeouts
// For custom timeout handling, you may need to extend the Client class
```

### Logging

Implement logging for debugging:

```php
try {
    $response = $client->chat($message);
    error_log('AI Proxy success: ' . json_encode($response));
    return $response;
} catch (\RuntimeException $e) {
    error_log('AI Proxy error: ' . $e->getMessage());
    throw $e;
}
```

