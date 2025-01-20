import { watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';

export function useToast() {
  const { props } = usePage();

  // Watch for error messages in Inertia props
  watch(
    () => props.errors,
    (errors) => {
      // Handle generic error message
      if (errors.message) {
        toast.error(errors.message, {
          duration: 5000,
          onAutoClose: () => props.errors = ''
        });
      }

      // Handle specific error types
      if (errors.authorization) {
        toast.warning('Access Denied', {
          description: errors.authorization,
          onAutoClose: () => props.errors = '',
          duration: 5000,
        });
      }

      // Add more specific error handling as needed
      if (errors.validation) {
        toast.error('Validation Error', {
          description: errors.validation.join(', '), // Assuming validation errors are an array
          onAutoClose: () => props.errors = '',
          duration: 5000,
        });
      }

      // Handle other specific errors
      if (errors.customError) {
        toast.error('Custom Error', {
          description: errors.customError,
          duration: 5000,
        });
      }
    },
    { immediate: true }
  );

  watch(() => props.flush, (flush) => {

    // Handle specific error types
    if (flush) {
      toast.success('Notification', {
        description: flush,
        onAutoClose: () => flush = '',
        duration: 5000,
      });
    }

  }, { immediate: true })
}
