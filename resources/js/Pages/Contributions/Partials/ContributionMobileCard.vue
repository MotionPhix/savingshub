<script setup lang="ts">
import {
  Card,
  CardContent
} from "@/Components/ui/card";
import { Badge } from '@/Components/ui/badge'
import { Button } from '@/Components/ui/button'

defineEmits(['view'])

const props = defineProps({
  contribution: {
    type: Object,
    required: true
  }
})

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'MWK',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(amount)
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const getStatusVariant = (status) => {
  switch(status) {
    case 'pending': return 'warning'
    case 'paid': return 'success'
    case 'overdue': return 'destructive'
    default: return 'secondary'
  }
}
</script>

<template>
  <Card class="hover:bg-muted/50 transition-colors">
    <CardContent class="p-4">
      <div class="flex justify-between items-center mb-2">
        <h3 class="text-lg font-bold">
          {{ formatCurrency(contribution.amount) }}
        </h3>
        <Badge :variant="getStatusVariant(contribution.status)">
          {{ contribution.status }}
        </Badge>
      </div>

      <div class="flex justify-between text-sm text-muted-foreground">
        <span class="capitalize">{{ contribution.type }} Contribution</span>
        <span>{{ formatDate(contribution.contribution_date) }}</span>
      </div>

      <Button
        variant="outline"
        size="sm"
        class="w-full mt-3"
        @click="$emit('view', contribution)">
        View Details
      </Button>
    </CardContent>
  </Card>
</template>
