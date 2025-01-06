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
  DropdownMenuContent,
  DropdownMenuItem
} from "@/Components/ui/dropdown-menu";

const groups = ref([])
const createModalOpen = ref(false)

const canCreateGroup = computed(() => {
  // Check user permissions
  return usePermission('create groups') })
const canEditGroup = computed(() => {
  // Check user permissions
  return usePermission('edit groups')
})

const groupColumns = [
  { name: 'Group Name', key: 'name' },
  { name: 'Created By', key: 'creator.name' },
  { name: 'Status', key: 'status' },
  { name: 'Actions', key: 'actions' }
]

const fetchGroups = async () => {
  const response = await axios.get('/api/groups')
  groups.value = response.data.groups
}

const openCreateGroupModal = () => {
  createModalOpen.value = true
}

const closeCreateModal = () => {
  createModalOpen.value = false
  fetchGroups() // Refresh the group list after closing the modal
}

const viewGroupDetails = (group) => {
  // Navigate to group details page
  router.push({ name: 'group.show', params: { id: group.id } })
}

const editGroup = (group) => {
  // Navigate to edit group page
  router.push({ name: 'group.edit', params: { id: group.id } })
}

onMounted(() => {
  fetchGroups()
})
</script>

<template>
  <div class="group-management">
    <Card>
      <CardHeader>
        <CardTitle>Group Management</CardTitle>
        <Button
          v-if="canCreateGroup"
          @click="openCreateGroupModal">
          Create New Group
        </Button>
      </CardHeader>

      <CardContent>

        <DataTable
          :columns="groupColumns"
          :data="groups">
          <template #actions="{ row }">
            <DropdownMenu>
              <DropdownMenuTrigger>
                Actions
              </DropdownMenuTrigger>

              <DropdownMenuContent>

                <DropdownMenuItem @click="viewGroupDetails(row)">
                  View Details
                </DropdownMenuItem>

                <DropdownMenuItem
                  v-if="canEditGroup"
                  @click="editGroup(row)">
                  Edit Group
                </DropdownMenuItem>

              </DropdownMenuContent>

            </DropdownMenu>

          </template>

        </DataTable>

      </CardContent>

    </Card>

    <!-- Modals for Create/Edit Group -->
<!--    <GroupCreateModal-->
<!--      :open="createModalOpen"-->
<!--      @close="closeCreateModal"-->
<!--    />-->
  </div>
</template>

<style scoped>
.group-management {
  padding: 20px;
}
</style>
