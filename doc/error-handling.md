# Error Handling

Comprehensive guide to handling errors and exceptions in the AI Proxy PHP SDK.

## Exception Types

The SDK throws `\RuntimeException` for all error conditions. The exception message provides details about what went wrong.

## Common Error Scenarios

### HTTP Errors

When the API returns a non-200 status code:

```php
try {
    $response = $client->chat('Hello!');
} catch (\RuntimeException $e) {
    // Exception message format: "AI Proxy API returned HTTP {code}"
    // For 401: "AI Proxy API returned HTTP 401"
    // For 500: "AI Proxy API returned HTTP 500"
}
```

**Common HTTP Status Codes:**

- **401 Unauthorized**: Invalid or missing credentials
- **400 Bad Request**: Invalid request payload
- **404 Not Found**: Endpoint not found (check base URL)
- **500 Internal Server Error**: Server-side error
- **503 Service Unavailable**: Service temporarily unavailable

### Network/Transport Errors

When there's a network issue or the server is unreachable:

```php
try {
    $response = $client->chat('Hello!');
} catch (\RuntimeException $e) {
    if (strpos($e->getMessage(), 'Curl error') !== false) {
        // Network error occurred
        // Common causes:
        // - Server unreachable
        // - DNS resolution failure
        // - Connection timeout
        // - SSL certificate issues
    }
}
```

### JSON Decode Errors

When the response cannot be decoded as JSON:

```php
try {
    $response = $client->chat('Hello!');
} catch (\RuntimeException $e) {
    if (strpos($e->getMessage(), 'Failed to decode') !== false) {
        // Response was not valid JSON
        // The raw body is included in the exception message
    }
}
```

### Payload Encoding Errors

When the request payload cannot be encoded as JSON (rare):

```php
try {
    $response = $client->chat('Hello!');
} catch (\RuntimeException $e) {
    if (strpos($e->getMessage(), 'Failed to encode') !== false) {
        // Payload encoding failed
        // This typically indicates invalid data in $extraPayload
    }
}
```

## Error Handling Patterns

### Pattern 1: Simple Try-Catch

Basic error handling for most use cases:

```php
try {
    $response = $client->chat($message);
    // Process successful response
    return $response;
} catch (\RuntimeException $e) {
    // Log error
    error_log('AI Proxy error: ' . $e->getMessage());
    
    // Return default or re-throw
    return ['error' => 'Failed to get response'];
}
```

### Pattern 2: Categorized Error Handling

Handle different error types differently:

```php
function handleChatRequest(Client $client, string $message): array
{
    try {
        return $client->chat($message);
    } catch (\RuntimeException $e) {
        $errorMessage = $e->getMessage();
        
        // Authentication errors
        if (preg_match('/HTTP 401/', $errorMessage)) {
            throw new AuthenticationException('Invalid credentials', 0, $e);
        }
        
        // Client errors (400-499)
        if (preg_match('/HTTP 4\d{2}/', $errorMessage)) {
            throw new ClientException('Invalid request', 0, $e);
        }
        
        // Server errors (500-599)
        if (preg_match('/HTTP 5\d{2}/', $errorMessage)) {
            throw new ServerException('Server error', 0, $e);
        }
        
        // Network errors
        if (strpos($errorMessage, 'Curl error') !== false) {
            throw new NetworkException('Network error', 0, $e);
        }
        
        // Unknown errors
        throw new \RuntimeException('Unexpected error: ' . $errorMessage, 0, $e);
    }
}
```

### Pattern 3: Retry Logic

Implement retry logic for transient errors:

```php
function chatWithRetry(
    Client $client,
    string $message,
    int $maxRetries = 3,
    int $delaySeconds = 1
): array {
    $attempt = 0;
    
    while ($attempt < $maxRetries) {
        try {
            return $client->chat($message);
        } catch (\RuntimeException $e) {
            $attempt++;
            
            // Don't retry on client errors (4xx)
            if (preg_match('/HTTP 4\d{2}/', $e->getMessage())) {
                throw $e;
            }
            
            // Don't retry on authentication errors
            if (preg_match('/HTTP 401/', $e->getMessage())) {
                throw $e;
            }
            
            // Retry on server errors (5xx) and network errors
            if ($attempt < $maxRetries) {
                sleep($delaySeconds * $attempt); // Exponential backoff
                continue;
            }
            
            throw $e;
        }
    }
    
    throw new \RuntimeException('Max retries exceeded');
}
```

### Pattern 4: Error Logging with Context

Log errors with full context:

```php
function chatWithLogging(Client $client, string $message): array
{
    $startTime = microtime(true);
    
    try {
        $response = $client->chat($message);
        
        $duration = microtime(true) - $startTime;
        error_log(sprintf(
            'AI Proxy success: message="%s", duration=%.2fs',
            substr($message, 0, 100),
            $duration
        ));
        
        return $response;
    } catch (\RuntimeException $e) {
        $duration = microtime(true) - $startTime;
        
        error_log(sprintf(
            'AI Proxy error: message="%s", error="%s", duration=%.2fs',
            substr($message, 0, 100),
            $e->getMessage(),
            $duration
        ));
        
        throw $e;
    }
}
```

## Troubleshooting Common Issues

### 401 Unauthorized

**Symptoms:**
- Exception: "AI Proxy API returned HTTP 401"

**Solutions:**
1. Verify consumer key and secret are correct
2. Check that credentials are active in the AI Proxy dashboard
3. Ensure server time is synchronized (OAuth timestamps are time-sensitive)
4. Verify you're using the correct environment (production vs. development)

### Connection Timeout

**Symptoms:**
- Exception: "Curl error: Operation timed out"

**Solutions:**
1. Check network connectivity
2. Verify the API server is accessible
3. Check firewall settings
4. For local development, ensure the server is running

### Invalid JSON Response

**Symptoms:**
- Exception: "Failed to decode AI Proxy response JSON"

**Solutions:**
1. Check the raw response body in the exception message
2. Verify the API endpoint is correct
3. Check if the API is returning an error page (HTML instead of JSON)
4. Ensure the API server is functioning correctly

### SSL Certificate Errors

**Symptoms:**
- Exception: "Curl error: SSL certificate problem"

**Solutions:**
1. Update CA certificates: `sudo update-ca-certificates` (Linux)
2. For development, you can temporarily disable SSL verification (not recommended for production)
3. Verify the API server's SSL certificate is valid

## Best Practices

1. **Always use try-catch**: Wrap all SDK calls in try-catch blocks
2. **Log errors**: Log errors with sufficient context for debugging
3. **Handle gracefully**: Provide fallback behavior when possible
4. **Don't expose internals**: Don't expose consumer keys/secrets in error messages
5. **Monitor error rates**: Track error rates to detect issues early
6. **Implement retries**: For transient errors (5xx, network issues), implement retry logic
7. **Validate input**: Validate user input before sending to the API

## Example: Complete Error Handling

Here's a complete example combining multiple patterns:

```php
<?php

class AiProxyService
{
    private Client $client;
    private int $maxRetries;
    
    public function __construct(string $key, string $secret, int $maxRetries = 3)
    {
        $this->client = new Client($key, $secret);
        $this->maxRetries = $maxRetries;
    }
    
    public function chat(string $message): array
    {
        $attempt = 0;
        
        while ($attempt < $this->maxRetries) {
            try {
                $startTime = microtime(true);
                $response = $this->client->chat($message);
                $duration = microtime(true) - $startTime;
                
                $this->logSuccess($message, $duration);
                return $response;
                
            } catch (\RuntimeException $e) {
                $attempt++;
                $duration = microtime(true) - $startTime;
                
                // Don't retry on client errors
                if (preg_match('/HTTP 4\d{2}/', $e->getMessage())) {
                    $this->logError($message, $e, $duration, $attempt);
                    throw $e;
                }
                
                // Retry on server errors and network issues
                if ($attempt < $this->maxRetries) {
                    $delay = pow(2, $attempt); // Exponential backoff
                    sleep($delay);
                    continue;
                }
                
                $this->logError($message, $e, $duration, $attempt);
                throw $e;
            }
        }
        
        throw new \RuntimeException('Max retries exceeded');
    }
    
    private function logSuccess(string $message, float $duration): void
    {
        error_log(sprintf(
            '[AI Proxy] Success: message="%s", duration=%.2fs',
            substr($message, 0, 100),
            $duration
        ));
    }
    
    private function logError(string $message, \RuntimeException $e, float $duration, int $attempt): void
    {
        error_log(sprintf(
            '[AI Proxy] Error: message="%s", error="%s", duration=%.2fs, attempt=%d',
            substr($message, 0, 100),
            $e->getMessage(),
            $duration,
            $attempt
        ));
    }
}
```

