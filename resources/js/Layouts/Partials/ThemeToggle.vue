<template>
  <DropdownMenu>
    <DropdownMenuTrigger asChild>
      <Button variant="ghost" size="icon">
        <SunIcon
          v-if="theme === 'light'"
          class="h-[1.2rem] w-[1.2rem] rotate-0 scale-100 transition-all dark:-rotate-90 dark:scale-0" />
        <MoonIcon
          v-if="theme === 'dark'"
          class="h-[1.2rem] w-[1.2rem] rotate-90 scale-0 transition-all dark:rotate-0 dark:scale-100"
        />
      </Button>
    </DropdownMenuTrigger>
    <DropdownMenuContent>
      <DropdownMenuItem @click="toggleTheme('light')">Light Mode</DropdownMenuItem>
      <DropdownMenuItem @click="toggleTheme('dark')">Dark Mode</DropdownMenuItem>
    </DropdownMenuContent>
  </DropdownMenu>
</template>

<script setup>
import { ref } from 'vue'
import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuTrigger,
  DropdownMenuContent,
  DropdownMenuItem
} from '@/components/ui/dropdown'
import { SunIcon, MoonIcon } from 'lucide-vue-next'

const theme = ref('light')

const toggleTheme = (selectedTheme) => {
  theme.value = selectedTheme
  document.documentElement.setAttribute('data-theme', selectedTheme)
}
</script>
