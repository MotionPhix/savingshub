import { ref } from 'vue';

export function useInitials() {
  const initials = ref('');

  const getInitials = (fullName) => {
    if (!fullName) return '';
    const nameParts = fullName.trim().split(' ');
    initials.value = nameParts.map(part => part[0].toUpperCase()).join('');
    return initials.value
  };

  return {
    initials,
    getInitials
  };
}
