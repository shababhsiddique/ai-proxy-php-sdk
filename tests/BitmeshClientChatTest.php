<?php

namespace BitmeshAI\Tests;

use BitmeshAI\BitmeshClient;

class BitmeshClientChatTest extends BitmeshClientTestCase
{
    public function testChatBuildsPayloadAndSignature()
    {
        $consumerKey = $this->getConsumerKey();
        $consumerSecret = $this->getConsumerSecret();

        $client = new class($consumerKey, $consumerSecret, 'https://api.bitmesh.ai') extends BitmeshClient {
            public array $captured = [];

            protected function sendRequest(
                string $url,
                string $authHeader,
                string $payloadSignature,
                string $jsonBody
            ): array {
                $this->captured = [
                    'url' => $url,
                    'authHeader' => $authHeader,
                    'payloadSignature' => $payloadSignature,
                    'jsonBody' => $jsonBody,
                ];

                // Simulate successful API response
                return [
                    200,
                    json_encode([
                        'choices' => [
                            ['message' => ['role' => 'assistant', 'content' => 'Hello from test!']],
                        ],
                    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                ];
            }
        };

        $message = 'What are some fun things to do with AI?';
        $response = $client->chat($message);

        // Assert decoded response structure
        $this->assertIsArray($response);
        $this->assertArrayHasKey('choices', $response);
        $this->assertSame('assistant', $response['choices'][0]['message']['role']);

        // Assert request URL
        $this->assertSame('https://api.bitmesh.ai/chat', $client->captured['url']);

        // Assert JSON body structure (model omitted when null per API: prohibited if key has fixed model)
        $decodedBody = json_decode($client->captured['jsonBody'], true);
        $this->assertIsArray($decodedBody);
        $this->assertArrayNotHasKey('model', $decodedBody);
        $this->assertArrayHasKey('messages', $decodedBody);
        $this->assertSame($message, $decodedBody['messages'][0]['content']);

        // Assert Authorization header contains oauth fields
        $authHeader = $client->captured['authHeader'];
        $this->assertStringStartsWith('OAuth ', $authHeader);
        $this->assertStringContainsString('oauth_consumer_key="' . rawurlencode($consumerKey) . '"', $authHeader);

        // Extract oauth_signature from header to verify payload signature formula
        $this->assertMatchesRegularExpression('/oauth_signature="([^"]+)"/', $authHeader);
        preg_match('/oauth_signature="([^"]+)"/', $authHeader, $matches);
        $oauthSignature = rawurldecode($matches[1]);

        $expectedPayloadSignature = hash(
            'sha256',
            $client->captured['jsonBody'] . $consumerKey . $oauthSignature
        );

        $this->assertSame($expectedPayloadSignature, $client->captured['payloadSignature']);
    }

    public function testChatWithMaxTokensAndModel()
    {
        $consumerKey = $this->getConsumerKey();
        $consumerSecret = $this->getConsumerSecret();

        $client = new class($consumerKey, $consumerSecret, 'https://api.bitmesh.ai') extends BitmeshClient {
            public array $captured = [];

            protected function sendRequest(
                string $url,
                string $authHeader,
                string $payloadSignature,
                string $jsonBody
            ): array {
                $this->captured = [
                    'url' => $url,
                    'jsonBody' => $jsonBody,
                ];

                return [
                    200,
                    json_encode([
                        'choices' => [
                            ['message' => ['role' => 'assistant', 'content' => 'Hi']],
                        ],
                    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                ];
            }
        };

        $model = 'google/gemma-3n-1b';
        $message = 'Hi';

        $response = $client->chat($message, $model, ['max_tokens' => 1]);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('choices', $response);

        $decodedBody = json_decode($client->captured['jsonBody'], true);
        $this->assertSame($model, $decodedBody['model']);
        $this->assertSame(1, $decodedBody['max_tokens']);
    }
}
