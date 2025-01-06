<script setup>
import { ref } from 'vue'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from 'shadcn-vue'
import { Form, FormField, FormLabel, FormControl, Input, Select, SelectTrigger, SelectValue, SelectContent, SelectItem, MultiSelect, Button } from '@/components/ui'

const isOpen = ref(false)
const groupForm = ref({
  name: '',
  frequency: '',
  amount: '',
  duration: '',
  members: []
})

const memberOptions = ref([
  { value: 'user1', label: 'User  1' },
  { value: 'user2', label: 'User  2' },
  { value: 'user3', label: 'User  3' }
])

const createGroup = () => {
  // Logic to create a new group
  console.log('Group created:', groupForm.value)
  closeModal()
}

const closeModal = () => {
  isOpen.value = false
}
</script>

<template>
  <Dialog v-model:open="isOpen">
    <DialogContent class="sm:max-w-[600px]">
      <DialogHeader>
        <DialogTitle>Create New Savings Group</DialogTitle>
        <DialogDescription>
          Set up a new group to start saving together
        </DialogDescription>
      </DialogHeader>

      <Form @submit="createGroup">
        <div class="grid gap-4 py-4">
          <FormField name="name">
            <FormLabel>Group Name</FormLabel>
            <FormControl>
              <Input
                placeholder="Enter group name"
                v-model="groupForm.name"
              />
            </FormControl>
          </FormField>

          <FormField name="contributionFrequency">
            <FormLabel>Contribution Frequency</FormLabel>
            <Select v-model="groupForm.frequency">
              <SelectTrigger>
                <SelectValue placeholder="Select frequency" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="monthly">Monthly</SelectItem>
                <SelectItem value="quarterly">Quarterly</SelectItem>
                <SelectItem value="weekly">Weekly</SelectItem>
              </SelectContent>
            </Select>
          </FormField>

          <div class="grid grid-cols-2 gap-4">
            <FormField name="contributionAmount">
              <FormLabel>Contribution Amount</FormLabel>
              <Input
                type="number"
                v-model="groupForm.amount"
                placeholder="Enter amount"
              />
            </FormField>

            <FormField name="duration">
              <FormLabel>Duration (Months)</FormLabel>
              <Input
                type="number"
                v-model="groupForm.duration"
                placeholder="Enter duration"
              />
            </FormField>
          </div>

          <FormField name="members">
            <FormLabel>Invite Members</FormLabel>
            <MultiSelect
              v-model="groupForm.members"
              :options="memberOptions"
              placeholder="Select members"
            />
          </FormField>
        </div>

        <div class="flex justify-end mt-4">
          <Button type="button" @click="closeModal" class="mr-2">Cancel</Button>
          <Button type="submit" variant="primary">Create Group</Button>
        </div>
      </Form>
    </DialogContent>
  </Dialog>
</template>
