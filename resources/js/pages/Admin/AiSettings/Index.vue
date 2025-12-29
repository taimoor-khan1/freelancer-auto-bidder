<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import { route } from '@/lib/utils';
import { Zap, Save, AlertCircle } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';

const props = defineProps<{
    settings: {
        id: number;
        ai_model: string;
        api_key: string;
        prompt_template: string;
        is_active: boolean;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin Dashboard',
        href: route('admin.dashboard'),
    },
    {
        title: 'AI Settings',
        href: route('admin.ai-settings.index'),
    },
];

const form = useForm({
    ai_model: props.settings.ai_model || 'openai',
    api_key: props.settings.api_key || '',
    prompt_template: props.settings.prompt_template || '',
    is_active: props.settings.is_active ?? true,
});

const defaultPromptTemplate = `Write a professional proposal for the following project:

Project Title: {project_title}
Description: {project_description}
Budget: {project_budget}
Required Technologies: {technologies}
{portfolio_items}

Write a compelling proposal that:
1. Shows understanding of the project requirements
2. Highlights relevant experience and skills
3. Mentions relevant portfolio items
4. Demonstrates enthusiasm and professionalism
5. Is concise (2-3 paragraphs)

Sign the proposal as: {user_name}`;

function loadDefaultPrompt() {
    form.prompt_template = defaultPromptTemplate;
}

function submitForm() {
    form.put(route('admin.ai-settings.update'), {
        onSuccess: () => {
            // Success handled by flash message
        },
    });
}
</script>

<template>
    <Head title="AI Settings" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-3 overflow-x-auto rounded-lg p-3 bg-gray-50">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <Zap class="h-5 w-5 text-[#006AFF]" />
                    <h1 class="text-xl font-bold">AI Settings</h1>
                </div>
            </div>

            <Card class="p-3">
                <CardHeader class="p-2 pb-2">
                    <CardTitle class="text-sm">Configure AI for Auto-Bidding</CardTitle>
                </CardHeader>
                <CardContent class="p-2 pt-0">
                    <form @submit.prevent="submitForm" class="space-y-4">
                        <!-- AI Model Selection -->
                        <div class="grid gap-2">
                            <Label for="ai_model" class="text-xs">AI Model</Label>
                            <select
                                id="ai_model"
                                v-model="form.ai_model"
                                class="h-8 px-3 text-xs border border-gray-300 rounded-md bg-white focus:outline-none focus:ring-2 focus:ring-[#006AFF] focus:border-[#006AFF]"
                            >
                                <option value="openai">OpenAI (GPT-4)</option>
                                <option value="anthropic">Anthropic (Claude)</option>
                            </select>
                            <InputError :message="form.errors.ai_model" />
                        </div>

                        <!-- API Key -->
                        <div class="grid gap-2">
                            <Label for="api_key" class="text-xs">API Key</Label>
                            <Input
                                id="api_key"
                                v-model="form.api_key"
                                type="password"
                                placeholder="Enter your API key"
                                class="h-8 text-xs"
                            />
                            <p class="text-[10px] text-gray-500">
                                Your API key is encrypted and stored securely. Never share your API key.
                            </p>
                            <InputError :message="form.errors.api_key" />
                        </div>

                        <!-- Prompt Template -->
                        <div class="grid gap-2">
                            <div class="flex items-center justify-between">
                                <Label for="prompt_template" class="text-xs">Prompt Template</Label>
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="outline"
                                    class="h-6 px-2 text-[10px]"
                                    @click="loadDefaultPrompt"
                                >
                                    Load Default
                                </Button>
                            </div>
                            <Textarea
                                id="prompt_template"
                                v-model="form.prompt_template"
                                placeholder="Enter your prompt template"
                                class="min-h-[200px] text-xs font-mono"
                            />
                            <p class="text-[10px] text-gray-500">
                                Available placeholders: {project_title}, {project_description}, {project_budget}, {technologies}, {portfolio_items}, {user_name}
                            </p>
                            <InputError :message="form.errors.prompt_template" />
                        </div>

                        <!-- Active Status -->
                        <div class="flex items-center space-x-2">
                            <Checkbox id="is_active" v-model:checked="form.is_active" />
                            <Label for="is_active" class="text-xs font-normal cursor-pointer">
                                Enable AI for auto-bidding proposals
                            </Label>
                        </div>

                        <!-- Info Alert -->
                        <div class="flex items-start gap-2 p-3 bg-blue-50 border border-blue-200 rounded-md">
                            <AlertCircle class="h-4 w-4 text-blue-600 mt-0.5 flex-shrink-0" />
                            <div class="text-xs text-blue-800">
                                <p class="font-semibold mb-1">How it works:</p>
                                <ul class="list-disc list-inside space-y-1 text-[10px]">
                                    <li>AI will generate personalized proposals based on project details</li>
                                    <li>Portfolio items will be automatically included when relevant</li>
                                    <li>Proposals are generated using the configured AI model and prompt template</li>
                                    <li>Make sure your API key has sufficient credits/quota</li>
                                </ul>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end gap-2">
                            <Button
                                type="submit"
                                size="sm"
                                class="h-8 px-4 text-xs bg-[#006AFF] hover:bg-[#0056CC]"
                                :disabled="form.processing"
                            >
                                <Save class="h-3.5 w-3.5 mr-1" />
                                {{ form.processing ? 'Saving...' : 'Save Settings' }}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

