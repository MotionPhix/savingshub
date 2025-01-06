<script setup>
import { Link } from '@inertiajs/vue3'
import { SavingsIcon } from 'lucide-vue-next'

const footerLinks = [
  { label: 'Privacy', href: '/privacy' },
  { label: 'Terms', href: '/terms' },
  { label: 'Contact', href: '/contact' }
]
</script>

<template>
  <footer class="border-t py-6">
    <div class="container flex flex-col items-center justify-between space-y-4 md:h-24 md:flex-row">
      <div class="flex flex-col items-center md:flex-row">
        <Link href="/" class="flex items-center space-x-2">
          <SavingsIcon class="h-6 w-6" />
          <span class="font-bold">Savings Tracker</span>
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

      <p class="text-center text-sm text-muted-foreground md:text-right">
        © {{ new Date().getFullYear() }} Savings Tracker. All rights reserved.
      </p>
    </div>
  </footer>
</template>
