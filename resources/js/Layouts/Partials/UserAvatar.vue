<script setup lang="ts">
import {
  Avatar,
  AvatarImage,
  AvatarFallback
} from "@/Components/ui/avatar";
import { useUserStore } from '@/stores/user'
import { storeToRefs } from 'pinia'
import { computed } from "vue";

const props = withDefaults(
  defineProps<{
    size?: number,
    src?: string,
    alt?: string,
    fallback: string
  }>(), {
    size: 10
  })

const userStore = useUserStore()
const { user } = storeToRefs(userStore)

const avatarSize = computed(() => {
  return `h-${props.size} w-${props.size}`
})

const avatarSource = computed(() => {
  // Priority:
  // 1. Provided src (for preview)
  // 2. User's current avatar
  // 3. Default avatar based on gender
  if (props.src) {
    return props.src
  }

  return user.value.avatar
    ? user.value.avatar
    : user.value.gender === 'male'
      ? '/default-m-avatar.png'
      : '/default-f-avatar.png'
})

const avatarAlt = computed(() => {
  if (props.alt) {
    return props.alt
  }

  return user.value.name
})
</script>

<template>
  <Avatar
    :class="
      user.gender === 'male' || user.gender === null
      ? `bg-amber-300 ${avatarSize}`
      : `bg-blue-600 ${avatarSize}`
    ">
    <AvatarImage
      :src="avatarSource"
      :alt="avatarAlt"/>

    <AvatarFallback>
      {{ fallback }}
    </AvatarFallback>
  </Avatar>
</template>
