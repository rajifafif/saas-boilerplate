<script lang="ts" setup>
import { useNavigation } from '@/composables/useNavigation'
import Footer from '@/layouts/components/Footer.vue'
import NavbarThemeSwitcher from '@/layouts/components/NavbarThemeSwitcher.vue'
import UserProfile from '@/layouts/components/UserProfile.vue'
import { themeConfig } from '@themeConfig'

const { layoutAttrs, injectSkinClasses } = useSkins()

// ℹ️ This will inject classes in body tag for accurate styling
injectSkinClasses()

// Get navigation from backend
const { navigation, homeRoute, isLoading, fetchNavigation } = useNavigation()

// Fetch navigation on mount
onMounted(async () => {
  await fetchNavigation()
})
</script>

<template>
  <div
    v-bind="layoutAttrs"
    class="home-layout"
  >
    <!-- Top App Bar -->
    <VAppBar
      flat
      class="home-appbar"
    >
      <VAppBarTitle>
        <VImg
          :src="themeConfig.app.logo"
          :alt="themeConfig.app.title"
          max-height="32"
          max-width="120"
        />
      </VAppBarTitle>

      <VSpacer />

      <NavbarThemeSwitcher class="me-2" />
      <UserProfile />
    </VAppBar>

    <!-- Main Content -->
    <VMain class="home-main">
      <VContainer
        fluid
        class="pa-4 pa-md-6"
      >
        <!-- Quick Navigation Cards -->
        <div
          v-if="navigation.length > 0"
          class="mb-6"
        >
          <h6 class="text-h6 mb-4">
            Quick Actions
          </h6>
          <VRow>
            <template
              v-for="(section, sectionIndex) in navigation"
              :key="sectionIndex"
            >
              <VCol
                v-for="(item, itemIndex) in section.items || []"
                :key="`${sectionIndex}-${itemIndex}`"
                cols="6"
                sm="4"
                md="3"
              >
                <VCard
                  :to="item.to"
                  class="nav-card text-center pa-4"
                  hover
                  flat
                  border
                >
                  <VIcon
                    :icon="item.icon || 'tabler-circle'"
                    size="32"
                    class="mb-2 text-primary"
                  />
                  <div class="text-body-2 font-weight-medium">
                    {{ item.title }}
                  </div>
                </VCard>
              </VCol>
            </template>
          </VRow>
        </div>

        <!-- Loading State -->
        <div
          v-else-if="isLoading"
          class="text-center py-6"
        >
          <VProgressCircular
            indeterminate
            color="primary"
          />
        </div>

        <!-- Page Content -->
        <slot />
      </VContainer>
    </VMain>

    <!-- Footer -->
    <Footer />

    <!-- Bottom Navigation (Mobile) -->
    <VBottomNavigation
      v-if="navigation.length > 0"
      grow
      class="d-md-none home-bottom-nav"
    >
      <template
        v-for="(section, sectionIndex) in navigation"
        :key="sectionIndex"
      >
        <VBtn
          v-for="(item, itemIndex) in (section.items || []).slice(0, 4)"
          :key="`${sectionIndex}-${itemIndex}`"
          :to="item.to"
        >
          <VIcon :icon="item.icon || 'tabler-circle'" />
          <span>{{ item.title }}</span>
        </VBtn>
      </template>
    </VBottomNavigation>
  </div>
</template>

<style lang="scss" scoped>
.home-layout {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

.home-appbar {
  z-index: 1000;
}

.home-main {
  flex: 1;
  padding-block-end: 80px; // Space for bottom nav on mobile

  @media (min-width: 960px) {
    padding-block-end: 0;
  }
}

.nav-card {
  transition: transform 0.2s ease, box-shadow 0.2s ease;

  &:hover {
    transform: translateY(-2px);
  }
}

.home-bottom-nav {
  position: fixed;
  z-index: 1000;
  inset-block-end: 0;
  inset-inline: 0;
}
</style>
