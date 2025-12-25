# Getting Started

This guide will help you get up and running with the AI Proxy PHP SDK in just a few minutes.

## Prerequisites

- PHP 8.0 or higher
- Composer (PHP package manager)
- An AI Proxy account with consumer key and secret

## Installation

### Install via Composer

The recommended way to install the SDK is through [Composer](https://getcomposer.org/):

```bash
composer require ai-proxy/php-sdk
```

This will add the SDK to your `composer.json` and install it in the `vendor/` directory.

### Manual Installation

If you prefer to install manually, clone this repository:

```bash
git clone https://github.com/your-org/ai-proxy-php-sdk.git
cd ai-proxy-php-sdk
composer install
```

## Quick Start

### 1. Include the Autoloader

In your PHP script, include the Composer autoloader:

```php
<?php

require 'vendor/autoload.php';
```

### 2. Create a Client Instance

Initialize the client with your consumer key and secret:

```php
use AiProxy\Client;

$consumerKey = 'YOUR_CONSUMER_KEY';
$consumerSecret = 'YOUR_CONSUMER_SECRET';

$client = new Client($consumerKey, $consumerSecret);
```

### 3. Make Your First API Call

Send a simple chat message:

```php
$response = $client->chat('What are some fun things to do with AI?');

print_r($response);
```

That's it! You've successfully made your first API call.

## Configuration Options

### Using a Custom Base URL

If you're running the AI Proxy API on a local or development server, you can specify a custom base URL:

```php
$client = new Client(
    $consumerKey,
    $consumerSecret,
    'http://localhost:8003'  // Custom base URL
);
```

The SDK will automatically append `/chat` to this URL when making requests.

### Custom User-Agent

You can optionally set a custom User-Agent header:

```php
$client = new Client(
    $consumerKey,
    $consumerSecret,
    'https://aiproxyapi-production.up.railway.app',
    'MyApp/1.0'  // Custom User-Agent
);
```

## Next Steps

- Read the [API Reference](api-reference.md) for detailed method documentation
- Check out [Examples](examples.md) for more advanced usage patterns
- Learn about [Error Handling](error-handling.md) to build robust applications
- Understand [Authentication](authentication.md) details if you need to customize the OAuth flow

