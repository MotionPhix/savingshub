<script setup>
import { Link } from '@inertiajs/vue3'
import { SaveIcon } from 'lucide-vue-next'
import ApplicationLogo from "@/Components/ApplicationLogo.vue";

const footerLinks = [
  { label: 'Privacy', href: '/privacy' },
  { label: 'Terms', href: '/terms' },
  { label: 'Contact', href: '/contact' }
]
</script>

<template>
  <footer>
    <div class="flex flex-col items-center gap-2 justify-between lg:flex-row lg:px-6">

      <div class="flex flex-col items-center md:flex-row lg:gap-8 gap-4">
        <Link href="/" class="flex items-center space-x-2">
          <ApplicationLogo class="h-5 w-5 fill-current" />
          <span class="font-bold text-lg font-headings">
            {{ $page.props.appName }}
          </span>
        </Link>

        <nav class="flex items-center space-x-4 mt-4 md:mt-0 md:ml-4">
          <Link
            v-for="link in footerLinks"
            :key="link.href"
            :href="link.href"
            class="text-sm text-muted-foreground">
            {{ link.label }}
          </Link>
        </nav>
      </div>

      <p class="text-center text-xs text-muted-foreground">
        © {{ `${new Date().getFullYear()} ${$page.props.appName}` }}. All rights reserved.
      </p>

    </div>
  </footer>
</template>
