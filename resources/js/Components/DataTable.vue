<script setup lang="ts">
import {ref, computed} from 'vue'
import {
  Table,
  TableHeader,
  TableBody,
  TableRow,
  TableHead,
  TableCell,
} from '@/Components/ui/table'
import {Input} from '@/Components/ui/input'
import {
  Select,
  SelectTrigger,
  SelectValue,
  SelectContent,
  SelectItem,
} from '@/Components/ui/select'
import {Button} from '@/Components/ui/button'
import * as XLSX from 'xlsx'
import jsPDF from 'jspdf'
import 'jspdf-autotable'
import {
  DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger
} from "@/Components/ui/dropdown-menu";

const props = withDefaults(
  defineProps<{
    columns: Array<{}>
    data: Array<{}>
    hasActions?: boolean
  }>(), {
    hasActions: false
  })

const emit = defineEmits(['export-pdf', 'export-excel', 'export-csv'])

const searchQuery = ref('')
const currentPage = ref(1)
const perPage = ref(10)
const pageOptions = [10, 25, 50, 100]

const getNestedValue = (obj, path) => {
  return path.split('.').reduce((o, key) => (o && o[key] !== 'undefined') ? o[key] : '', obj)
}

const filteredAndPaginatedData = computed(() => {
  // Filter
  const filtered = props.data.filter(row =>
    Object.values(row).some(value =>
      String(value).toLowerCase().includes(searchQuery.value.toLowerCase())
    )
  )

  // Paginate
  const start = (currentPage.value - 1) * perPage.value
  const end = start + perPage.value
  return filtered.slice(start, end)
})

const totalItems = computed(() => props.data.length)
const totalPages = computed(() => Math.ceil(totalItems.value / perPage.value))

const startIndex = computed(() => (currentPage.value - 1) * perPage.value + 1)
const endIndex = computed(() => Math.min(currentPage.value * perPage.value, totalItems.value))

const nextPage = () => {
  if (currentPage.value < totalPages.value) {
    currentPage.value++
  }
}

const prevPage = () => {
  if (currentPage.value > 1) {
    currentPage.value--
  }
}

const exportToPDF = () => {
  const doc = new jsPDF()
  const tableColumn = props.columns.map(col => col.label)
  const tableRows = filteredAndPaginatedData.value.map(row =>
    props.columns.map(col => getNestedValue(row, col.key))
  )

  doc.autoTable({
    head: [tableColumn],
    body: tableRows,
    startY: 20,
    theme: 'striped'
  })

  doc.save(`export_${new Date().toISOString()}.pdf`)
  emit('export-pdf', filteredAndPaginatedData.value)
}

const exportToExcel = () => {
  const worksheet = XLSX.utils.json_to_sheet(filteredAndPaginatedData.value)
  const workbook = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(workbook, worksheet, 'Data')

  XLSX.writeFile(workbook, `export_${new Date().toISOString()}.xlsx`)
  emit('export-excel', filteredAndPaginatedData.value)
}

const exportToCSV = () => {
  const worksheet = XLSX.utils.json_to_sheet(filteredAndPaginatedData.value)
  const csvContent = XLSX.utils.sheet_to_csv(worksheet)

  const blob = new Blob([csvContent], {type: 'text/csv;charset=utf-8;'})
  const link = document.createElement('a')
  const url = URL.createObjectURL(blob)

  link.setAttribute('href', url)
  link.setAttribute('download', `export_${new Date().toISOString()}.csv`)
  link.style.visibility = 'hidden'

  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  emit('export-csv', filteredAndPaginatedData.value)
}
</script>

<template>
  <div class="data-table">
    <div class="table-controls mb-4 flex justify-between items-center">
      <div class="search-filter flex items-center space-x-2">
        <Input
          v-model="searchQuery"
          placeholder="Search..."
          class="w-64"
        />
        <Select v-model="perPage">
          <SelectTrigger class="w-[180px]">
            <SelectValue placeholder="Rows per page"/>
          </SelectTrigger>

          <SelectContent>
            <SelectItem v-for="option in pageOptions" :key="option" :value="option">
              {{ option }} rows
            </SelectItem>
          </SelectContent>
        </Select>
      </div>

      <div class="export-actions flex space-x-2">
        <DropdownMenu>
          <DropdownMenuTrigger as-child>
            <Button>Export</Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            <DropdownMenuItem @click="exportToPDF">Export PDF</DropdownMenuItem>
            <DropdownMenuItem @click="exportToExcel">Export Excel</DropdownMenuItem>
            <DropdownMenuItem @click="exportToCSV">Export CSV</DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>
    </div>

    <Table>
      <TableHeader>
        <TableRow>
          <TableHead v-for="column in columns" :key="column.key">
            {{ column.label }}
          </TableHead>
          <TableHead v-if="hasActions">Actions</TableHead>
        </TableRow>
      </TableHeader>

      <TableBody>
        <TableRow v-for="(row, index) in filteredAndPaginatedData" :key="index">
          <TableCell v-for="column in columns" :key="column.key">
            {{ getNestedValue(row, column.key) }}
          </TableCell>
          <TableCell v-if="hasActions">
            <slot name="actions" :row="row"></slot>
          </TableCell>
        </TableRow>
      </TableBody>
    </Table>

    <div class="pagination mt-4 flex justify-between items-center">
      <div>
        Showing {{ startIndex }} to {{ endIndex }} of {{ totalItems }} entries
      </div>

      <div class="flex space-x-2">
        <Button
          :disabled="currentPage === 1"
          @click="prevPage">
          Previous
        </Button>

        <Button
          :disabled="currentPage === totalPages"
          @click="nextPage">
          Next
        </Button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.data-table {
  margin: 20px 0;
}

.table-controls {
  margin-bottom: 1rem;
}
</style>
