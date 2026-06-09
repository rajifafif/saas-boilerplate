<script lang="ts" setup>

import { useConfigStore } from '@/@core/stores/config'
import Footer from '@/views/front-pages/front-page-footer.vue'
import Navbar from '@/views/front-pages/front-page-navbar.vue'

const { injectSkinClasses } = useSkins()

// ℹ️ This will inject classes in body tag for accurate styling
injectSkinClasses()


const store = useConfigStore()

store.theme = 'light'

</script>

<template>
  <div
    class="layout-wrapper layout-blank"
    data-allow-mismatch
  >
    <div class="landing-page-wrapper">

      <Navbar :active-id="'home'" />
      
      <slot />

      <!-- 👉 Footer -->
      <Footer />
    </div>
  </div>
</template>

<style>
.layout-wrapper.layout-blank {
  flex-direction: column;
}
</style>
