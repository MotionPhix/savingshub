<script setup lang="ts">
import {router, usePage, Link} from '@inertiajs/vue3'
import {
  UserIcon,
  UsersIcon,
  ShieldCheckIcon,
  CreditCardIcon,
  PowerIcon
} from 'lucide-vue-next'
import {
  DropdownMenu,
  DropdownMenuItem,
  DropdownMenuTrigger,
  DropdownMenuContent,
  DropdownMenuSeparator
} from "@/Components/ui/dropdown-menu";
import UserAvatar from "@/Layouts/Partials/UserAvatar.vue";
import {useInitials} from '@/composables/useInitials'

const {user} = usePage().props.auth
const {getInitials} = useInitials()
</script>

<template>

  <DropdownMenu :modal="false">
    <DropdownMenuTrigger>
      <div class="flex items-center space-x-2">
        <UserAvatar
          :size="8"
          :fallback="getInitials(user.name)" />

        <span class="hidden md:block">
            {{ user.name }}
          </span>
      </div>
    </DropdownMenuTrigger>

    <DropdownMenuContent align="end">
      <DropdownMenuItem
        @click="router.visit(route('profile.index'), { replace: true })">
        <UserIcon class="w-4 h-4 mr-2"/>
        Profile
      </DropdownMenuItem>

      <DropdownMenuItem :href="route('groups.index')">
        <UsersIcon class="w-4 h-4 mr-2"/>
        My Groups
      </DropdownMenuItem>

      <DropdownMenuSeparator />

      <DropdownMenuItem
        v-if="user.is_admin"
        href="#">
        <ShieldCheckIcon class="w-4 h-4 mr-2"/>
        Admin Dashboard
      </DropdownMenuItem>

      <DropdownMenuItem
        href="#">
        <CreditCardIcon class="w-4 h-4 mr-2"/>
        Subscription
      </DropdownMenuItem>

      <DropdownMenuSeparator />

      <DropdownMenuItem>
        <Link
          as="button"
          method="delete"
          class="w-full flex items-center gap-2"
          :href="route('logout')">
          <PowerIcon class="w-4 h-4 mr-2"/>
          Log Out
        </Link>
      </DropdownMenuItem>
    </DropdownMenuContent>
  </DropdownMenu>

</template>
