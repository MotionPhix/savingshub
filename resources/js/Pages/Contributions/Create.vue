<script setup lang="ts">
import {Button} from "@/Components/ui/button"
import {Label} from "@/Components/ui/label"
import {Card, CardContent, CardFooter} from "@/Components/ui/card"
import {useForm, usePage} from '@inertiajs/vue3'
import AppLayout from "@/Layouts/AppLayout.vue";
import PageHeader from "@/Components/PageHeader.vue";
import FormField from "@/Components/Forms/FormField.vue";
import InputError from "@/Components/InputError.vue";
import {Calendar} from "@/Components/ui/v-calendar";
import {subDays, format} from 'date-fns'
import {useScreens} from 'vue-screen-utils';
import {toast} from "vue-sonner";
import {watch, ref} from "vue";
import { Loader2Icon } from 'lucide-vue-next'
import {data} from "autoprefixer";
const {mapCurrent} = useScreens({xs: '0px', sm: '640px', md: '768px', lg: '1024px'});
const columns = mapCurrent({md: 2, lg: 2}, 1);

const payment_method = ref('cash')

const form = useForm({
  amount: 0,
  contribution_date: null,
  transaction_reference: '',
  type: 'regular'
})

// Contribution Types
const contributionTypes = [
  {value: 'regular', label: 'Regular Contribution'},
  {value: 'extra', label: 'Extra Contribution'},
  {value: 'makeup', label: 'Makeup Contribution'}
]

// Payment Methods
const paymentMethods = [
  {value: 'cash', label: 'Cash'},
  {value: 'bank_transfer', label: 'Bank Transfer'},
  {value: 'mobile_money', label: 'Mobile Money'}
]

// Form submission method
const submit = () => {
  form
    .transform(data => {
      const { transaction_reference, ...rest } = data

      return {
        ...rest,
        ...(payment_method.value && {payment_method: payment_method.value}),
        ...(transaction_reference.length && {transaction_reference: data.transaction_reference}),
        ...(data.contribution_date && {contribution_date: format(data.contribution_date, 'yyyy-MM-dd')}),
      }
    })
    .post(route('contributions.store'), {
      onSuccess: () => {
        // Reset form after successful submission
        form.reset()
      },
      onError: (errors) => {
        if (errors.message) {
          toast.error(errors.message, {
            duration: 5000
          })
        }
      },
      onFinish: () => usePage().props.errors = null
    })
}

// Watch payment method to reset transaction reference
watch(payment_method, (newMethod) => {
  if (newMethod !== 'bank_transfer') {
    form.reset('transaction_reference')
  }
}, {immediate: true})
</script>

<template>
  <AppLayout>
    <div class="mx-auto sm:px-4 space-y-6 my-12">

      <PageHeader>
        Add Contribution
      </PageHeader>

      <form @submit.prevent="submit">
        <Card>
          <CardContent class="pt-6">
            <div class="mb-4 grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
              <div>
                <FormField
                  label="Amount"
                  type="number"
                  v-model="form.amount"
                  :error="form.errors.amount"
                  :max="Number($page.props.current_group.contribution_amount)"
                  format="currency"
                  required
                  placeholder="Enter contribution amount"
                />
              </div>

              <div>
                <FormField
                  type="select"
                  label="Contribution Type"
                  v-model="form.type"
                  :options="contributionTypes"
                />
              </div>
            </div>

            <div class="mb-4">
              <Label for="contribution_date">
                Contribution Date
              </Label>

              <Calendar
                :max-date="new Date()"
                :min-date="subDays(new Date(), 2)"
                id="contribution_date"
                v-model="form.contribution_date"
                :columns="columns"
                class="mt-1"
              />

              <InputError :message="form.errors.contribution_date" class="mt-2"/>
            </div>

            <div class="grid" :class="payment_method === 'bank_transfer' ? 'grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6' : 'grid-cols-1'">

              <FormField
                label="Payment Method"
                v-model="payment_method"
                placeholder="Select payment method"
                :options="paymentMethods"
                :error="form.errors.payment_method"
                type="select"
              />

              <FormField
                v-model="form.transaction_reference"
                v-if="payment_method === 'bank_transfer'"
                label="Transaction Reference"
                placeholder="Provide the bank transfer reference number"
                :error="form.errors.transaction_reference"
              />

            </div>
          </CardContent>

          <CardFooter class="justify-end">

            <Button
              :disabled="form.processing"
              type="submit">
              <Loader2Icon
                v-if="form.processing"
                class="mr-2 h-4 w-4 animate-spin"
              />
              Add Contribution
            </Button>

          </CardFooter>
        </Card>
      </form>

    </div>
  </AppLayout>
</template>
