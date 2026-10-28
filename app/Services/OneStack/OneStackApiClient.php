<?php

namespace App\Services\OneStack;

use App\Services\OneStack\Exceptions\OneStackApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OneStackApiClient
{
    protected string $url;

    protected ?string $apiKey;

    protected int $timeout;

    protected int $retry;

    public function __construct(
        ?string $url = null,
        ?string $apiKey = null,
        ?int $timeout = null,
        ?int $retry = null
    ) {
        $this->url = $url ?? config('services.onestack.url', 'https://onestack.kejora.gov.my/onestack/api-v2/data_user.php');
        $this->apiKey = $apiKey ?? config('services.onestack.key');
        $this->timeout = $timeout ?? (int) config('services.onestack.timeout', 15);
        $this->retry = $retry ?? (int) config('services.onestack.retry', 3);
    }

    /**
     * Fetch all users from the OneStack API.
     *
     * @return array<int, array<string, mixed>>
     */
    public function fetchAllUsers(): array
    {
        $response = $this->sendRequest();

        return $this->extractDataArray($response);
    }

    /**
     * Fetch a single user by employee ID (id_pengguna).
     *
     * @return array<string, mixed>|null
     */
    public function fetchUserById(string $idPengguna): ?array
    {
        $response = $this->sendRequest(['id_pengguna' => trim($idPengguna)]);

        $data = $response->json('data');

        if (empty($data)) {
            return null;
        }

        // The API returns an associative object when filtered, or an array with 1 item
        if (is_array($data)) {
            if (isset($data['id_pengguna'])) {
                return $data;
            }

            return ! empty($data[0]) ? $data[0] : null;
        }

        return null;
    }

    /**
     * Send authenticated HTTP request to OneStack API.
     */
    protected function sendRequest(array $queryParams = []): Response
    {
        if (empty($this->apiKey)) {
            throw OneStackApiException::missingApiKey();
        }

        try {
            $response = Http::withHeaders([
                'X-API-Key' => $this->apiKey,
                'Accept' => 'application/json',
            ])
                ->connectTimeout(5)
                ->timeout($this->timeout)
                ->retry($this->retry, 1000, function ($exception) {
                    return $exception instanceof ConnectionException;
                }, throw: false)
                ->get($this->url, $queryParams);

            if (! $response->successful()) {
                throw OneStackApiException::requestFailed(
                    $response->json('message') ?? 'HTTP status '.$response->status(),
                    $response->status()
                );
            }

            return $response;
        } catch (OneStackApiException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $status = 0;
            if ($e instanceof \Illuminate\Http\Client\RequestException && $e->response) {
                $status = $e->response->status();
            } elseif ($e->getCode()) {
                $status = (int) $e->getCode();
            }

            if (! app()->runningUnitTests()) {
                Log::error('OneStack API communication failure', [
                    'error' => $e->getMessage(),
                    'url' => $this->url,
                ]);
            }

            throw OneStackApiException::requestFailed($e->getMessage(), $status, $e);
        }
    }

    /**
     * Extract array of users from response payload.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function extractDataArray(Response $response): array
    {
        $data = $response->json('data');

        if (! is_array($data)) {
            throw OneStackApiException::invalidResponse('Payload data key is missing or not a valid list.');
        }

        // If a single associative object was returned
        if (isset($data['id_pengguna'])) {
            return [$data];
        }

        return $data;
    }
}
