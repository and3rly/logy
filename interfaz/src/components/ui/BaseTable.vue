<script setup>
defineProps({
  columns: { type: Array, required: true },
  rows: { type: Array, required: true },
  rowKey: { type: String, default: 'id' },
  emptyText: { type: String, default: 'No hay registros disponibles.' },
  rowClass: { type: [String, Function], default: '' },
})

defineEmits(['row-click'])
</script>

<template>
  <div class="overflow-x-auto">
    <table class="w-full border-collapse text-left text-sm [&_th]:bg-soft [&_th]:px-5 [&_th]:py-2 [&_th]:font-medium [&_th]:text-muted [&_td]:border-t [&_td]:border-line [&_td]:px-5 [&_td]:py-2 [&_td]:whitespace-nowrap align-middle mb-0">
      <thead>
        <tr>
          <th v-for="column in columns" :key="column.key" scope="col" :class="column.class">
            {{ column.label }}
          </th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="row in rows"
          :key="row[rowKey]"
          class="transition-colors hover:bg-soft/70"
          :class="typeof rowClass === 'function' ? rowClass(row) : rowClass"
          @click="$emit('row-click', row)"
        >
          <td v-for="column in columns" :key="column.key" :class="column.class">
            <slot :name="`cell-${column.key}`" :row="row" :value="row[column.key]">
              {{ row[column.key] }}
            </slot>
          </td>
        </tr>
        <tr v-if="!rows.length">
          <td :colspan="columns.length" class="py-12! text-center text-muted">{{ emptyText }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
