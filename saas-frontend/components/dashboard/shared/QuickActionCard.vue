<script setup lang="ts">
interface Props {
  title: string
  subtitle?: string
  icon: string
  to?: string
  color?: string
}

const props = withDefaults(defineProps<Props>(), {
  color: 'primary'
})
</script>

<template>
  <VCard
    :to="to"
    class="quick-action-card d-flex align-center pa-4 h-100"
    flat
  >
    <div class="action-icon-wrapper mr-4" :class="`text-${color} bg-${color}-subtle`">
      <VIcon :icon="icon" size="26" />
    </div>
    
    <div class="flex-grow-1">
      <h3 class="text-body-1 font-weight-bold mb-0 lh-1">{{ title }}</h3>
      <p v-if="subtitle" class="text-caption text-medium-emphasis mb-0 mt-1 line-clamp-1">
        {{ subtitle }}
      </p>
    </div>

    <!-- Arrow Icon visible on hover or always for clearer affordance -->
    <div class="action-arrow rounded-circle d-flex align-center justify-center">
      <VIcon icon="tabler-arrow-right" size="18" class="text-medium-emphasis" />
    </div>
  </VCard>
</template>

<style lang="scss" scoped>
.quick-action-card {
  border-radius: 16px;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  cursor: pointer;
  background: white;
  border: 1px solid rgba(0,0,0,0.04);
  box-shadow: 0 2px 8px rgba(0,0,0,0.03);

  &:hover {
    border-color: rgba(var(--v-theme-primary), 0.3);
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(var(--v-theme-primary), 0.1);
    
    .action-arrow {
      background: rgba(var(--v-theme-on-surface), 0.05);
      color: rgb(var(--v-theme-primary));
      transform: translateX(2px);
    }
  }
}

.action-icon-wrapper {
  width: 52px;
  height: 52px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: transform 0.2s ease;
  
  /* Dynamic subtle background */
  &.bg-primary-subtle {
    background-color: rgba(var(--v-theme-primary), 0.08);
  }
  &.bg-secondary-subtle {
    background-color: rgba(var(--v-theme-secondary), 0.08);
  }
  &.bg-success-subtle {
    background-color: rgba(var(--v-theme-success), 0.08);
  }
}

.action-arrow {
  width: 32px;
  height: 32px;
  transition: all 0.2s ease;
}

.line-clamp-1 {
  display: -webkit-box;
  -webkit-line-clamp: 1;
  line-clamp: 1; // Standard property
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
