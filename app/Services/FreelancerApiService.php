<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\RateLimiterService;

class FreelancerApiService
{
    private string $baseUrl = 'https://www.freelancer.com/api';
    private ?string $accessToken = null;
    private RateLimiterService $rateLimiter;
    private int $maxRetries = 3;
    private int $retryDelay = 2; // seconds

    public function __construct(?string $accessToken = null, ?RateLimiterService $rateLimiter = null)
    {
        $this->accessToken = $accessToken;
        $this->rateLimiter = $rateLimiter ?? new RateLimiterService();
    }

    /**
     * Set the access token for API requests.
     */
    public function setAccessToken(string $accessToken): self
    {
        $this->accessToken = $accessToken;
        return $this;
    }

    /**
     * Make an authenticated API request with retry logic and rate limiting.
     */
    private function request(string $method, string $endpoint, array $data = [], int $retryCount = 0): array
    {
        $url = $this->baseUrl . $endpoint;
        
        // Check rate limits before making request
        if ($this->accessToken && !$this->rateLimiter->canMakeRequest($this->accessToken)) {
            Log::warning('Rate limit reached, waiting...', ['endpoint' => $endpoint]);
            
            if (!$this->rateLimiter->waitForRateLimit($this->accessToken)) {
                return ['status' => 'error', 'message' => 'Rate limit exceeded. Please try again later.'];
            }
        }
        
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        if ($this->accessToken) {
            $headers['Authorization'] = 'Bearer ' . $this->accessToken;
        }

        try {
            $response = Http::timeout(30)->withHeaders($headers)->$method($url, $data);
            
            // Record the API request for rate limiting
            if ($this->accessToken) {
                $this->rateLimiter->recordRequest($this->accessToken);
            }
            
            if ($response->successful()) {
                return $response->json();
            }

            // Handle rate limiting errors
            if ($response->status() === 429) {
                Log::warning('Rate limit hit, retrying...', [
                    'endpoint' => $endpoint,
                    'retry_count' => $retryCount,
                ]);
                
                if ($retryCount < $this->maxRetries) {
                    sleep($this->retryDelay * ($retryCount + 1)); // Exponential backoff
                    return $this->request($method, $endpoint, $data, $retryCount + 1);
                }
                
                return ['status' => 'error', 'message' => 'Rate limit exceeded after retries'];
            }

            // Handle authentication errors
            if ($response->status() === 401) {
                Log::error('Authentication failed', [
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                ]);
                
                return ['status' => 'error', 'message' => 'Authentication failed. Token may be expired.'];
            }

            // Retry on server errors (5xx)
            if ($response->status() >= 500 && $retryCount < $this->maxRetries) {
                Log::warning('Server error, retrying...', [
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                    'retry_count' => $retryCount,
                ]);
                
                sleep($this->retryDelay * ($retryCount + 1));
                return $this->request($method, $endpoint, $data, $retryCount + 1);
            }

            Log::error('Freelancer API Error', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return ['status' => 'error', 'message' => 'API request failed', 'status_code' => $response->status()];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // Retry on connection errors
            if ($retryCount < $this->maxRetries) {
                Log::warning('Connection error, retrying...', [
                    'endpoint' => $endpoint,
                    'error' => $e->getMessage(),
                    'retry_count' => $retryCount,
                ]);
                
                sleep($this->retryDelay * ($retryCount + 1));
                return $this->request($method, $endpoint, $data, $retryCount + 1);
            }
            
            Log::error('Freelancer API Connection Exception', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);
            
            return ['status' => 'error', 'message' => 'Connection failed: ' . $e->getMessage()];
        } catch (\Exception $e) {
            Log::error('Freelancer API Exception', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Get user profile information.
     */
    public function getUserProfile(): array
    {
        return $this->request('get', '/users/0.1/self/');
    }

    /**
     * Get all countries from Freelancer API.
     */
    public function getCountries(): array
    {
        // Cache for 24 hours
        return Cache::remember('freelancer_countries', 86400, function () {
            $response = $this->request('get', '/countries/0.1/countries/');
            
            if (isset($response['status']) && $response['status'] === 'success') {
                return array_map(function ($country) {
                    return [
                        'code' => $country['code'] ?? $country['id'] ?? null,
                        'name' => $country['name'] ?? $country['country'] ?? null,
                    ];
                }, $response['result'] ?? []);
            }

            // Fallback to common countries if API fails
            return $this->getDefaultCountries();
        });
    }

    /**
     * Get all skills/technologies from Freelancer API.
     */
    public function getSkills(): array
    {
        // Cache for 24 hours
        return Cache::remember('freelancer_skills', 86400, function () {
            $response = $this->request('get', '/projects/0.1/jobs/');
            
            if (isset($response['status']) && $response['status'] === 'success') {
                return array_map(function ($skill) {
                    return [
                        'id' => $skill['id'] ?? null,
                        'name' => $skill['name'] ?? null,
                    ];
                }, $response['result'] ?? []);
            }

            // Fallback to common technologies if API fails
            return $this->getDefaultTechnologies();
        });
    }

    /**
     * Get all project categories from Freelancer API.
     */
    public function getCategories(): array
    {
        // Cache for 24 hours
        return Cache::remember('freelancer_categories', 86400, function () {
            $response = $this->request('get', '/projects/0.1/categories/');
            
            if (isset($response['status']) && $response['status'] === 'success') {
                return array_map(function ($category) {
                    return [
                        'id' => $category['id'] ?? null,
                        'name' => $category['name'] ?? null,
                    ];
                }, $response['result'] ?? []);
            }

            // Fallback to common categories if API fails
            return $this->getDefaultCategories();
        });
    }

    /**
     * Search for projects based on criteria.
     */
    public function searchProjects(array $criteria): array
    {
        $params = [
            'active_only' => true,
            'limit' => $criteria['limit'] ?? 50,
            'offset' => $criteria['offset'] ?? 0,
        ];
        
        if (isset($criteria['countries']) && !empty($criteria['countries'])) {
            // Freelancer API expects countries as array
            foreach ($criteria['countries'] as $country) {
                $params['countries[]'][] = $country;
            }
        }
        
        if (isset($criteria['jobs']) && !empty($criteria['jobs'])) {
            // Map technology names to job IDs (simplified - in production, maintain a mapping)
            foreach ($criteria['jobs'] as $job) {
                $params['jobs[]'][] = $job;
            }
        }
        
        if (isset($criteria['categories']) && !empty($criteria['categories'])) {
            foreach ($criteria['categories'] as $category) {
                $params['categories[]'][] = $category;
            }
        }
        
        if (isset($criteria['min_budget'])) {
            $params['min_budget'] = (int) $criteria['min_budget'];
        }
        
        if (isset($criteria['max_budget'])) {
            $params['max_budget'] = (int) $criteria['max_budget'];
        }

        // Build query string manually to handle array parameters
        $queryParts = [];
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $item) {
                    $queryParts[] = urlencode($key) . '=' . urlencode($item);
                }
            } else {
                $queryParts[] = urlencode($key) . '=' . urlencode($value);
            }
        }
        
        $queryString = implode('&', $queryParts);
        return $this->request('get', '/projects/0.1/projects/search/?' . $queryString);
    }

    /**
     * Submit a bid on a project.
     */
    public function submitBid(int $projectId, float $amount, string $coverLetter): array
    {
        return $this->request('post', '/projects/0.1/bids/', [
            'project_id' => $projectId,
            'bidder_id' => null, // Will be set from access token
            'amount' => $amount,
            'period' => 0, // Fixed price
            'milestone_percentage' => 100,
            'description' => $coverLetter,
        ]);
    }

    /**
     * Default countries fallback.
     */
    private function getDefaultCountries(): array
    {
        return [
            ['code' => 'US', 'name' => 'United States'],
            ['code' => 'GB', 'name' => 'United Kingdom'],
            ['code' => 'CA', 'name' => 'Canada'],
            ['code' => 'AU', 'name' => 'Australia'],
            ['code' => 'IN', 'name' => 'India'],
            ['code' => 'PH', 'name' => 'Philippines'],
            ['code' => 'PK', 'name' => 'Pakistan'],
            ['code' => 'BD', 'name' => 'Bangladesh'],
            ['code' => 'NG', 'name' => 'Nigeria'],
            ['code' => 'KE', 'name' => 'Kenya'],
        ];
    }

    /**
     * Default technologies fallback.
     */
    private function getDefaultTechnologies(): array
    {
        return [
            ['id' => 1, 'name' => 'PHP'],
            ['id' => 2, 'name' => 'JavaScript'],
            ['id' => 3, 'name' => 'Python'],
            ['id' => 4, 'name' => 'Java'],
            ['id' => 5, 'name' => 'React'],
            ['id' => 6, 'name' => 'Vue.js'],
            ['id' => 7, 'name' => 'Laravel'],
            ['id' => 8, 'name' => 'Node.js'],
            ['id' => 9, 'name' => 'Angular'],
            ['id' => 10, 'name' => 'Django'],
        ];
    }

    /**
     * Default categories fallback.
     */
    private function getDefaultCategories(): array
    {
        return [
            ['id' => 1, 'name' => 'Web Development'],
            ['id' => 2, 'name' => 'Mobile Development'],
            ['id' => 3, 'name' => 'Design'],
            ['id' => 4, 'name' => 'Writing'],
            ['id' => 5, 'name' => 'Marketing'],
            ['id' => 6, 'name' => 'Data Entry'],
            ['id' => 7, 'name' => 'Video Editing'],
            ['id' => 8, 'name' => 'SEO'],
        ];
    }
}

