<script setup>
import {ref, computed} from 'vue'
import {router} from '@inertiajs/vue3'
import {toast} from 'vue-sonner'
import {Button} from '@/Components/ui/button'
import {Label} from '@/Components/ui/label'
import {Textarea} from '@/Components/ui/textarea'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue
} from '@/Components/ui/select'
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle
} from "@/Components/ui/card/index.js";
import FormField from "@/Components/Forms/FormField.vue";

const props = defineProps({
  group: {
    type: Object,
    required: true
  }
})

const emailInput = ref('')
const invitationMessage = ref('')
const selectedRole = ref('member')
const processing = ref(false)

// Validate email addresses
const isValidEmails = computed(() => {
  const emails = emailInput.value
    .split(/[\n,]+/)
    .map(email => email.trim())
    .filter(email => email !== '')

  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return emails.length > 0 && emails.every(email => emailRegex.test(email))
})

const sendInvitations = () => {
  // Validate emails
  if (!isValidEmails.value) {
    toast.error('Please enter valid email addresses')
    return
  }

  processing.value = true

  // Prepare email list
  const emails = emailInput.value
    .split(/[\n,]+/)
    .map(email => email.trim())
    .filter(email => email !== '')

  router.post(route('groups.invite.send'), {
    emails,
    role: selectedRole.value,
    message: invitationMessage.value
  }, {
    preserveScroll: true,
    onSuccess: () => {
      toast.success(`Invitations sent to ${emails.length} email(s)`)
    },
    onError: (errors) => {
      Object.values(errors).forEach(error => {
        toast.error(error)
      })
    },
    onFinish: () => {
      processing.value = false
    }
  })
}
</script>

<template>
  <GlobalModal
    max-width="md"
    padding-classes="0"
    v-slot="{ close }">
    <form @submit.prevent="sendInvitations">
      <Card>
        <CardHeader>
          <CardTitle>
            Invite Members to {{ group.name }}
          </CardTitle>

          <CardDescription>
            Add members to your group by email. They will receive an invitation.
          </CardDescription>
        </CardHeader>

        <CardContent class="space-y-4">
          <div>
            <Label>Email Addresses</Label>
            <Textarea
              v-model="emailInput"
              placeholder="Enter email addresses, separated by comma or new line"
              rows="4"
              class="mt-2"
            />

            <p class="text-sm text-muted-foreground mt-1">
              One email per line or comma-separated
            </p>
          </div>

          <div>
            <FormField
              type="textarea"
              label="Invitation Message (Optional)"
              placeholder="Write a custom invitation message"
              v-model="invitationMessage"
              rows="3"
            />
          </div>

          <div>
            <Label>Member Role</Label>
            <Select v-model="selectedRole">
              <SelectTrigger class="mt-2">
                <SelectValue placeholder="Select a role"/>
              </SelectTrigger>

              <SelectContent>
                <SelectItem value="member">Member</SelectItem>
                <SelectItem value="treasurer">Treasurer</SelectItem>
                <SelectItem value="secretary">Secretary</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </CardContent>

        <CardFooter class="justify-end gap-2">
          <Button
            type="button"
            variant="outline"
            @click="close">
            Cancel
          </Button>

          <Button
            type="submit"
            :disabled="!isValidEmails || processing">
            {{ processing ? 'Sending...' : 'Send Invitations' }}
          </Button>
        </CardFooter>
      </Card>
    </form>
  </GlobalModal>
</template>
