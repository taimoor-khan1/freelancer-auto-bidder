<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Portfolio;
use App\Services\FreelancerApiService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PortfolioController extends Controller
{
    protected FreelancerApiService $freelancerApi;

    public function __construct(FreelancerApiService $freelancerApi)
    {
        $this->freelancerApi = $freelancerApi;
    }

    /**
     * Display a listing of the user's portfolios.
     */
    public function index(): Response
    {
        $user = auth()->user();
        $portfolios = $user->portfolios()->orderBy('order')->orderBy('created_at', 'desc')->get();
        $technologies = $this->freelancerApi->getSkills();

        return Inertia::render('User/Portfolio/Index', [
            'portfolios' => $portfolios,
            'technologies' => $technologies,
        ]);
    }

    /**
     * Store a newly created portfolio.
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'url' => 'required|url|max:500',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'image_url' => 'nullable|url|max:500',
            'technologies' => 'nullable|array',
            'technologies.*' => 'string',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        // Auto-detect technologies from URL if not provided
        if (empty($validated['technologies']) && !empty($validated['url'])) {
            $validated['technologies'] = $this->detectTechnologiesFromUrl($validated['url']);
        }

        // Auto-generate title from URL if not provided
        if (empty($validated['title']) && !empty($validated['url'])) {
            $validated['title'] = $this->generateTitleFromUrl($validated['url']);
        }

        $validated['user_id'] = $user->id;
        $validated['order'] = $validated['order'] ?? ($user->portfolios()->max('order') ?? 0) + 1;
        $validated['is_active'] = $validated['is_active'] ?? true;

        Portfolio::create($validated);

        return redirect()->route('user.portfolio.index')
            ->with('success', 'Portfolio added successfully.');
    }

    /**
     * Detect technologies from URL.
     */
    protected function detectTechnologiesFromUrl(string $url): array
    {
        $technologies = [];
        
        // Get all available skills from Freelancer API
        $allSkills = $this->freelancerApi->getSkills();
        $skillNames = array_map(fn($skill) => strtolower($skill['name']), $allSkills);
        
        // Extract domain and path from URL
        $parsedUrl = parse_url($url);
        $domain = strtolower($parsedUrl['host'] ?? '');
        $path = strtolower($parsedUrl['path'] ?? '');
        $fullText = $domain . ' ' . $path;
        
        // Check for common technology keywords in URL
        $commonTechs = [
            'react', 'vue', 'angular', 'javascript', 'typescript', 'node', 'php', 'laravel',
            'python', 'django', 'flask', 'java', 'spring', 'csharp', 'dotnet', 'asp',
            'html', 'css', 'sass', 'less', 'bootstrap', 'tailwind',
            'mysql', 'postgresql', 'mongodb', 'redis',
            'aws', 'azure', 'gcp', 'docker', 'kubernetes',
            'wordpress', 'shopify', 'magento', 'woocommerce',
            'ios', 'android', 'swift', 'kotlin', 'flutter',
        ];
        
        foreach ($commonTechs as $tech) {
            if (str_contains($fullText, $tech)) {
                // Find matching skill from Freelancer API
                foreach ($allSkills as $skill) {
                    if (stripos($skill['name'], $tech) !== false) {
                        $technologies[] = $skill['name'];
                        break;
                    }
                }
            }
        }
        
        // If no technologies found, try to match from skill names
        if (empty($technologies)) {
            foreach ($allSkills as $skill) {
                $skillName = strtolower($skill['name']);
                if (str_contains($fullText, $skillName) || str_contains($fullText, str_replace(' ', '', $skillName))) {
                    $technologies[] = $skill['name'];
                    if (count($technologies) >= 5) { // Limit to 5 technologies
                        break;
                    }
                }
            }
        }
        
        return array_unique($technologies);
    }

    /**
     * Generate title from URL.
     */
    protected function generateTitleFromUrl(string $url): string
    {
        $parsedUrl = parse_url($url);
        $host = $parsedUrl['host'] ?? '';
        
        // Remove www. and common TLDs
        $host = preg_replace('/^www\./', '', $host);
        $host = preg_replace('/\.(com|net|org|io|co|dev)$/', '', $host);
        
        // Capitalize and return
        return ucwords(str_replace(['.', '-', '_'], ' ', $host));
    }

    /**
     * Update the specified portfolio.
     */
    public function update(Request $request, Portfolio $portfolio)
    {
        // Ensure user owns this portfolio
        if ($portfolio->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'url' => 'required|url|max:500',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'image_url' => 'nullable|url|max:500',
            'technologies' => 'nullable|array',
            'technologies.*' => 'string',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        // Auto-detect technologies from URL if URL changed and technologies not provided
        if ($portfolio->url !== $validated['url'] && empty($validated['technologies'])) {
            $validated['technologies'] = $this->detectTechnologiesFromUrl($validated['url']);
        }

        // Auto-generate title from URL if not provided
        if (empty($validated['title']) && !empty($validated['url'])) {
            $validated['title'] = $this->generateTitleFromUrl($validated['url']);
        }

        $portfolio->update($validated);

        return redirect()->route('user.portfolio.index')
            ->with('success', 'Portfolio updated successfully.');
    }

    /**
     * Remove the specified portfolio.
     */
    public function destroy(Portfolio $portfolio)
    {
        // Ensure user owns this portfolio
        if ($portfolio->user_id !== auth()->id()) {
            abort(403);
        }

        $portfolio->delete();

        return redirect()->route('user.portfolio.index')
            ->with('success', 'Portfolio deleted successfully.');
    }
}
