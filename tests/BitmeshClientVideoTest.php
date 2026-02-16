<?php

namespace BitmeshAI\Tests;

use BitmeshAI\BitmeshClient;

class BitmeshClientVideoTest extends BitmeshClientTestCase
{
    public function testVideoBuildsPayloadAndSignature()
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
                        'object' => 'video',
                        'id' => '019c65b5-b577-7963-84cd-0303456ecdf5',
                        'model' => 'ByteDance/Seedance-1.0-lite',
                        'status' => 'in_progress',
                        'created_at' => 1771232933,
                        'seconds' => '5',
                        'size' => '1440x1440',
                    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                ];
            }
        };

        $prompt = 'Test';
        $model = 'bytedance/seedance-1.0-lite';
        $options = ['seed' => 13];
        $response = $client->video($prompt, $model, $options);

        $this->assertIsArray($response);
        $this->assertSame('video', $response['object']);
        $this->assertSame('019c65b5-b577-7963-84cd-0303456ecdf5', $response['id']);
        $this->assertSame('in_progress', $response['status']);

        $this->assertSame('https://api.bitmesh.ai/video', $client->captured['url']);

        $decodedBody = json_decode($client->captured['jsonBody'], true);
        $this->assertIsArray($decodedBody);
        $this->assertSame($prompt, $decodedBody['prompt']);
        $this->assertSame($model, $decodedBody['model']);
        $this->assertSame(13, $decodedBody['seed']);

        $this->assertStringStartsWith('OAuth ', $client->captured['authHeader']);
    }

    public function testVideoWithModelAndOptions()
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
                    json_encode([
                        'id' => 'job-456',
                        'status' => 'queued',
                    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                ];
            }
        };

        $prompt = 'Ocean waves at sunset';
        $model = 'provider/video-model-v1';
        $options = [
            'width' => 1280,
            'height' => 720,
            'seconds' => '5',
            'fps' => 24,
            'steps' => 25,
            'seed' => 123,
            'output_format' => 'MP4',
            'output_quality' => 2,
            'negative_prompt' => 'blurry',
        ];

        $response = $client->video($prompt, $model, $options);

        $this->assertIsArray($response);
        $this->assertSame('job-456', $response['id']);

        $decodedBody = json_decode($client->captured['jsonBody'], true);
        $this->assertSame($prompt, $decodedBody['prompt']);
        $this->assertSame($model, $decodedBody['model']);
        $this->assertSame(1280, $decodedBody['width']);
        $this->assertSame(720, $decodedBody['height']);
        $this->assertSame('5', $decodedBody['seconds']);
        $this->assertSame(24, $decodedBody['fps']);
        $this->assertSame(25, $decodedBody['steps']);
        $this->assertSame(123, $decodedBody['seed']);
        $this->assertSame('MP4', $decodedBody['output_format']);
        $this->assertSame(2, $decodedBody['output_quality']);
        $this->assertSame('blurry', $decodedBody['negative_prompt']);
    }

    public function testVideoStatusBuildsGetRequest()
    {
        $consumerKey = $this->getConsumerKey();
        $consumerSecret = $this->getConsumerSecret();

        $client = new class($consumerKey, $consumerSecret, 'https://api.bitmesh.ai') extends BitmeshClient {
            public array $captured = [];

            protected function sendGetRequest(string $url, string $authHeader, string $payloadSignature): array
            {
                $this->captured = [
                    'url' => $url,
                    'authHeader' => $authHeader,
                    'payloadSignature' => $payloadSignature,
                ];

                return [
                    200,
                    json_encode([
                        'id' => 'video-job-789',
                        'status' => 'completed',
                        'outputs' => [
                            'video_url' => 'https://api.bitmesh.ai/imgrslt/video-abc',
                        ],
                    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                ];
            }
        };

        $jobId = 'video-job-789';
        $response = $client->videoStatus($jobId);

        $this->assertIsArray($response);
        $this->assertSame($jobId, $response['id']);
        $this->assertSame('completed', $response['status']);
        $this->assertArrayHasKey('outputs', $response);
        $this->assertSame('https://api.bitmesh.ai/imgrslt/video-abc', $response['outputs']['video_url']);

        $this->assertSame('https://api.bitmesh.ai/video/video-job-789', $client->captured['url']);
        $this->assertStringStartsWith('OAuth ', $client->captured['authHeader']);
        $this->assertNotEmpty($client->captured['payloadSignature']);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $client->captured['payloadSignature']);
    }

    public function testVideoStatusEncodesJobIdInUrl()
    {
        $consumerKey = $this->getConsumerKey();
        $consumerSecret = $this->getConsumerSecret();

        $client = new class($consumerKey, $consumerSecret, 'https://api.bitmesh.ai') extends BitmeshClient {
            public array $captured = [];

            protected function sendGetRequest(string $url, string $authHeader, string $payloadSignature): array
            {
                $this->captured = ['url' => $url];
                return [200, json_encode(['id' => 'job/with/slashes', 'status' => 'running'])];
            }
        };

        $client->videoStatus('job/with/slashes');

        $this->assertSame('https://api.bitmesh.ai/video/job%2Fwith%2Fslashes', $client->captured['url']);
    }
}
