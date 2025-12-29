<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AiSettingController extends Controller
{
    /**
     * Display AI settings.
     */
    public function index(): Response
    {
        $settings = AiSetting::first();
        
        if (!$settings) {
            $settings = AiSetting::create([
                'ai_model' => 'openai',
                'api_key' => '',
                'prompt_template' => '',
                'is_active' => true,
            ]);
        }

        return Inertia::render('Admin/AiSettings/Index', [
            'settings' => $settings,
        ]);
    }

    /**
     * Update AI settings.
     */
    public function update(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'ai_model' => 'required|in:openai,anthropic',
            'api_key' => 'required|string',
            'prompt_template' => 'required|string',
            'is_active' => 'boolean',
        ]);

        $settings = AiSetting::first();
        
        if (!$settings) {
            $settings = new AiSetting();
        }

        $settings->fill($validated);
        $settings->save();

        return redirect()->route('admin.ai-settings.index')
            ->with('success', 'AI settings updated successfully.');
    }
}
