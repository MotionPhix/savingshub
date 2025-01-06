<script setup>
import { ref, computed, onMounted } from 'vue'
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle
} from "@/Components/ui/card";
import {
  DropdownMenu,
  DropdownMenuTrigger,
  DropdownMenuItem,
  DropdownMenuContent,
} from "@/Components/ui/dropdown-menu";

const loans = ref([])
const loanModalOpen = ref(false)

const canRequestLoan = computed(() => {
  return usePermission('request loans')
})

const canApproveLoan = computed(() => {
  return usePermission('approve loans')
})

const loanColumns = [
  { name: 'Amount', key: 'amount' },
  { name: 'Interest', key: 'interest_amount' },
  { name: 'Due Date', key: 'due_date' },
  { name: 'Status', key: 'status' },
  { name: 'Actions', key: 'actions' }
]

const fetchLoans = async () => {
  const response = await axios.get('/api/loans')
  loans.value = response.data
}

const openLoanModal = () => {
  loanModalOpen.value = true
}

const closeLoanModal = () => {
  loanModalOpen.value = false
  fetchLoans() // Refresh the loans list
}

const viewLoanDetails = (loan) => {
  // Logic to view loan details
}

const approveLoan = async (id) => {
  await axios.patch(`/api/loans/${id}/approve`)
  fetchLoans() // Refresh the loans list
}

onMounted(() => {
  fetchLoans()
})
</script>

<template>
  <div class="loan-management">
    <Card>
      <CardHeader>
        <CardTitle>My Loans</CardTitle>
        <Button
          v-if="canRequestLoan"
          @click="openLoanModal"
        >
          Request Loan
        </Button>
      </CardHeader>

      <CardContent>
        <DataTable
          :columns="loanColumns"
          :data="loans">
          <template #actions="{ row }">
            <DropdownMenu>
              <DropdownMenuTrigger>Actions</DropdownMenuTrigger>
              <DropdownMenuContent>
                <DropdownMenuItem @click="viewLoanDetails(row)">
                  View Details
                </DropdownMenuItem>

                <DropdownMenuItem
                  v-if="canApproveLoan"
                  @click="approveLoan(row.id)">
                  Approve Loan
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </template>
        </DataTable>
      </CardContent>
    </Card>

<!--    <LoanModal-->
<!--      :open="loanModalOpen"-->
<!--      @close="closeLoanModal"-->
<!--    />-->
  </div>
</template>

<style scoped>
.loan-management {
  padding: 20px;
}
</style>
