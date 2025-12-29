<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { route } from '@/lib/utils';
import { Briefcase, Plus, Edit, Trash2, ExternalLink, Image as ImageIcon } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    portfolios: Array<{
        id: number;
        title: string;
        description: string | null;
        url: string | null;
        image_url: string | null;
        technologies: string[] | null;
        order: number;
        is_active: boolean;
    }>;
    technologies: Array<{
        id: number;
        name: string;
    }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: route('user.dashboard'),
    },
    {
        title: 'Portfolio',
        href: route('user.portfolio.index'),
    },
];

const isDialogOpen = ref(false);
const editingPortfolio = ref<any>(null);

const form = useForm({
    url: '',
});

function openCreateDialog() {
    editingPortfolio.value = null;
    form.reset();
    form.clearErrors();
    isDialogOpen.value = true;
}

function openEditDialog(portfolio: any) {
    editingPortfolio.value = portfolio;
    form.url = portfolio.url || '';
    form.clearErrors();
    isDialogOpen.value = true;
}

function submitForm() {
    if (editingPortfolio.value) {
        form.put(route('user.portfolio.update', editingPortfolio.value.id), {
            onSuccess: () => {
                isDialogOpen.value = false;
                editingPortfolio.value = null;
                form.reset();
            },
        });
    } else {
        form.post(route('user.portfolio.store'), {
            onSuccess: () => {
                isDialogOpen.value = false;
                form.reset();
            },
        });
    }
}

function deletePortfolio(id: number) {
    if (confirm('Are you sure you want to delete this portfolio item?')) {
        router.delete(route('user.portfolio.destroy', id));
    }
}

function toggleActive(portfolio: any) {
    router.put(route('user.portfolio.update', portfolio.id), {
        ...portfolio,
        is_active: !portfolio.is_active,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Portfolio" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-3 overflow-x-auto rounded-lg p-3 bg-gray-50">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <Briefcase class="h-5 w-5 text-[#006AFF]" />
                    <h1 class="text-xl font-bold">My Portfolio</h1>
                </div>
                <Dialog v-model:open="isDialogOpen">
                    <DialogTrigger as-child>
                        <Button size="sm" class="h-7 px-3 text-xs bg-[#006AFF] hover:bg-[#0056CC]" @click="openCreateDialog">
                            <Plus class="h-3 w-3 mr-1" />
                            Add Portfolio
                        </Button>
                    </DialogTrigger>
                    <DialogContent class="max-w-2xl max-h-[90vh] overflow-y-auto">
                        <DialogHeader>
                            <DialogTitle class="text-sm">
                                {{ editingPortfolio ? 'Edit Portfolio' : 'Add Portfolio' }}
                            </DialogTitle>
                        </DialogHeader>
                        <form @submit.prevent="submitForm" class="space-y-3">
                            <div class="space-y-1">
                                <Label for="url" class="text-xs">Portfolio URL *</Label>
                                <Input 
                                    id="url" 
                                    v-model="form.url" 
                                    type="url" 
                                    required
                                    class="h-8 text-xs" 
                                    placeholder="https://example.com or https://github.com/username/project" 
                                />
                                <p class="text-[10px] text-gray-500 mt-1">
                                    Enter the URL of your portfolio project. Technologies will be automatically detected from the URL.
                                </p>
                                <p v-if="form.errors.url" class="text-xs text-red-600">{{ form.errors.url }}</p>
                            </div>

                            <div class="p-2 bg-blue-50 border border-blue-200 rounded text-xs text-blue-800">
                                <p class="font-semibold mb-1">💡 Auto-detection:</p>
                                <ul class="list-disc list-inside space-y-0.5 text-[10px]">
                                    <li>Technologies will be automatically detected from the URL</li>
                                    <li>Title will be generated from the domain name</li>
                                    <li>Portfolio will be included in auto-bidding proposals when relevant</li>
                                </ul>
                            </div>

                            <Button type="submit" size="sm" :disabled="form.processing" class="h-7 px-3 text-xs w-full bg-[#006AFF] hover:bg-[#0056CC]">
                                {{ form.processing ? 'Processing...' : (editingPortfolio ? 'Update Portfolio' : 'Add Portfolio') }}
                            </Button>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <!-- Portfolio Items -->
            <div v-if="portfolios.length > 0" class="grid gap-2 md:grid-cols-2 lg:grid-cols-3">
                <Card
                    v-for="portfolio in portfolios"
                    :key="portfolio.id"
                    class="p-3 hover:shadow-md transition-shadow"
                    :class="{ 'opacity-60': !portfolio.is_active }"
                >
                    <CardContent class="p-0">
                        <div class="space-y-2">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <h3 class="font-semibold text-sm text-gray-900 mb-1">{{ portfolio.title }}</h3>
                                    <p v-if="portfolio.description" class="text-xs text-gray-600 line-clamp-2">
                                        {{ portfolio.description }}
                                    </p>
                                </div>
                                <span
                                    class="text-[10px] px-1.5 py-0.5 rounded"
                                    :class="portfolio.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
                                >
                                    {{ portfolio.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>

                            <div v-if="portfolio.image_url" class="aspect-video bg-gray-100 rounded overflow-hidden">
                                <img :src="portfolio.image_url" :alt="portfolio.title" class="w-full h-full object-cover" />
                            </div>

                            <div v-if="portfolio.technologies && portfolio.technologies.length > 0" class="flex flex-wrap gap-1">
                                <span
                                    v-for="tech in portfolio.technologies"
                                    :key="tech"
                                    class="text-[10px] px-1.5 py-0.5 bg-blue-100 text-blue-800 rounded"
                                >
                                    {{ tech }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between pt-2 border-t">
                                <div class="flex gap-1">
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        class="h-6 px-2 text-xs"
                                        @click="openEditDialog(portfolio)"
                                    >
                                        <Edit class="h-3 w-3" />
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="destructive"
                                        class="h-6 px-2 text-xs"
                                        @click="deletePortfolio(portfolio.id)"
                                    >
                                        <Trash2 class="h-3 w-3" />
                                    </Button>
                                </div>
                                <div class="flex gap-1">
                                    <a
                                        v-if="portfolio.url"
                                        :href="portfolio.url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="text-[#006AFF] hover:text-[#0056CC]"
                                    >
                                        <ExternalLink class="h-3 w-3" />
                                    </a>
                                    <Button
                                        size="sm"
                                        variant="ghost"
                                        class="h-6 px-2 text-xs"
                                        @click="toggleActive(portfolio)"
                                    >
                                        {{ portfolio.is_active ? 'Deactivate' : 'Activate' }}
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div v-else class="text-center py-12">
                <Briefcase class="h-12 w-12 text-gray-400 mx-auto mb-3" />
                <p class="text-sm text-gray-600 mb-4">No portfolio items yet</p>
                <Button size="sm" class="h-7 px-3 text-xs bg-[#006AFF] hover:bg-[#0056CC]" @click="openCreateDialog">
                    <Plus class="h-3 w-3 mr-1" />
                    Add Your First Portfolio
                </Button>
            </div>
        </div>
    </AppLayout>
</template>

