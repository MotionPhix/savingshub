import { ref } from 'vue'

export function useSidebar() {
  const isMobile = ref(window.innerWidth < 1024)
  const isSidebarOpen = ref(false)

  const checkScreenSize = () => {
    const prevIsMobile = isMobile.value
    isMobile.value = window.innerWidth < 1024

    // Automatically close sidebar when switching to mobile
    if (!prevIsMobile && isMobile.value) {
      isSidebarOpen.value = false
    }
  }

  const toggleSidebar = () => {
    isSidebarOpen.value = !isSidebarOpen.value

    // Prevent body scrolling on mobile
    if (isMobile.value) {
      document.body.classList.toggle('sidebar-open', isSidebarOpen.value)
    }
  }

  const closeSidebar = () => {
    isSidebarOpen.value = false

    if (isMobile.value) {
      document.body.classList.remove('sidebar-open')
    }
  }

  return {
    isMobile,
    isSidebarOpen,
    checkScreenSize,
    toggleSidebar,
    closeSidebar
  }
}
