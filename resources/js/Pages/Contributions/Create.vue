<script setup lang="ts">
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { Button } from "@/Components/ui/button"
import { Input } from "@/Components/ui/input"
import { Label } from "@/Components/ui/label"
import { Card, CardContent, CardHeader, CardTitle } from "@/Components/ui/card"
import { useForm } from '@inertiajs/vue3'

const form = useForm({
  amount: '',
  contribution_date: '',
  type: 'regular'
})

// Role check (assuming user roles are passed as props)
const userRoles = defineProps(['userRoles'])
const isAuthorized = userRoles.includes('treasurer') || userRoles.includes('admin')

// Form submission method
const submit = () => {
  form.post(route('contributions.store'), {
    onSuccess: () => {
      // Reset form after successful submission
      form.reset()
    },
    onError: (errors) => {
      // Handle errors (optional)
      console.error(errors)
    }
  })
}
</script>

<template>
  <AppLayout>
    <div class="mx-auto sm:px-4 space-y-6 my-12">
      <Card>
        <CardHeader>
          <CardTitle>Add Contribution</CardTitle>
        </CardHeader>
        <CardContent>
          <div v-if="!isAuthorized" class="text-red-600">
            You do not have permission to add contributions.
          </div>
          <div v-else>
            <form @submit.prevent="submit">
              <div class="mb-4">
                <Label for="amount" class="block text-sm font-medium">Amount</Label>
                <Input
                  id="amount"
                  type="number"
                  v-model="form.amount"
                  required
                  placeholder="Enter contribution amount"
                  class="mt-1"
                />
                <span v-if="form.errors.amount" class="text-red-600 text-sm">{{ form.errors.amount }}</span>
              </div>

              <div class="mb-4">
                <Label for="contribution_date" class="block text-sm font-medium">Contribution Date</Label>
                <Input
                  id="contribution_date"
                  type="date"
                  v-model="form.contribution_date"
                  required
                  class="mt-1"
                />
                <span v-if="form.errors.contribution_date" class="text-red-600 text-sm">{{ form.errors.contribution_date }}</span>
              </div>

              <div class="mb-4">
                <Label for="type" class="block text-sm font-medium">Contribution Type</Label>
                <select
                  id="type"
                  v-model="form.type"
                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-opacity-50"
                >
                  <option value="regular">Regular</option>
                  <option value="extra">Extra</option>
                  <option value="makeup">Makeup</option>
                </select>
              </div>

              <Button type="submit" class="mt-4">Add Contribution</Button>
            </form>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>

<style scoped>
/* Add any additional styles here */
</style>
