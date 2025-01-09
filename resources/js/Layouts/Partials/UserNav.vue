<script setup>
import {router, usePage} from '@inertiajs/vue3'
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
import {
  AvatarImage,
  Avatar,
  AvatarFallback
} from "@/Components/ui/avatar";

const props = defineProps({
  defaultAvatar: {
    type: String,
    default: '/default-avatar.png'
  }
})

const {user} = usePage().props.auth
</script>

<template>

  <DropdownMenu :modal="false">
    <DropdownMenuTrigger>
      <div class="flex items-center space-x-2">
        <Avatar>
          <AvatarImage
            :src="user.avatar || defaultAvatar"
            :alt="user.name"/>

          <AvatarFallback>HG</AvatarFallback>
        </Avatar>

        <span class="hidden md:block">
            {{ user.name }}
          </span>
      </div>
    </DropdownMenuTrigger>

    <DropdownMenuContent align="start">
      <DropdownMenuItem
        @click="router.visit(route('profile.edit'), { replace: true })">
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

      <DropdownMenuItem
        :href="route('logout')"
        method="post"
        as="button">
        <PowerIcon class="w-4 h-4 mr-2"/>
        Log Out
      </DropdownMenuItem>
    </DropdownMenuContent>
  </DropdownMenu>

</template>
