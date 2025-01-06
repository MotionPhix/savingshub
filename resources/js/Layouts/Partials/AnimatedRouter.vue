<template>
  <div class="relative">
    <TransitionGroup
      appear
      enter-active-class="transition-all duration-500 ease-out"
      enter-from-class="opacity-0 translate-y-4"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition-all duration-500 ease-in absolute inset-0"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0 -translate-y-4">
      <slot/>
    </TransitionGroup>
  </div>
</template>
