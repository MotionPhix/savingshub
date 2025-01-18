<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import {Head, Link, useForm} from '@inertiajs/vue3';
import {Button} from "@/Components/ui/button";
import FormField from "@/Components/Forms/FormField.vue";

const props = defineProps<{
  invitationToken?: string
}>()

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
});

const submit = () => {
  form
    .transform(data => {
      const user = {
        ...data
      }

      if (props.invitationToken) user.invitation_token = props.invitationToken

      return user
    })
    .post(props.invitationToken ? route('register.with_invitation') : route('register'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  });
};
</script>

<template>
  <GuestLayout>
    <Head title="Register"/>

    <form @submit.prevent="submit">
      <div>
        <FormField
          label="Name"
          v-model="form.name"
          :error="form.errors.name"
          placeholder="Enter your name"
          autofocus
          required
        />
      </div>

      <div class="mt-4">
        <FormField
          type="email"
          label="Email"
          :error="form.errors.email"
          placeholder="Enter your email"
          v-model="form.email"
          required
        />
      </div>

      <div class="mt-4">
        <FormField
          label="Password"
          type="password"
          :error="form.errors.password"
          placeholder="Enter your password"
          v-model="form.password"
          required
        />
      </div>

      <div class="mt-4">
        <FormField
          type="password"
          label="Confirm Password"
          placeholder="Confirm your password"
          v-model="form.password_confirmation"
          required
        />
      </div>

      <div class="mt-4 flex items-center justify-end">
        <Link
          as="button"
          :href="route('login')"
          class="hover:text-muted duration-500 transition-opacity text-sm">
          Already registered?
        </Link>

        <Button
          class="ms-4"
          :class="{ 'opacity-25': form.processing }"
          :disabled="form.processing">
          Register
        </Button>
      </div>
    </form>
  </GuestLayout>
</template>
