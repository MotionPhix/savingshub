<script setup lang="ts">
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import {Head, Link, useForm} from '@inertiajs/vue3';
import FormField from "@/Components/Forms/FormField.vue";
import {Button} from "@/Components/ui/button";
import PageHeader from "@/Components/PageHeader.vue";
import {Label} from "@/Components/ui/label";

defineProps<{
  canResetPassword: boolean
  flush?: string
  invitationToken?: string
  invitationEmail?: string
  invitationGroup?: string
}>();

const form = useForm({
  email: '',
  password: '',
  remember: false,
});

const submit = () => {
  form.post(route('login'), {
    onFinish: () => form.reset('password'),
  });
};
</script>

<template>
  <GuestLayout>
    <Head title="Log in"/>

    <div v-if="flush" class="mb-4 text-sm font-medium text-green-600">
      {{ flush }}
    </div>

    <div
      v-if="invitationToken"
      class="mb-4 bg-blue-100 border-l-4 border-blue-500 p-4">

      <p class="font-bold">Group Invitation</p>

      <p>You're logging in to accept an invitation to join
        <span class="font-semibold">
          {{ invitationGroup }}
        </span>
      </p>

      <p class="text-sm text-muted-foreground">
        This invitation will be processed after login
      </p>

    </div>

    <PageHeader>
        Login

      <template #description>
        Enter your email below to login to your account
      </template>
    </PageHeader>

    <form @submit.prevent="submit">
      <div>
        <FormField
          label="Email"
          type="email"
          v-model="form.email"
          placeholder="Enter your email"
          :error="form.errors.email"
          required
          autofocus
        />
      </div>

      <div class="mt-4">
        <div class="flex items-center">
          <Label for="password">Password</Label>

          <Link
            as="button"
            v-if="canResetPassword"
            :href="route('password.request')"
            class="ml-auto inline-block text-sm underline">
            Forgot your password?
          </Link>
        </div>

        <FormField
          type="password"
          v-model="form.password"
          :error="form.errors.password"
          placeholder="Enter your password"
        />
      </div>

      <div class="mt-4 block">
        <label class="flex items-center">
          <Checkbox name="remember" v-model:checked="form.remember"/>
          <span
            class="ms-2 text-sm text-gray-600 dark:text-gray-400">
            Remember me
          </span>
        </label>
      </div>

      <div class="mt-4 flex items-center justify-end">
        <div class="text-center text-sm">
          Don't have an account?
          <Link as="button" :href="route('register')" class="underline">
            Sign up
          </Link>
        </div>

        <Button
          class="ms-4"
          :class="{ 'opacity-25': form.processing }"
          :disabled="form.processing">
          Log in
        </Button>
      </div>
    </form>
  </GuestLayout>
</template>
