<script setup>
import { onMounted, ref } from 'vue'

defineProps({ url: { type: String, required: true } })
const emit = defineEmits(['close'])
const dialog = ref(null)

onMounted(() => dialog.value.showModal())

function closeFromBackdrop(event) {
  if (event.target === event.currentTarget) dialog.value.close()
}
</script>

<template>
  <dialog ref="dialog" class="m-auto h-[92vh] w-[92vw] max-w-5xl overflow-hidden rounded-lg border-0 bg-slate-800 p-0 shadow-2xl backdrop:bg-slate-950/75" @click="closeFromBackdrop" @close="emit('close')">
    <iframe :src="url" title="Documento PDF" class="size-full border-0" />
  </dialog>
</template>
