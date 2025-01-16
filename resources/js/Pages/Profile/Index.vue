<script setup lang="ts">
import {ref} from 'vue'
import {router, useForm} from '@inertiajs/vue3'
import {Card, CardContent, CardDescription, CardHeader, CardTitle} from '@/Components/ui/card'
import {Input} from '@/Components/ui/input'
import {Label} from '@/Components/ui/label'
import {Button} from '@/Components/ui/button'
import {useInitials} from "@/composables/useInitials";
import AppLayout from "@/Layouts/AppLayout.vue";
import FormField from "@/Components/Forms/FormField.vue";
import {TrashIcon} from "lucide-vue-next";
import UserAvatar from "@/Layouts/Partials/UserAvatar.vue";
import {toast} from "vue-sonner";
import imageCompression from "browser-image-compression";
import {useUserStore} from "@/stores/user";

const props = defineProps<{
  user: {
    id: number
    uuid: string
    name: string
    email: string
    phone_number?: string
    gender?: string
    bio?: string
    timezone?: string
    locale?: string
    avatar?: string
  }
}>()

const {getInitials} = useInitials()

const form = useForm({
  name: props.user.name,
  email: props.user.email,
  phone_number: props.user.phone_number || '',
  gender: props.user.gender || '',
  bio: props.user.bio || '',
  timezone: props.user.timezone ?? 'cat',
  locale: props.user.locale || 'en',
  avatar: props.user.avatar || null
})

const MAX_FILE_SIZE = 10 * 1024 * 1024 // 10MB
const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp']
const previewAvatar = ref(null)
const isCompressing = ref(false)

const userStore = useUserStore()

// State for preview and compression

const handleAvatarUpload = (event) => {
  const file = event.target.files[0]

  // Check file type
  if (!ALLOWED_TYPES.includes(file.type)) {
    toast.error('Invalid file type', {
      description: 'Please upload a valid image (JPEG, PNG, GIF, WebP)'
    })
    event.target.value = null // Clear the input
    return
  }

  // Check file size
  if (file.size > MAX_FILE_SIZE) {
    toast.error('File too large', {
      description: `Image must be smaller than ${MAX_FILE_SIZE / (1024 * 1024)}MB`
    })
    event.target.value = null // Clear the input
    return
  }

  // Validate image dimensions (optional)
  const img = new Image()
  img.onload = () => {
    // Optional: Check image dimensions
    if (img.width > 3000 || img.height > 3000) {
      toast.error('Image too large', {
        description: 'Image dimensions should not exceed 3000x3000 pixels'
      })
      event.target.value = null
      return
    }

    // If all checks pass
    form.avatar = file

    // Create preview
    const reader = new FileReader()
    reader.onload = (e) => {
      previewAvatar.value = e.target.result
    }
    reader.readAsDataURL(file)
  }
  img.src = URL.createObjectURL(file)
}

// Image compression method (optional but recommended)
const compressImage = async (file, maxSizeInMB = 1) => {
  const options = {
    maxSizeMB: maxSizeInMB,
    maxWidthOrHeight: 1920,
    useWebWorker: true
  }

  try {
    return await imageCompression(file, options)
  } catch (error) {
    console.error('Image compression error', error)
    return file
  }
}

const removeAvatar = () => {
  router.delete(route('profile.avatar.destroy'), {
    preserveScroll: true,
    onSuccess: () => {
      // Update user store
      userStore.updateUser({
        ...props.user,
        avatar: null
      })

      toast.info('Avatar Removed', {
        description: 'Your avatar was removed. The default avatar will be used',
      })

      previewAvatar.value = null
      form.reset('avatar')
    },
    onError: (error) => {
      toast.error('Failed to remove avatar', {
        description: error.message || 'An unexpected error occurred'
      })

      form.reset('avatar')
    }
  })
}

const updateProfile = async () => {
  form.clearErrors()
  isCompressing.value = true

  try {
    if (form.avatar as any instanceof File) {
      form.avatar = await compressImage(form.avatar)
    }

    form
      .transform((data) => {
        return {
          name: data.name,
          email: data.email,
          timezone: data.timezone ?? 'cat',
          locale: data.locale || 'en',
          _method: 'patch',
          // Only include non-null values
          ...(data.phone_number && { phone_number: data.phone_number }),
          ...(data.gender && { gender: data.gender }),
          ...(data.bio && { bio: data.bio }),
        }
      })
      .post(route('profile.update', props.user.uuid), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
          // Update user store
          userStore.updateUser({
            ...props.user,
          })

          previewAvatar.value = null
          isCompressing.value = false

          form.reset('avatar')

          toast.success('Profile updated successfully')
        },
        onError: (errors) => {
          isCompressing.value = false

          Object.entries(errors).forEach(([field, error]) => {
            toast.error(`${field.charAt(0).toUpperCase() + field.slice(1)}: ${error}`)
          })
        }
      })
  } catch (error) {
    isCompressing.value = false
    toast.error('Profile update failed', {
      description: error instanceof Error ? error.message : 'An unexpected error occurred'
    })
  }
}

const timezones = [
  {
    label: 'North America',
    items: [
      {value: 'est', label: 'Eastern Standard Time (EST)'},
      {value: 'cst', label: 'Central Standard Time (CST)'},
      {value: 'mst', label: 'Mountain Standard Time (MST)'},
      {value: 'pst', label: 'Pacific Standard Time (PST)'},
      {value: 'akst', label: 'Alaska Standard Time (AKST)'},
      {value: 'hst', label: 'Hawaii Standard Time (HST)'}
    ]
  },
  {
    label: 'Europe & Africa',
    items: [
      {value: 'gmt', label: 'Greenwich Mean Time (GMT)'},
      {value: 'cet', label: 'Central European Time (CET)'},
      {value: 'eet', label: 'Eastern European Time (EET)'},
      {value: 'west', label: 'Western European Summer Time (WEST)'},
      {value: 'cat', label: 'Central Africa Time (CAT)'},
      {value: 'eat', label: 'East Africa Time (EAT)'}
    ]
  },
  {
    label: 'Asia',
    items: [
      {value: 'msk', label: 'Moscow Time (MSK)'},
      {value: 'ist', label: 'India Standard Time (IST)'},
      {value: 'cst_china', label: 'China Standard Time (CST)'},
      {value: 'jst', label: 'Japan Standard Time (JST)'},
      {value: 'kst', label: 'Korea Standard Time (KST)'},
      {value: 'ist_indonesia', label: 'Indonesia Central Standard Time (WITA)'}
    ]
  },
  {
    label: 'EAustralia & Pacific',
    items: [
      {value: 'acst', label: 'Australian Western Standard Time (AWST)'},
      {value: 'aest', label: 'Australian Eastern Standard Time (AEST)'},
      {value: 'nzst', label: 'New Zealand Standard Time (NZST)'},
      {value: 'fjt', label: 'Fiji Time (FJT)'}
    ]
  },
  {
    label: 'South America',
    items: [
      {value: 'art', label: 'Argentina Time (ART)'},
      {value: 'bot', label: 'Bolivia Time (BOT)'},
      {value: 'brt', label: 'NBrasilia Time (BRT)'},
      {value: 'clt', label: 'Chile Standard Time (CLT)'}
    ]
  }
]

const locales = [
  {value: 'en', label: 'English'},
  {value: 'fr', label: 'Français'},
]
</script>

<template>
  <AppLayout>
    <div class="mx-auto sm:px-4 py-8">
      <Card class="max-w-2xl mx-auto bg-background">
        <CardHeader>
          <CardTitle class="text-foreground">Profile Settings</CardTitle>
          <CardDescription class="text-muted-foreground">
            Manage your profile information and preferences
          </CardDescription>
        </CardHeader>

        <CardContent>
          <div class="grid gap-6">
            <!-- Avatar Upload -->
            <div class="flex flex-col sm:flex-row items-center sm:space-x-6">
              <UserAvatar
                :size="48"
                :src="previewAvatar"
                :alt="`${user.name}'s profile avatar`"
                :fallback="getInitials(user.name)"
              />

              <div class="text-center mt-4 sm:mt-0 sm:text-left relative w-full">
                <Label
                  for="avatar"
                  class="text-foreground"
                >
                  Change Avatar
                </Label>
                <Input
                  id="avatar"
                  type="file"
                  accept="image/jpeg,image/png,image/gif,image/webp"
                  @change="handleAvatarUpload"
                  class="mt-2 file:bg-primary file:text-primary-foreground hover:file:bg-primary/90"
                />

                <!-- Image Preview with Compression Info -->
                <div v-if="previewAvatar" class="text-center absolute w-full">
                  <p class="text-xs text-muted-foreground mt-1 mb-6">
                    Image will be automatically optimized before upload
                  </p>
                </div>

                <!-- Compression loading state -->
                <div v-if="isCompressing" class="mb-4 absolute w-full text-center">
                  <p class="text-sm text-muted-foreground">
                    Compressing image...
                  </p>
                </div>

                <Button
                  size="icon"
                  v-if="user.avatar"
                  variant="destructive"
                  class="absolute bottom-0 right-0 rounded-s-none"
                  @click="removeAvatar">
                  <TrashIcon/>
                </Button>
              </div>
            </div>

            <!-- Profile Details Form -->
            <div class="grid md:grid-cols-2 gap-4">
              <div>
                <FormField
                  label="Name"
                  v-model="form.name"
                  :error="form.errors.name"
                />
              </div>

              <div>
                <FormField
                  type="email"
                  label="Email"
                  v-model="form.email"
                  :error="form.errors.email"
                />
              </div>

              <div>
                <FormField
                  label="Phone Number"
                  v-model="form.phone_number"
                  placeholder="Enter your phone number"
                  :error="form.errors.phone_number"
                  type="tel"
                />
              </div>

              <div>
                <FormField
                  label="Gender"
                  placeholder="Select Gender"
                  :error="form.errors.gender"
                  v-model="form.gender"
                  type="select"
                  :options="[
                    { label: 'Male', value: 'male' },
                    { label: 'Female', value: 'female' }
                  ]"
                />
              </div>

              <div class="md:col-span-2">
                <FormField
                  label="Bio"
                  placeholder="Write a few things about yourself here."
                  v-model="form.bio"
                  type="textarea"
                />
              </div>

              <div>
                <FormField
                  :has-groups="true"
                  placeholder="Select a timezone"
                  label="Timezone"
                  :options="timezones"
                  v-model="form.timezone"
                  type="select"
                />
              </div>

              <div>
                <FormField
                  placeholder="Select Language"
                  label="Language"
                  :options="locales"
                  v-model="form.locale"
                  type="select"
                />
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end space-x-4">
              <Button
                variant="outline"
                @click="$inertia.get(route('dashboard'))"
                class="hover:bg-accent hover:text-accent-foreground">
                Cancel
              </Button>

              <Button
                @click="updateProfile"
                :disabled="form.processing"
                class="bg-primary text-primary-foreground hover:bg-primary/90">
                Save Changes
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
