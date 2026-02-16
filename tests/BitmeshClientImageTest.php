<?php

namespace BitmeshAI\Tests;

use BitmeshAI\BitmeshClient;

class BitmeshClientImageTest extends BitmeshClientTestCase
{
    public function testImageBuildsPayloadAndSignature()
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

                return [
                    200,
                    json_encode([
                        'data' => [
                            ['url' => 'https://api.bitmesh.ai/imgrslt/abc123'],
                        ],
                    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                ];
            }
        };

        $prompt = 'A sunset over the mountains';
        $response = $client->image($prompt);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('data', $response);
        $this->assertSame('https://api.bitmesh.ai/imgrslt/abc123', $response['data'][0]['url']);

        $this->assertSame('https://api.bitmesh.ai/image', $client->captured['url']);

        $decodedBody = json_decode($client->captured['jsonBody'], true);
        $this->assertIsArray($decodedBody);
        $this->assertArrayNotHasKey('model', $decodedBody);
        $this->assertArrayHasKey('prompt', $decodedBody);
        $this->assertSame($prompt, $decodedBody['prompt']);

        $this->assertStringStartsWith('OAuth ', $client->captured['authHeader']);
    }

    public function testImageWithModelAndOptions()
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
                $this->captured = ['jsonBody' => $jsonBody];
                return [
                    200,
                    json_encode(['data' => []]),
                ];
            }
        };

        $prompt = 'test';
        $model = 'rundiffusion/juggernaut-lightning-flux';
        $options = [
            'width' => 1024,
            'height' => 1024,
            'seed' => 42,
            'n' => 1,
            'steps' => 1,
        ];

        $response = $client->image($prompt, $model, $options);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('data', $response);

        $decodedBody = json_decode($client->captured['jsonBody'], true);
        $this->assertSame($prompt, $decodedBody['prompt']);
        $this->assertSame($model, $decodedBody['model']);
        $this->assertSame(1024, $decodedBody['width']);
        $this->assertSame(1024, $decodedBody['height']);
        $this->assertSame(42, $decodedBody['seed']);
        $this->assertSame(1, $decodedBody['n']);
        $this->assertSame(1, $decodedBody['steps']);
    }
}
