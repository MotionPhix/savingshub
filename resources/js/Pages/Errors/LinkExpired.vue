<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { Button } from '@/Components/ui/button'
import { AlertTriangleIcon } from 'lucide-vue-next'

const props = defineProps({
  message: {
    type: String,
    default: 'The invitation link has expired or is invalid.'
  },
  status: {
    type: Number,
    default: 403
  }
})

const goToHome = () => {
  router.visit(route('dashboard'))
}

const contactSupport = () => {
  // Implement support contact logic
  // Could open a modal, redirect to support page, etc.
  window.location.href = 'mailto:support@yourapp.com'
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-background">
    <div class="text-center">
      <div class="mb-6">
        <AlertTriangleIcon class="mx-auto h-16 w-16 text-destructive" />
      </div>

      <h1 class="text-2xl font-bold text-foreground mb-4">
        Link Expired
      </h1>

      <p class="text-muted-foreground mb-6">
        {{ message }}
      </p>

      <div class="flex justify-center space-x-4">
        <Button
          variant="default"
          @click="goToHome"
        >
          Go to Home
        </Button>

        <Button
          variant="secondary"
          @click="contactSupport"
        >
          Contact Support
        </Button>
      </div>
    </div>
  </div>
</template>
