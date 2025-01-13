import { defineStore } from 'pinia'
import { ref } from 'vue'
import { usePage } from '@inertiajs/vue3'

export const useUserStore = defineStore('user', () => {
  const user = ref(usePage().props.auth.user)

  const updateUser = (updatedUser) => {
    user.value = { ...user.value, ...updatedUser }
  }

  const updateAvatar = (avatarUrl) => {
    user.value.avatar = avatarUrl
  }

  return {
    user,
    updateUser,
    updateAvatar
  }
})
