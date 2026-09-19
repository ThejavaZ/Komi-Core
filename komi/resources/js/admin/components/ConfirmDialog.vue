<template>
  <Teleport to="body">
    <transition name="fade">
      <div v-if="modelValue" class="fixed inset-0 z-[100] flex items-center justify-center p-4" @click.self="cancel">
        <div class="fixed inset-0 bg-black/50 transition-opacity" />
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden transform transition-all">
          <!-- Icon + Content -->
          <div class="p-6 text-center">
            <div v-if="icon" class="mx-auto w-12 h-12 rounded-full flex items-center justify-center mb-4" :class="iconBg">
              <component :is="icon" class="w-6 h-6" :class="iconColor" />
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-1">{{ title }}</h3>
            <p v-if="subtitle" class="text-sm text-gray-500">{{ subtitle }}</p>
          </div>

          <!-- Reason textarea (optional) -->
          <div v-if="showReason" class="px-6 pb-4">
            <label class="block text-xs font-medium text-gray-500 mb-1">{{ reasonLabel }}</label>
            <textarea
              v-model="reason"
              :rows="3"
              :placeholder="reasonPlaceholder"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"
            />
          </div>

          <!-- Actions -->
          <div class="flex border-t border-gray-100">
            <button
              class="flex-1 px-4 py-3 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors"
              @click="cancel"
            >
              {{ cancelText }}
            </button>
            <button
              class="flex-1 px-4 py-3 text-sm font-medium transition-colors"
              :class="confirmClass"
              @click="confirm"
            >
              {{ confirmText }}
            </button>
          </div>
        </div>
      </div>
    </transition>
  </Teleport>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
  icon: { type: [Object, null], default: null },
  iconBg: { type: String, default: 'bg-red-100' },
  iconColor: { type: String, default: 'text-red-600' },
  confirmText: { type: String, default: 'Confirmar' },
  cancelText: { type: String, default: 'Cancelar' },
  confirmClass: { type: String, default: 'text-white bg-red-600 hover:bg-red-700' },
  showReason: { type: Boolean, default: false },
  reasonLabel: { type: String, default: 'Motivo (opcional)' },
  reasonPlaceholder: { type: String, default: 'Escribe el motivo...' },
});

const emit = defineEmits(['update:modelValue', 'confirm', 'cancel']);

const reason = ref('');

watch(() => props.modelValue, (val) => {
  if (val) reason.value = '';
});

function confirm() {
  emit('confirm', reason.value);
  emit('update:modelValue', false);
}

function cancel() {
  emit('cancel');
  emit('update:modelValue', false);
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
