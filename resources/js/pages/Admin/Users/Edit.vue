<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { route } from '@/lib/utils';
import { Users } from 'lucide-vue-next';

const props = defineProps<{
    user: {
        id: number;
        name: string;
        username: string;
        email: string;
    };
    subscriptions: Array<{
        id: number;
        name: string;
        price: number;
        bid_limit: number;
    }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Users',
        href: route('admin.users.index'),
    },
    {
        title: 'Edit User',
        href: route('admin.users.edit', { id: props.user.id }),
    },
];

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    username: props.user.username || '',
});

function submit() {
    form.put(route('admin.users.update', { id: props.user.id }), {
        onSuccess: () => {
            router.visit(route('admin.users.index'));
        },
    });
}
</script>

<template>
    <Head title="Edit User" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="flex items-center gap-3">
                <Users class="h-6 w-6 text-[#006AFF]" />
                <h1 class="text-2xl font-bold">Edit User</h1>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>User Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-4">
                        <div class="space-y-2">
                            <Label for="name">Name</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                required
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="email">Email</Label>
                            <Input
                                id="email"
                                type="email"
                                v-model="form.email"
                                required
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="username">Username</Label>
                            <Input
                                id="username"
                                v-model="form.username"
                            />
                        </div>
                        <div class="flex gap-2">
                            <Button type="submit" :disabled="form.processing" size="sm">
                                Update User
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                @click="router.visit(route('admin.users.index'))"
                            >
                                Cancel
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

