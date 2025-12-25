# Authentication

The AI Proxy PHP SDK uses OAuth 1.0 authentication with additional payload signature verification.

## Overview

The SDK handles all authentication details automatically. You only need to provide your consumer key and secret when creating the client:

```php
$client = new Client($consumerKey, $consumerSecret);
```

However, understanding how authentication works can help with debugging and integration.

## OAuth 1.0 Flow

The SDK implements OAuth 1.0 (one-legged) authentication, which means:

1. **Consumer Key & Secret**: You provide these credentials (no user tokens required)
2. **Signature Generation**: The SDK generates an OAuth signature for each request
3. **Authorization Header**: The signature and OAuth parameters are sent in the `Authorization` header

## Signature Generation

The OAuth signature is generated using HMAC-SHA1 and follows these steps:

### 1. Collect Parameters

The SDK collects the following OAuth parameters:

- `oauth_consumer_key`: Your consumer key
- `oauth_signature_method`: `HMAC-SHA1`
- `oauth_timestamp`: Current Unix timestamp
- `oauth_nonce`: Random 16-character hex string
- `oauth_version`: `1.0`

### 2. Normalize URL

The request URL is normalized:

- Scheme and host are preserved
- Port is included only if non-standard (not 80 for HTTP, not 443 for HTTPS)
- Path is normalized (leading slash preserved)

Example:
- `https://api.example.com:443/chat` → `https://api.example.com/chat`
- `http://localhost:8003/chat` → `http://localhost:8003/chat`

### 3. Build Signature Base String

The signature base string is constructed as:

```
METHOD&URL&PARAMS
```

Where:
- `METHOD`: HTTP method (always `POST` for chat requests)
- `URL`: Normalized URL (percent-encoded)
- `PARAMS`: Sorted, normalized, percent-encoded parameters

### 4. Generate Signature

The signature is computed using HMAC-SHA1:

```php
$signingKey = urlencode($consumerSecret) . '&';
$signature = base64_encode(
    hash_hmac('sha1', $signatureBaseString, $signingKey, true)
);
```

### 5. Build Authorization Header

The `Authorization` header is constructed as:

```
OAuth oauth_consumer_key="...", oauth_signature_method="HMAC-SHA1", oauth_timestamp="...", oauth_nonce="...", oauth_version="1.0", oauth_signature="..."
```

All values are percent-encoded according to RFC 3986.

## Payload Signature

In addition to OAuth authentication, the SDK includes a payload signature header:

```
X-Payload-Signature: <hash>
```

The payload signature is computed as:

```php
$payloadSignature = hash(
    'sha256',
    $jsonBody . $consumerKey . $oauthSignature
);
```

This provides an additional layer of request integrity verification.

## Complete Request Example

Here's what a complete authenticated request looks like:

**Request:**
```
POST /chat HTTP/1.1
Host: aiproxyapi-production.up.railway.app
Authorization: OAuth oauth_consumer_key="KO45tkb7vs6HPdjZMkzWCgpKqGrycRol", oauth_signature_method="HMAC-SHA1", oauth_timestamp="1234567890", oauth_nonce="a1b2c3d4e5f6g7h8", oauth_version="1.0", oauth_signature="..."
Accept: application/json
Content-Type: application/json
User-Agent: AiProxyPhpSdk/1.0
X-Payload-Signature: <sha256-hash>

{
    "model": "meta-llama/Llama-3.2-3B-Instruct-Turbo",
    "messages": [
        {"role": "user", "content": "Hello!"}
    ]
}
```

## Troubleshooting Authentication

### Common Issues

**401 Unauthorized**

- Verify your consumer key and secret are correct
- Ensure your API key is active in the AI Proxy dashboard
- Check that the server time is synchronized (OAuth timestamps are time-sensitive)

**Signature Mismatch**

- The SDK handles URL normalization automatically
- Ensure you're using the correct base URL (production vs. development)
- Check that query parameters (if any) are being included correctly

**Payload Signature Errors**

- Verify the JSON body encoding matches what the server expects
- Ensure the consumer key matches between OAuth and payload signature

### Debugging

If you need to debug authentication issues, you can:

1. Check the exact URL being called (should be `{baseUrl}/chat`)
2. Verify the Authorization header format
3. Compare the payload signature with server-side expectations
4. Ensure all headers are being sent correctly

The SDK throws `RuntimeException` with descriptive error messages for most authentication failures.

