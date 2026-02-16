<?php

namespace BitmeshAI;

class BitmeshClient
{
    private string $consumerKey;
    private string $consumerSecret;
    private string $apiBaseUrl;
    private string $userAgent;

    /**
     * Create a new Bitmesh AI client.
     *
     * @param string $consumerKey    OAuth consumer key provided by Bitmesh
     * @param string $consumerSecret OAuth consumer secret provided by Bitmesh
     * @param string $apiBaseUrl     Base URL of the Bitmesh AI API (change this if you use a local/dev server)
     *                               Defaults to the production URL.
     * @param string $userAgent      Optional User-Agent header value
     */
    public function __construct(
        string $consumerKey,
        string $consumerSecret,
        string $apiBaseUrl = 'https://aiproxyapi-production.up.railway.app',
        string $userAgent = 'BitmeshPhpSdk/1.0'
    ) {
        $this->consumerKey = $consumerKey;
        $this->consumerSecret = $consumerSecret;
        $this->apiBaseUrl = rtrim($apiBaseUrl, '/');
        $this->userAgent = $userAgent;
    }

    /**
     * Call the `/chat` endpoint.
     *
     * Minimal usage:
     *
     * $client = new BitmeshClient($consumerKey, $consumerSecret);
     * $response = $client->chat('What are some fun things to do with AI?');
     *
     * @param string|array<int, array{role:string,content:string}> $messages
     *        - string: convenience form, will be wrapped as a single "user" message
     *        - array: full messages array as expected by the API
     * @param string|null $model     Optional model name; if null, a sensible default is used
     * @param array<string,mixed> $extraPayload Extra fields to merge into the request payload
     *
     * @return array<string,mixed>   Decoded JSON response as associative array
     *
     * @throws \RuntimeException     On HTTP / transport / decode errors
     */
    public function chat(
        string|array $messages,
        ?string $model = null,
        array $extraPayload = []
    ): array {
        $url = $this->apiBaseUrl . '/chat';

        // Normalize messages parameter
        if (is_string($messages)) {
            $messages = [
                ['role' => 'user', 'content' => $messages],
            ];
        }

        $payload = array_merge(
            [
                'model' => $model ?: 'meta-llama/Llama-3.2-3B-Instruct-Turbo',
                'messages' => $messages,
            ],
            $extraPayload
        );

        $jsonBody = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($jsonBody === false) {
            throw new \RuntimeException('Failed to encode chat payload as JSON.');
        }

        // OAuth 1.0 params and Authorization header
        $method = 'POST';
        $oauthParams = $this->generateOAuthParams($method, $url);
        $authHeader = $this->buildAuthorizationHeader($oauthParams);

        // Payload signature header (matches sample script)
        $payloadSignature = hash('sha256', $jsonBody . $this->consumerKey . $oauthParams['oauth_signature']);

        // Execute HTTP request (extracted for easier testing)
        [$httpCode, $body] = $this->sendRequest($url, $authHeader, $payloadSignature, $jsonBody);

        $decoded = json_decode($body, true);

        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException(
                'Failed to decode Bitmesh response JSON: ' . json_last_error_msg() . '. Raw body: ' . $body
            );
        }

        if ($httpCode !== 200) {
            $message = 'Bitmesh API returned HTTP ' . $httpCode;
            if (is_array($decoded) && isset($decoded['error'])) {
                $message .= ' - ' . json_encode($decoded['error']);
            }
            throw new \RuntimeException($message);
        }

        return is_array($decoded) ? $decoded : ['data' => $decoded];
    }

    /**
     * Send HTTP request to Bitmesh AI.
     *
     * @param string $url
     * @param string $authHeader
     * @param string $payloadSignature
     * @param string $jsonBody
     *
     * @return array{0:int,1:string} [HTTP status code, response body]
     *
     * @throws \RuntimeException on transport errors
     */
    protected function sendRequest(
        string $url,
        string $authHeader,
        string $payloadSignature,
        string $jsonBody
    ): array {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: ' . $authHeader,
                'Accept: application/json',
                'Content-Type: application/json',
                'User-Agent: ' . $this->userAgent,
                'X-Payload-Signature: ' . $payloadSignature,
            ],
            CURLOPT_HEADER => false,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonBody,
        ]);

        $body = curl_exec($ch);
        if ($body === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException('Curl error while calling Bitmesh: ' . $error);
        }

        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [$httpCode, $body];
    }

    /**
     * Generate OAuth 1.0 parameters and signature.
     */
    private function generateOAuthParams(string $method, string $url): array
    {
        $params = [
            'oauth_consumer_key' => $this->consumerKey,
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => (string) time(),
            'oauth_nonce' => bin2hex(random_bytes(8)),
            'oauth_version' => '1.0',
        ];

        $params['oauth_signature'] = $this->generateSignature($method, $url, $params);

        return $params;
    }

    /**
     * Generate OAuth 1.0 signature using HMAC-SHA1.
     *
     * This mirrors the standalone script logic you provided.
     */
    private function generateSignature(string $method, string $url, array $params): string
    {
        $parsedUrl = parse_url($url);

        $scheme = $parsedUrl['scheme'] ?? 'http';
        $host = $parsedUrl['host'] ?? 'localhost';
        $port = $parsedUrl['port'] ?? null;
        $path = isset($parsedUrl['path']) ? ltrim($parsedUrl['path'], '/') : '';

        $normalizedUrl = $scheme . '://' . $host;

        if (
            ($scheme === 'http' && $port !== null && $port !== 80) ||
            ($scheme === 'https' && $port !== null && $port !== 443)
        ) {
            $normalizedUrl .= ':' . $port;
        }

        $normalizedUrl .= '/' . $path;

        $queryParams = [];
        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $queryParams);
        }

        $allParams = array_merge($params, $queryParams);
        unset($allParams['oauth_signature']);

        ksort($allParams);

        $normalizedParams = [];
        foreach ($allParams as $key => $value) {
            $normalizedParams[] = $this->urlEncode($key) . '=' . $this->urlEncode((string) $value);
        }
        $paramString = implode('&', $normalizedParams);

        $signatureBaseString =
            $this->urlEncode($method) . '&' .
            $this->urlEncode($normalizedUrl) . '&' .
            $this->urlEncode($paramString);

        $signingKey = $this->urlEncode($this->consumerSecret) . '&';

        return base64_encode(hash_hmac('sha1', $signatureBaseString, $signingKey, true));
    }

    /**
     * Build OAuth Authorization header.
     */
    private function buildAuthorizationHeader(array $oauthParams): string
    {
        $headerParts = [];

        foreach ($oauthParams as $key => $value) {
            if (strpos($key, 'oauth_') === 0) {
                $headerParts[] = $this->urlEncode($key) . '="' . $this->urlEncode((string) $value) . '"';
            }
        }

        return 'OAuth ' . implode(', ', $headerParts);
    }

    /**
     * RFC 3986-compliant URL encoding (for OAuth).
     */
    private function urlEncode(string $value): string
    {
        return str_replace(
            ['+', '%7E'],
            ['%20', '~'],
            rawurlencode($value)
        );
    }
}
