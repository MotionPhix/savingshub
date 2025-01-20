<script setup lang="ts">
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle
} from "@/Components/ui/card"
import {
  DropdownMenu,
  DropdownMenuTrigger,
  DropdownMenuContent,
  DropdownMenuItem
} from "@/Components/ui/dropdown-menu"
import {Button} from "@/Components/ui/button"
import {
  PlusIcon,
  MoreVerticalIcon
} from "lucide-vue-next"
import AppLayout from "@/Layouts/AppLayout.vue"
import EmptyGroups from "@/Pages/Groups/Partials/EmptyGroups.vue"
import {router} from "@inertiajs/vue3"
import {usePluralize} from '@/composables/usePluralize'
import PageHeader from "@/Components/PageHeader.vue";
import {visitModal} from '@inertiaui/modal-vue'
import {Separator} from "@/Components/ui/separator";
import UserAvatar from "@/Layouts/Partials/UserAvatar.vue";
import {getInitials} from "@/lib/stringUtils";

const props = withDefaults(defineProps<{
  groups: Array<{
    uuid: string
    name: string
    description?: string
    user_role?: 'admin' | 'treasurer' | 'member' | 'secretary' | null
    creator: {
      name: string
      avatar: string
    }
    total_members: number
    status: 'active' | 'inactive' | 'pending'
    pending_contributions_count: number
    pending_loan_requests_count: number
    can_contribute?: boolean
    can_request_loan?: boolean
  }>
}>(), {
  groups: () => []
})

const {pluralizeWord} = usePluralize()

const viewGroupDetails = (group) => {
  router.visit(route('groups.show', group.uuid))
}

const editGroup = (group) => {
  router.visit(route('groups.edit', group.uuid))
}

const activateGroup = (group) => {
  router.post(route('groups.set.active', group.uuid), {
    replace: true
  })
}

const groupStatusVariants = {
  active: 'bg-green-100 text-green-800',
  inactive: 'bg-gray-100 text-gray-800',
  pending: 'bg-yellow-100 text-yellow-800'
}
</script>

<template>
  <AppLayout>
    <div class="mx-auto sm:px-4 py-8">
      <PageHeader>
        Group Management

        <template #description>
          Groups you are a member of, <br class="sm:hidden"/>plus those you own.
        </template>

        <template #action>

          <Button
            size="icon"
            variant="outline"
            class="shrink-0"
            @click="router.get(route('groups.create'))">
            <PlusIcon class="h-5 w-5"/>
          </Button>

        </template>
      </PageHeader>


      <EmptyGroups v-if="groups.length === 0"/>

      <div
        v-else
        class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        <!--        <Card-->
        <!--          v-for="group in groups"-->
        <!--          class="hover:shadow-lg flex flex-col transition-shadow p-3"-->
        <!--          :key="group.uuid">-->
        <!--          <CardHeader class="p-0">-->

        <!--            <CardTitle class="text-lg flex justify-between items-center">-->
        <!--              <div class="grid">-->
        <!--                <span>{{ group.name }}</span>-->
        <!--                <span class="capitalize text-muted-foreground text-sm font-thin">-->
        <!--                  Group {{ group.user_role }}-->
        <!--                </span>-->
        <!--              </div>-->

        <!--              <DropdownMenu>-->
        <!--                <DropdownMenuTrigger as-child>-->
        <!--                  <Button variant="outline" size="icon">-->
        <!--                    <MoreVerticalIcon class="h-4 w-4"/>-->
        <!--                  </Button>-->
        <!--                </DropdownMenuTrigger>-->

        <!--                <DropdownMenuContent align="end" :side-offset="-4">-->
        <!--                  <DropdownMenuItem-->
        <!--                    @click="viewGroupDetails(group)"-->
        <!--                    class="cursor-pointer">-->
        <!--                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none">-->
        <!--                      <path-->
        <!--                        d="M2 12C2 7.75736 2 5.63604 3.46447 4.31802C4.92893 3 7.28596 3 12 3C16.714 3 19.0711 3 20.5355 4.31802C22 5.63604 22 7.75736 22 12C22 16.2426 22 18.364 20.5355 19.682C19.0711 21 16.714 21 12 21C7.28596 21 4.92893 21 3.46447 19.682C2 18.364 2 16.2426 2 12Z"-->
        <!--                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>-->
        <!--                      <path-->
        <!--                        d="M8.4 8H7.6C6.84575 8 6.46863 8 6.23431 8.23431C6 8.46863 6 8.84575 6 9.6V10.4C6 11.1542 6 11.5314 6.23431 11.7657C6.46863 12 6.84575 12 7.6 12H8.4C9.15425 12 9.53137 12 9.76569 11.7657C10 11.5314 10 11.1542 10 10.4V9.6C10 8.84576 10 8.46863 9.76569 8.23431C9.53137 8 9.15425 8 8.4 8Z"-->
        <!--                        stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>-->
        <!--                      <path d="M6 16H10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"-->
        <!--                            stroke-linejoin="round"/>-->
        <!--                      <path d="M14 8H18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"-->
        <!--                            stroke-linejoin="round"/>-->
        <!--                      <path d="M14 12H18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"-->
        <!--                            stroke-linejoin="round"/>-->
        <!--                      <path d="M14 16H18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"-->
        <!--                            stroke-linejoin="round"/>-->
        <!--                    </svg>-->
        <!--                    Details-->
        <!--                  </DropdownMenuItem>-->

        <!--                  <DropdownMenuItem-->
        <!--                    @click="editGroup(group)"-->
        <!--                    class="cursor-pointer">-->
        <!--                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none">-->
        <!--                      <path-->
        <!--                        d="M14.0737 3.88545C14.8189 3.07808 15.1915 2.6744 15.5874 2.43893C16.5427 1.87076 17.7191 1.85309 18.6904 2.39232C19.0929 2.6158 19.4769 3.00812 20.245 3.79276C21.0131 4.5774 21.3972 4.96972 21.6159 5.38093C22.1438 6.37312 22.1265 7.57479 21.5703 8.5507C21.3398 8.95516 20.9446 9.33578 20.1543 10.097L10.7506 19.1543C9.25288 20.5969 8.504 21.3182 7.56806 21.6837C6.63212 22.0493 5.6032 22.0224 3.54536 21.9686L3.26538 21.9613C2.63891 21.9449 2.32567 21.9367 2.14359 21.73C1.9615 21.5234 1.98636 21.2043 2.03608 20.5662L2.06308 20.2197C2.20301 18.4235 2.27297 17.5255 2.62371 16.7182C2.97444 15.9109 3.57944 15.2555 4.78943 13.9445L14.0737 3.88545Z"-->
        <!--                        stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>-->
        <!--                      <path d="M13 4L20 11" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>-->
        <!--                      <path d="M14 22L22 22" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"-->
        <!--                            stroke-linejoin="round"/>-->
        <!--                    </svg>-->
        <!--                    Edit Group-->
        <!--                  </DropdownMenuItem>-->

        <!--                  <DropdownMenuItem-->
        <!--                    v-if="$page.props.current_path !== group.id"-->
        <!--                    @click="activateGroup(group)"-->
        <!--                    class="cursor-pointer">-->
        <!--                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none">-->
        <!--                      <path d="M20 10C19.9641 7.52043 19.7801 6.11466 18.8365 5.17157C17.6643 4 15.7776 4 12.0043 4C8.23106 4 6.34442 4 5.17221 5.17157C4 6.34315 4 8.22876 4 12C4 15.7712 4 17.6569 5.17221 18.8284C6.23545 19.8911 7.88646 19.9899 11 19.9991" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />-->
        <!--                      <path d="M18 19.7143V21M18 19.7143C16.8432 19.7143 15.8241 19.1461 15.2263 18.2833M18 19.7143C19.1568 19.7143 20.1759 19.1461 20.7737 18.2833M18 13.2857C19.1569 13.2857 20.1761 13.854 20.7738 14.7169M18 13.2857C16.8431 13.2857 15.8239 13.854 15.2262 14.7169M18 13.2857V12M22 13.9286L20.7738 14.7169M14.0004 19.0714L15.2263 18.2833M14 13.9286L15.2262 14.7169M21.9996 19.0714L20.7737 18.2833M20.7738 14.7169C21.1273 15.2271 21.3333 15.8403 21.3333 16.5C21.3333 17.1597 21.1272 17.773 20.7737 18.2833M15.2262 14.7169C14.8727 15.2271 14.6667 15.8403 14.6667 16.5C14.6667 17.1597 14.8728 17.773 15.2263 18.2833" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />-->
        <!--                      <path d="M9.5 2V4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />-->
        <!--                      <path d="M14.5 2V4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />-->
        <!--                      <path d="M9.5 20V22" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />-->
        <!--                      <path d="M4 9.5L2 9.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />-->
        <!--                      <path d="M4 14.5L2 14.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />-->
        <!--                      <path d="M22 9.5L20 9.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />-->
        <!--                      <path d="M12 8H8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />-->
        <!--                    </svg>-->
        <!--                    Activate-->
        <!--                  </DropdownMenuItem>-->
        <!--                </DropdownMenuContent>-->
        <!--              </DropdownMenu>-->
        <!--            </CardTitle>-->

        <!--          </CardHeader>-->

        <!--          <Separator class="my-4" />-->

        <!--          <CardContent class="p-0">-->
        <!--            <p class="text-sm text-muted-foreground mb-4 line-clamp-2">-->
        <!--              {{ group.description || 'No description provided' }}-->
        <!--            </p>-->
        <!--          </CardContent>-->

        <!--          <span class="flex-1"></span>-->

        <!--          <CardContent class="p-0">-->
        <!--            <div class="flex items-center justify-between">-->
        <!--              <div class="flex items-center space-x-2">-->
        <!--                <svg-->
        <!--                  class="text-muted-foreground shrink-0 h-5 w-5"-->
        <!--                  xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none">-->
        <!--                  <path-->
        <!--                    d="M3 20V17.9704C3 16.7281 3.55927 15.5099 4.68968 14.9946C6.0685 14.3661 7.72212 14 9.5 14C10.7448 14 11.9287 14.1795 13 14.5028"-->
        <!--                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>-->
        <!--                  <circle cx="9.5" cy="7.5" r="3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"-->
        <!--                          stroke-linejoin="round"/>-->
        <!--                  <path d="M14.5 4.14453C15.9457 4.57481 17 5.91408 17 7.49959C17 9.0851 15.9457 10.4244 14.5 10.8547"-->
        <!--                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>-->
        <!--                  <path d="M18 14V20M15 17H21" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"-->
        <!--                        stroke-linejoin="round"/>-->
        <!--                </svg>-->
        <!--                <span class="text-sm">-->
        <!--                      {{ group.total_members ?? 0 }} {{ pluralizeWord(group.total_members, 'Member') }}-->
        <!--                    </span>-->
        <!--              </div>-->

        <!--              <div-->
        <!--                :class="`-->
        <!--                  px-2 py-1 capitalize rounded-md text-xs text-center-->
        <!--                  ${groupStatusVariants[group.status] || groupStatusVariants.inactive}-->
        <!--                `">-->
        <!--                {{ group.status }}-->
        <!--              </div>-->
        <!--            </div>-->

        <!--            <div-->
        <!--              class="mt-4 grid grid-cols-2 gap-2"-->
        <!--              v-if="group.pending_contributions_count > 0 || group.pending_loan_requests_count > 0">-->
        <!--              <div-->
        <!--                v-if="group.pending_contributions_count > 0"-->
        <!--                class="bg-blue-100 text-blue-800 text-xs py-1 rounded-full text-center">-->
        <!--                {{ group.pending_contributions_count }} Pending Contributions-->
        <!--              </div>-->

        <!--              <div-->
        <!--                v-if="group.pending_loan_requests_count > 0"-->
        <!--                class="bg-red-100 text-red-800 text-xs py-1 rounded-full text-center">-->
        <!--                {{ group.pending_loan_requests_count }} Loan Requests-->
        <!--              </div>-->
        <!--            </div>-->
        <!--          </CardContent>-->
        <!--        </Card>-->

        <Card
          v-for="group in groups"
          class="hover:shadow-lg flex flex-col transition-shadow p-3"
          :key="group.uuid">
          <CardHeader class="p-0">
            <CardTitle class="text-lg">
              <div class="grid">
                <span>{{ group.name }}</span>

                <span class="capitalize text-muted-foreground text-sm font-thin">
                  <span v-if="group.user_role === 'admin'">
                    You own this group
                  </span>

                  <span v-else>
                    Group {{ group.user_role }}
                  </span>
                </span>
              </div>
            </CardTitle>
          </CardHeader>

          <Separator class="my-4"/>

          <CardContent class="p-0">
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-3">
                <UserAvatar
                  v-if="group.creator.avatar"
                  :src="group.creator.avatar"
                  :alt="group.creator.name"
                  :fallback="getInitials(group.creator.name)"
                />

                <div>
                  <div class="font-medium">{{ group.creator.name }}</div>
                  <div class="text-xs text-muted-foreground font-thin">
                    Group Creator
                  </div>
                </div>
              </div>

              <DropdownMenu>
                <DropdownMenuTrigger as-child>
                  <Button variant="outline" size="icon">
                    <MoreVerticalIcon class="h-4 w-4"/>
                  </Button>
                </DropdownMenuTrigger>

                <DropdownMenuContent align="end" :side-offset="-4">
                  <DropdownMenuItem
                    @click="viewGroupDetails(group)"
                    v-if="group.uuid === $page.props.current_group.uuid"
                    class="cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none">
                      <path
                        d="M2 12C2 7.75736 2 5.63604 3.46447 4.31802C4.92893 3 7.28596 3 12 3C16.714 3 19.0711 3 20.5355 4.31802C22 5.63604 22 7.75736 22 12C22 16.2426 22 18.364 20.5355 19.682C19.0711 21 16.714 21 12 21C7.28596 21 4.92893 21 3.46447 19.682C2 18.364 2 16.2426 2 12Z"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                      <path
                        d="M8.4 8H7.6C6.84575 8 6.46863 8 6.23431 8.23431C6 8.46863 6 8.84575 6 9.6V10.4C6 11.1542 6 11.5314 6.23431 11.7657C6.46863 12 6.84575 12 7.6 12H8.4C9.15425 12 9.53137 12 9.76569 11.7657C10 11.5314 10 11.1542 10 10.4V9.6C10 8.84576 10 8.46863 9.76569 8.23431C9.53137 8 9.15425 8 8.4 8Z"
                        stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                      <path d="M6 16H10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                            stroke-linejoin="round"/>
                      <path d="M14 8H18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                            stroke-linejoin="round"/>
                      <path d="M14 12H18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                            stroke-linejoin="round"/>
                      <path d="M14 16H18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                            stroke-linejoin="round"/>
                    </svg>
                    Details
                  </DropdownMenuItem>

                  <DropdownMenuItem
                    v-if="(group.user_role === 'admin' || group.user_role === 'treasurer') && group.uuid === $page.props.current_group.uuid"
                    @click="editGroup(group)"
                    class="cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none">
                      <path
                        d="M14.0737 3.88545C14.8189 3.07808 15.1915 2.6744 15.5874 2.43893C16.5427 1.87076 17.7191 1.85309 18.6904 2.39232C19.0929 2.6158 19.4769 3.00812 20.245 3.79276C21.0131 4.5774 21.3972 4.96972 21.6159 5.38093C22.1438 6.37312 22.1265 7.57479 21.5703 8.5507C21.3398 8.95516 20.9446 9.33578 20.1543 10.097L10.7506 19.1543C9.25288 20.5969 8.504 21.3182 7.56806 21.6837C6.63212 22.0493 5.6032 22.0224 3.54536 21.9686L3.26538 21.9613C2.63891 21.9449 2.32567 21.9367 2.14359 21.73C1.9615 21.5234 1.98636 21.2043 2.03608 20.5662L2.06308 20.2197C2.20301 18.4235 2.27297 17.5255 2.62371 16.7182C2.97444 15.9109 3.57944 15.2555 4.78943 13.9445L14.0737 3.88545Z"
                        stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                      <path d="M13 4L20 11" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                      <path d="M14 22L22 22" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                            stroke-linejoin="round"/>
                    </svg>
                    Edit Group
                  </DropdownMenuItem>

                  <DropdownMenuItem
                    v-if="group.uuid !== $page.props.current_group.uuid"
                    @click="activateGroup(group)"
                    class="cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none">
                      <path d="M20 10C19.9641 7.52043 19.7801 6.11466 18.8365 5.17157C17.6643 4 15.7776 4 12.0043 4C8.23106 4 6.34442 4 5.17221 5.17157C4 6.34315 4 8.22876 4 12C4 15.7712 4 17.6569 5.17221 18.8284C6.23545 19.8911 7.88646 19.9899 11 19.9991" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                      <path d="M18 19.7143V21M18 19.7143C16.8432 19.7143 15.8241 19.1461 15.2263 18.2833M18 19.7143C19.1568 19.7143 20.1759 19.1461 20.7737 18.2833M18 13.2857C19.1569 13.2857 20.1761 13.854 20.7738 14.7169M18 13.2857C16.8431 13.2857 15.8239 13.854 15.2262 14.7169M18 13.2857V12M22 13.9286L20.7738 14.7169M14.0004 19.0714L15.2263 18.2833M14 13.9286L15.2262 14.7169M21.9996 19.0714L20.7737 18.2833M20.7738 14.7169C21.1273 15.2271 21.3333 15.8403 21.3333 16.5C21.3333 17.1597 21.1272 17.773 20.7737 18.2833M15.2262 14.7169C14.8727 15.2271 14.6667 15.8403 14.6667 16.5C14.6667 17.1597 14.8728 17.773 15.2263 18.2833" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                      <path d="M9.5 2V4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                      <path d="M14.5 2V4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                      <path d="M9.5 20V22" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                      <path d="M4 9.5L2 9.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                      <path d="M4 14.5L2 14.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                      <path d="M22 9.5L20 9.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                      <path d="M12 8H8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    Activate
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>

            </div>

            <p class="text-sm text-muted-foreground my-4 line-clamp-2">
              {{ group.description || 'No description provided' }}
            </p>
          </CardContent>

          <span class="flex-1"></span>

          <CardContent class="p-0">

            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-2">
                <svg
                  class="text-muted-foreground shrink-0 h-5 w-5"
                  xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none">
                  <path
                    d="M3 20V17.9704C3 16.7281 3.55927 15.5099 4.68968 14.9946C6.0685 14.3661 7.72212 14 9.5 14C10.7448 14 11.9287 14.1795 13 14.5028"
                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                  <circle cx="9.5" cy="7.5" r="3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                          stroke-linejoin="round"/>
                  <path d="M14.5 4.14453C15.9457 4.57481 17 5.91408 17 7.49959C17 9.0851 15.9457 10.4244 14.5 10.8547"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M18 14V20M15 17H21" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                        stroke-linejoin="round"/>
                </svg>

                <span class="text-sm">
                  {{ group.total_members ?? 0 }} {{ pluralizeWord(group.total_members, 'Member') }}
                </span>
              </div>

              <div
                :class="`
                  px-2 py-1 capitalize rounded-md text-xs text-center
                  ${groupStatusVariants[group.status] || groupStatusVariants.inactive}
                `">
                {{ group.status }}
              </div>
            </div>

            <div
              class="mt-4 grid grid-cols-2 gap-2"
              v-if="group.pending_contributions_count > 0 || group.pending_loan_requests_count > 0">
              <div
                v-if="group.pending_contributions_count > 0"
                class="bg-blue-100 text-blue-800 text-xs py-1 rounded-full text-center">
                {{ group.pending_contributions_count }} Pending Contributions
              </div>

              <div
                v-if="group.pending_loan_requests_count > 0"
                class="bg-red-100 text-red-800 text-xs py-1 rounded-full text-center">
                {{ group.pending_loan_requests_count }} Loan Requests
              </div>
            </div>
          </CardContent>

        </Card>

      </div>

    </div>

  </AppLayout>
</template>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
