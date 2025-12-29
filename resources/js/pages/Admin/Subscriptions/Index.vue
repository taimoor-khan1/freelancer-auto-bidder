<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { route } from '@/lib/utils';
import { Package } from 'lucide-vue-next';

defineProps<{
    subscriptions: Array<{
        id: number;
        name: string;
        description: string | null;
        price: number;
        bid_limit: number;
        is_active: boolean;
        created_at: string;
    }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Subscriptions',
        href: route('admin.subscriptions.index'),
    },
];

const isCreateDialogOpen = ref(false);
const isEditDialogOpen = ref(false);
const editingSubscription = ref<any>(null);

const createForm = useForm({
    name: '',
    description: '',
    price: 0,
    bid_limit: 0,
    is_active: true,
});

const editForm = useForm({
    name: '',
    description: '',
    price: 0,
    bid_limit: 0,
    is_active: true,
});

function openEditDialog(subscription: any) {
    editingSubscription.value = subscription;
    editForm.name = subscription.name;
    editForm.description = subscription.description || '';
    editForm.price = subscription.price;
    editForm.bid_limit = subscription.bid_limit;
    editForm.is_active = subscription.is_active;
    isEditDialogOpen.value = true;
}

function submitCreate() {
    createForm.post(route('admin.subscriptions.store'), {
        onSuccess: () => {
            isCreateDialogOpen.value = false;
            createForm.reset();
        },
    });
}

function submitEdit() {
    if (editingSubscription.value) {
        editForm.put(route('admin.subscriptions.update', { id: editingSubscription.value.id }), {
            onSuccess: () => {
                isEditDialogOpen.value = false;
                editingSubscription.value = null;
                editForm.reset();
            },
        });
    }
}

function deleteSubscription(id: number) {
    if (confirm('Are you sure you want to delete this subscription?')) {
        router.delete(route('admin.subscriptions.destroy', { id }));
    }
}
</script>

<template>
    <Head title="Subscriptions" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-3 overflow-x-auto rounded-lg p-3 bg-gray-50">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <Package class="h-5 w-5 text-[#006AFF]" />
                    <h1 class="text-xl font-bold">Subscriptions</h1>
                </div>
                <Dialog v-model:open="isCreateDialogOpen">
                    <DialogTrigger as-child>
                        <Button size="sm" class="h-7 px-3 text-xs bg-[#006AFF] hover:bg-[#0056CC]">Create</Button>
                    </DialogTrigger>
                    <DialogContent class="max-w-md">
                        <DialogHeader>
                            <DialogTitle class="text-sm">Create Subscription</DialogTitle>
                        </DialogHeader>
                        <form @submit.prevent="submitCreate" class="space-y-3">
                            <div class="space-y-1">
                                <Label for="name" class="text-xs">Name</Label>
                                <Input id="name" v-model="createForm.name" required class="h-8 text-xs" />
                            </div>
                            <div class="space-y-1">
                                <Label for="description" class="text-xs">Description</Label>
                                <Input id="description" v-model="createForm.description" class="h-8 text-xs" />
                            </div>
                            <div class="space-y-1">
                                <Label for="price" class="text-xs">Price</Label>
                                <Input id="price" type="number" step="0.01" v-model="createForm.price" required class="h-8 text-xs" />
                            </div>
                            <div class="space-y-1">
                                <Label for="bid_limit" class="text-xs">Bid Limit</Label>
                                <Input id="bid_limit" type="number" v-model="createForm.bid_limit" required class="h-8 text-xs" />
                            </div>
                            <div class="flex items-center space-x-2">
                                <Checkbox id="is_active" v-model:checked="createForm.is_active" />
                                <Label for="is_active" class="text-xs">Active</Label>
                            </div>
                            <Button type="submit" size="sm" :disabled="createForm.processing" class="h-7 px-3 text-xs">Create</Button>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <div class="grid gap-2 md:grid-cols-2 lg:grid-cols-3">
                <Card v-for="subscription in subscriptions" :key="subscription.id" class="p-3 hover:shadow transition-shadow">
                    <CardHeader class="p-2 pb-2">
                        <CardTitle class="flex items-center justify-between text-sm">
                            <span>{{ subscription.name }}</span>
                            <span
                                class="text-[10px] px-1.5 py-0.5 rounded"
                                :class="subscription.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
                            >
                                {{ subscription.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="p-2 pt-0 space-y-1.5">
                        <p class="text-xs text-muted-foreground">{{ subscription.description }}</p>
                        <div class="flex justify-between text-xs">
                            <span>Price:</span>
                            <span class="font-semibold">${{ subscription.price }}</span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span>Bid Limit:</span>
                            <span class="font-semibold">{{ subscription.bid_limit }}</span>
                        </div>
                        <div class="flex gap-1.5 mt-2">
                            <Button size="sm" variant="outline" class="h-6 px-2 text-xs flex-1" @click="openEditDialog(subscription)">Edit</Button>
                            <Button size="sm" variant="destructive" class="h-6 px-2 text-xs flex-1" @click="deleteSubscription(subscription.id)">Delete</Button>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Dialog v-model:open="isEditDialogOpen">
                <DialogContent class="max-w-md">
                    <DialogHeader>
                        <DialogTitle class="text-sm">Edit Subscription</DialogTitle>
                    </DialogHeader>
                    <form @submit.prevent="submitEdit" class="space-y-3">
                        <div class="space-y-1">
                            <Label for="edit_name" class="text-xs">Name</Label>
                            <Input id="edit_name" v-model="editForm.name" required class="h-8 text-xs" />
                        </div>
                        <div class="space-y-1">
                            <Label for="edit_description" class="text-xs">Description</Label>
                            <Input id="edit_description" v-model="editForm.description" class="h-8 text-xs" />
                        </div>
                        <div class="space-y-1">
                            <Label for="edit_price" class="text-xs">Price</Label>
                            <Input id="edit_price" type="number" step="0.01" v-model="editForm.price" required class="h-8 text-xs" />
                        </div>
                        <div class="space-y-1">
                            <Label for="edit_bid_limit" class="text-xs">Bid Limit</Label>
                            <Input id="edit_bid_limit" type="number" v-model="editForm.bid_limit" required class="h-8 text-xs" />
                        </div>
                        <div class="flex items-center space-x-2">
                            <Checkbox id="edit_is_active" v-model:checked="editForm.is_active" />
                            <Label for="edit_is_active" class="text-xs">Active</Label>
                        </div>
                        <Button type="submit" size="sm" :disabled="editForm.processing" class="h-7 px-3 text-xs">Update</Button>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
