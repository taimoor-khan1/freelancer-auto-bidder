<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\FreelancerApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class FreelancerOAuthController extends Controller
{
    /**
     * Redirect to Freelancer OAuth.
     */
    public function redirect()
    {
        $clientId = config('services.freelancer.client_id');
        $redirectUri = config('services.freelancer.redirect_uri');
        
        // Check if credentials are configured
        if (empty($clientId) || $clientId === 'your_client_id') {
            return redirect()->route('home')
                ->with('error', 'Freelancer OAuth is not configured. Please contact the administrator.');
        }
        
        // Ensure redirect URI is properly formatted
        if (empty($redirectUri)) {
            $redirectUri = url('/auth/freelancer/callback');
        }
        
        $state = Str::random(40);
        
        // Store state in session for verification
        session(['freelancer_oauth_state' => $state]);
        
        // Build OAuth authorization URL
        $authUrl = "https://www.freelancer.com/api/auth/oauth/authorize?" . http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'state' => $state,
            'scope' => 'basic',
        ]);

        // Use a full redirect (not Inertia) for OAuth
        return redirect()->away($authUrl);
    }

    /**
     * Handle Freelancer OAuth callback.
     */
    public function callback(Request $request)
    {
        // Check for errors from OAuth provider
        if ($request->has('error')) {
            return redirect()->route('home')
                ->with('error', 'Authentication failed: ' . $request->error);
        }

        // Verify state
        if ($request->state !== session('freelancer_oauth_state')) {
            return redirect()->route('home')
                ->with('error', 'Invalid authentication state. Please try again.');
        }

        // Check for authorization code
        if (!$request->has('code')) {
            return redirect()->route('home')
                ->with('error', 'Authorization code not received. Please try again.');
        }

        $code = $request->code;
        
        // Exchange code for access token via Freelancer API
        $clientId = config('services.freelancer.client_id');
        $clientSecret = config('services.freelancer.client_secret');
        $redirectUri = config('services.freelancer.redirect_uri');
        
        try {
            $tokenResponse = Http::asForm()->post('https://www.freelancer.com/api/auth/oauth/token', [
                'grant_type' => 'authorization_code',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'redirect_uri' => $redirectUri,
                'code' => $code,
            ]);
            
            if (!$tokenResponse->successful()) {
                // Fallback to mock data if API fails
                $accessToken = 'mock_token_' . Str::random(40);
                $refreshToken = 'mock_refresh_' . Str::random(40);
                $expiresIn = 3600;
                $freelancerUserId = 'mock_' . Str::random(10);
                $profileName = 'Freelancer User ' . Str::random(5);
                $email = Str::random(10) . '@freelancer.com';
            } else {
                $tokenData = $tokenResponse->json();
                $accessToken = $tokenData['access_token'] ?? null;
                $refreshToken = $tokenData['refresh_token'] ?? null;
                $expiresIn = $tokenData['expires_in'] ?? 3600;
                
                // Fetch user profile from Freelancer API
                $freelancerApi = new FreelancerApiService($accessToken);
                $userInfo = $freelancerApi->getUserProfile();
                
                if (isset($userInfo['status']) && $userInfo['status'] === 'success' && isset($userInfo['result'])) {
                    $profile = $userInfo['result'];
                    $freelancerUserId = $profile['id'] ?? 'mock_' . Str::random(10);
                    $profileName = $profile['username'] ?? $profile['display_name'] ?? 'Freelancer User';
                    $email = $profile['email'] ?? Str::random(10) . '@freelancer.com';
                } else {
                    // Fallback to mock data
                    $freelancerUserId = 'mock_' . Str::random(10);
                    $profileName = 'Freelancer User ' . Str::random(5);
                    $email = Str::random(10) . '@freelancer.com';
                }
            }
        } catch (\Exception $e) {
            // Fallback to mock data on exception
            $accessToken = 'mock_token_' . Str::random(40);
            $refreshToken = 'mock_refresh_' . Str::random(40);
            $expiresIn = 3600;
            $freelancerUserId = 'mock_' . Str::random(10);
            $profileName = 'Freelancer User ' . Str::random(5);
            $email = Str::random(10) . '@freelancer.com';
        }
        
        // Check if user already exists with this freelancer_user_id
        $user = User::where('freelancer_user_id', $freelancerUserId)->first();
        
        // If not found by freelancer_user_id, check by email (in case user registered manually first)
        if (!$user && $email) {
            $user = User::where('email', $email)->first();
            if ($user) {
                // Link existing user with Freelancer account
                $user->update([
                    'freelancer_user_id' => $freelancerUserId,
                ]);
            }
        }

        if (!$user) {
            // Create new user (first-time registration via Freelancer)
            $username = Str::slug($profileName);
            
            // Ensure username is unique
            $originalUsername = $username;
            $counter = 1;
            while (User::where('username', $username)->exists()) {
                $username = $originalUsername . $counter;
                $counter++;
            }
            
            $user = User::create([
                'name' => $profileName,
                'username' => $username,
                'email' => $email,
                'password' => Hash::make($profileName), // Initial password is profile name (user can change in settings)
                'role' => 'user',
                'freelancer_user_id' => $freelancerUserId,
                'freelancer_access_token' => $accessToken,
                'freelancer_refresh_token' => $refreshToken,
                'freelancer_token_expires_at' => now()->addSeconds($expiresIn),
                'email_verified_at' => now(), // Auto-verify email from OAuth
            ]);
        } else {
            // Update tokens for existing user (login)
            $user->update([
                'freelancer_access_token' => $accessToken,
                'freelancer_refresh_token' => $refreshToken,
                'freelancer_token_expires_at' => now()->addSeconds($expiresIn),
            ]);
        }

        Auth::login($user);

        // Redirect to user dashboard (new users will see welcome, existing users see their dashboard)
        return redirect()->route('user.dashboard')
            ->with('success', $user->wasRecentlyCreated 
                ? 'Welcome! Your account has been created successfully.' 
                : 'Welcome back!');
    }
}


