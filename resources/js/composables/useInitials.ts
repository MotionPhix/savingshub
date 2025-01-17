// useInitials.ts
export function useInitials() {
  // Remove the reactive ref
  const getInitials = (fullName: string) => {
    if (!fullName) return '';

    // Use a pure function approach
    return fullName
      .trim()
      .split(' ')
      .map(part => part[0]?.toUpperCase() || '')
      .slice(0, 2)
      .join('');
  };

  return {
    getInitials
  };
}
