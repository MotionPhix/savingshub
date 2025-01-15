import { computed } from 'vue'

export function useCurrency(group) {
  const currencySymbol = computed(() =>
    group.settings?.currency_symbol || '$'
  )

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: group.settings?.currency || 'USD'
    }).format(amount)
  }

  return {
    currencySymbol,
    formatCurrency
  }
}
