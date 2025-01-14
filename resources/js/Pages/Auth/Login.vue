<script setup lang="ts">
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import {Head, Link, useForm} from '@inertiajs/vue3';
import FormField from "@/Components/Forms/FormField.vue";
import {Button} from "@/Components/ui/button";

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
        <FormField
          type="password"
          v-model="form.password"
          :error="form.errors.password"
          placeholder="Enter your password"
          label="Password"
          required
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
        <Link
          as="button"
          v-if="canResetPassword"
          :href="route('password.request')"
          class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:text-gray-400 dark:hover:text-gray-100 dark:focus:ring-offset-gray-800">
          Forgot your password?
        </Link>

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
