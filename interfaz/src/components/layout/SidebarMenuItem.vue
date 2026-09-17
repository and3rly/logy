<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { ChevronDown } from '@lucide/vue'
import { useAppStore } from '../../stores/app'

const props = defineProps({
  item: { type: Object, required: true },
  level: { type: Number, default: 0 },
})

const appStore = useAppStore()
const route = useRoute()
const contraido = computed(() => appStore.sidebarCollapsed && !appStore.mobileSidebarOpen)
const hasChildren = computed(() => props.item.children?.length > 0)
const descendantActive = computed(() => {
  const containsPath = (children) => children.some((child) =>
    child.url === route.path || containsPath(child.children || []))
  return hasChildren.value && containsPath(props.item.children)
})
const open = ref(descendantActive.value)

watch(descendantActive, (active) => {
  if (active) open.value = true
})

const toggleChildren = () => {
  if (contraido.value) {
    appStore.sidebarCollapsed = false
    open.value = true
    return
  }
  open.value = !open.value
}
</script>

<template>
  <div class="dynamic-menu-item" :class="`menu-level-${level}`">
    <button
      v-if="hasChildren"
      type="button"
      class="nav-item nav-group-button"
      :class="{ 'group-active': descendantActive }"
      :aria-expanded="open"
      :title="appStore.sidebarCollapsed ? item.name : undefined"
      @click="toggleChildren"
    >
      <i v-if="item.icon" :class="['database-menu-icon', item.icon]" aria-hidden="true"></i>
      <span>{{ item.name }}</span>
      <ChevronDown class="nav-chevron ml-auto transition-transform [&.rotated]:rotate-180" :class="{ rotated: open }" :size="15" />
    </button>

    <RouterLink
      v-else-if="item.url"
      :to="item.url"
      class="nav-item"
      active-class="menu-route-parent-active"
      exact-active-class="router-link-active"
      :class="{ 'submenu-item': level > 0 }"
      :title="appStore.sidebarCollapsed ? item.name : undefined"
    >
      <i v-if="item.icon" :class="['database-menu-icon', item.icon]" aria-hidden="true"></i>
      <span>{{ item.name }}</span>
    </RouterLink>

    <div v-else class="nav-item nav-item-disabled">
      <i v-if="item.icon" :class="['database-menu-icon', item.icon]" aria-hidden="true"></i>
      <span>{{ item.name }}</span>
    </div>

    <div v-if="hasChildren && open && !contraido" class="ml-5 pl-2">
      <SidebarMenuItem
        v-for="child in item.children"
        :key="child.id || `${child.name}-${child.url}`"
        :item="child"
        :level="level + 1"
      />
    </div>
  </div>
</template>
