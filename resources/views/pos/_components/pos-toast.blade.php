<div x-show="showToast"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 translate-y-2"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 translate-y-2"
     @click="showToast = false"
     style="display: none;"
     class="fixed bottom-5 right-5 z-[70] cursor-pointer">
  <div :class="toastType === 'success' ? 'bg-green-500' : 'bg-red-500'"
       class="px-5 py-3 rounded-xl shadow-lg text-white text-sm font-medium flex items-center gap-2">
    <svg x-show="toastType === 'success'"
         class="w-4 h-4"
         fill="none"
         stroke="currentColor"
         viewBox="0 0 24 24">
      <path stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M5 13l4 4L19 7" />
    </svg>
    <svg x-show="toastType === 'error'"
         class="w-4 h-4"
         fill="none"
         stroke="currentColor"
         viewBox="0 0 24 24">
      <path stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M6 18L18 6M6 6l12 12" />
    </svg>
    <span x-text="toastMessage"></span>
  </div>
</div>
