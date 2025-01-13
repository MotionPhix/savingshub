export const formatCurrency = (amount: number, currency: string = 'MWK'): string => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: currency,
  }).format(amount || 0)
}

export const formatTimeAgo = (date: string | Date): string => {
  const now = new Date()
  const past = new Date(date)
  const diffInSeconds = Math.floor((now.getTime() - past.getTime()) / 1000)

  const units = [
    { name: 'year', seconds: 31536000 },
    { name: 'month', seconds: 2592000 },
    { name: 'week', seconds: 604800 },
    { name: 'day', seconds: 86400 },
    { name: 'hour', seconds: 3600 },
    { name: 'minute', seconds: 60 },
    { name: 'second', seconds: 1 }
  ]

  for (const unit of units) {
    const interval = Math.floor(diffInSeconds / unit.seconds)
    if (interval >= 1) {
      return interval === 1
        ? `1 ${unit.name} ago`
        : `${interval} ${unit.name}s ago`
    }
  }

  return 'just now'
}

export const formatDate = (date: string | Date, format: 'short' | 'long' = 'short'): string => {
  const options: Intl.DateTimeFormatOptions =
    format === 'short'
      ? {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      }
      : {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      }

  return new Intl.DateTimeFormat('en-US', options).format(new Date(date))
}

export const calculatePercentage = (value: number, total: number): string => {
  return total > 0
    ? `${((value / total) * 100).toFixed(2)}%`
    : '0.00%'
}

export const truncateText = (text: string, length: number = 50): string => {
  return text.length > length
    ? `${text.substring(0, length)}...`
    : text
}
