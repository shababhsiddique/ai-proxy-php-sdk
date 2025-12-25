<?php

namespace AiProxy\Tests;

use AiProxy\Client;
use PHPUnit\Framework\TestCase;

class ClientChatTest extends TestCase
{
    public function testChatBuildsPayloadAndSignature()
    {
        $consumerKey = 'test-consumer-key';
        $consumerSecret = 'test-consumer-secret';

        $client = new class($consumerKey, $consumerSecret, 'https://example.com') extends Client {
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
        $this->assertSame('https://example.com/chat', $client->captured['url']);

        // Assert JSON body structure
        $decodedBody = json_decode($client->captured['jsonBody'], true);
        $this->assertIsArray($decodedBody);
        $this->assertArrayHasKey('model', $decodedBody);
        $this->assertArrayHasKey('messages', $decodedBody);
        $this->assertSame($message, $decodedBody['messages'][0]['content']);

        // Assert Authorization header contains oauth fields
        $authHeader = $client->captured['authHeader'];
        $this->assertStringStartsWith('OAuth ', $authHeader);
        $this->assertStringContainsString('oauth_consumer_key="' . rawurlencode($consumerKey) . '"', $authHeader);

        // Extract oauth_signature from header to verify payload signature formula
        $this->assertMatchesRegularExpression('/oauth_signature="([^"]+)"/', $authHeader, $matches);
        $oauthSignature = rawurldecode($matches[1]);

        $expectedPayloadSignature = hash(
            'sha256',
            $client->captured['jsonBody'] . $consumerKey . $oauthSignature
        );

        $this->assertSame($expectedPayloadSignature, $client->captured['payloadSignature']);
    }
}


