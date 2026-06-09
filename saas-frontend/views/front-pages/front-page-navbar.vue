<script setup lang="ts">
import { useWindowScroll } from '@vueuse/core'
import { useDisplay } from 'vuetify'

const props = defineProps({
  activeId: String,
})

const mobilenav = ref(false)
const display = useDisplay()
const { userData, isLoggedIn } = useUserData()

const { y } = useWindowScroll()
const route = useRoute()

// Close mobile nav on route change
watch(() => route.path, () => {
  mobilenav.value = false
})

// Close mobile nav on larger screens
watch(() => display.smAndUp, (isSmAndUp) => {
  if (isSmAndUp) mobilenav.value = false
})

const navItems = [
  { name: 'Home', to: '/', icon: 'tabler-home' },
  { name: 'Organizations', to: '/manage/organizations', icon: 'tabler-building' },
  { name: 'Team', to: '/manage/users/staff', icon: 'tabler-users' },
]
</script>

<template>
  <!-- 👉 Glassmorphism Navbar -->
  <div class="glass-navbar-wrapper">
    <nav class="glass-navbar" :class="{ 'scrolled': y > 50 }">
      <!-- Logo -->
      <NuxtLink to="/" class="navbar-brand text-high-emphasis font-weight-bold">
        SaaS Boilerplate
      </NuxtLink>

      <!-- Desktop Navigation -->
      <div class="nav-links d-none d-sm-flex">
        <NuxtLink
          v-for="item in navItems"
          :key="item.name"
          :to="item.to"
          class="nav-link"
          :class="{ 'active': route.path === item.to }"
        >
          {{ item.name }}
        </NuxtLink>

        <!-- Admin link (if admin) -->
        <NuxtLink
          v-if="userData && userData.roles_names?.includes('Administrator')"
          to="/manage"
          class="nav-link"
        >
          Manage
        </NuxtLink>
      </div>

      <!-- Desktop Actions -->
      <div class="nav-actions d-none d-sm-flex">
        <NuxtLink
          v-if="!isLoggedIn"
          to="/login"
          class="profile-btn"
        >
          <VIcon icon="tabler-user" size="18" />
          <span>Login</span>
        </NuxtLink>
        <NuxtLink
          v-else
          to="/profile"
          class="profile-btn"
        >
          <VIcon icon="tabler-user" size="18" />
          <span>Profile</span>
        </NuxtLink>
      </div>

      <!-- Mobile Menu Toggle -->
      <button 
        class="mobile-menu-btn d-sm-none"
        @click="mobilenav = !mobilenav"
        :class="{ 'active': mobilenav }"
      >
        <span class="hamburger-line"></span>
        <span class="hamburger-line"></span>
        <span class="hamburger-line"></span>
      </button>
    </nav>

    <!-- Mobile Dropdown Menu -->
    <Transition name="slide-down">
      <div v-if="mobilenav" class="mobile-menu d-sm-none">
        <NuxtLink
          v-for="item in navItems"
          :key="item.name"
          :to="item.to"
          class="mobile-nav-item"
          :class="{ 'active': route.path === item.to }"
          @click="mobilenav = false"
        >
          <VIcon :icon="item.icon" size="20" />
          <span>{{ item.name }}</span>
        </NuxtLink>

        <div class="mobile-divider"></div>

        <!-- Profile/Login -->
        <NuxtLink
          v-if="!isLoggedIn"
          to="/login"
          class="mobile-nav-item profile"
          @click="mobilenav = false"
        >
          <VIcon icon="tabler-login" size="20" />
          <span>Login</span>
        </NuxtLink>
        <NuxtLink
          v-else
          to="/profile"
          class="mobile-nav-item profile"
          @click="mobilenav = false"
        >
          <VIcon icon="tabler-user-circle" size="20" />
          <span>My Profile</span>
        </NuxtLink>
      </div>
    </Transition>
  </div>
</template>

<style lang="scss" scoped>
// Glassmorphism color palette
$primary-slate: #6B8CAE;
$secondary-teal: #7EB8B8;
$accent-rose: #C4A7A7;
$glass-white: rgba(255, 255, 255, 0.75);
$glass-border: rgba(255, 255, 255, 0.4);
$text-dark: #2C3E50;

.glass-navbar-wrapper {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1000;
  padding: 1rem;
  pointer-events: none;

  > * {
    pointer-events: auto;
  }
}

.glass-navbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.875rem 1.5rem;
  max-width: 1200px;
  margin: 0 auto;
  
  // Glassmorphism effect
  background: $glass-white;
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border: 1px solid $glass-border;
  border-radius: 16px;
  box-shadow: 
    0 8px 32px rgba(0, 0, 0, 0.08),
    0 0 0 1px rgba(255, 255, 255, 0.5) inset;
  
  transition: all 0.3s ease;

  &.scrolled {
    background: rgba(255, 255, 255, 0.9);
    box-shadow: 
      0 12px 40px rgba(0, 0, 0, 0.12),
      0 0 0 1px rgba(255, 255, 255, 0.6) inset;
  }
}

.navbar-brand {
  display: flex;
  align-items: center;
  
  img {
    height: 24px;
    transition: transform 0.3s ease;
    
    &:hover {
      transform: scale(1.05);
    }
  }
}

.nav-links {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.nav-link {
  position: relative;
  padding: 0.5rem 1rem;
  font-size: 0.9375rem;
  font-weight: 500;
  color: $text-dark;
  text-decoration: none;
  border-radius: 8px;
  transition: all 0.2s ease;

  &:hover {
    color: $primary-slate;
    background: rgba($primary-slate, 0.08);
  }

  &.active {
    color: $primary-slate;
    
    &::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 50%;
      transform: translateX(-50%);
      width: 20px;
      height: 3px;
      background: linear-gradient(135deg, $primary-slate, $secondary-teal);
      border-radius: 2px;
    }
  }
}

.nav-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.profile-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem 1.25rem;
  font-size: 0.875rem;
  font-weight: 600;
  color: white;
  text-decoration: none;
  background: linear-gradient(135deg, $primary-slate 0%, $secondary-teal 100%);
  border-radius: 10px;
  box-shadow: 0 4px 16px rgba($primary-slate, 0.35);
  transition: all 0.3s ease;

  &:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 24px rgba($primary-slate, 0.45);
  }

  &:active {
    transform: translateY(0);
  }
}

// Mobile Menu Button
.mobile-menu-btn {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  gap: 5px;
  width: 40px;
  height: 40px;
  padding: 0;
  background: transparent;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.2s ease;

  &:hover {
    background: rgba($primary-slate, 0.08);
  }

  .hamburger-line {
    width: 22px;
    height: 2px;
    background: $text-dark;
    border-radius: 2px;
    transition: all 0.3s ease;
  }

  &.active {
    .hamburger-line:nth-child(1) {
      transform: rotate(45deg) translate(5px, 5px);
    }
    .hamburger-line:nth-child(2) {
      opacity: 0;
    }
    .hamburger-line:nth-child(3) {
      transform: rotate(-45deg) translate(5px, -5px);
    }
  }
}

// Mobile Menu
.mobile-menu {
  margin-top: 0.5rem;
  padding: 0.75rem;
  max-width: 1200px;
  margin-left: auto;
  margin-right: auto;
  
  // Glassmorphism
  background: $glass-white;
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border: 1px solid $glass-border;
  border-radius: 16px;
  box-shadow: 
    0 8px 32px rgba(0, 0, 0, 0.08),
    0 0 0 1px rgba(255, 255, 255, 0.5) inset;
}

.mobile-nav-item {
  display: flex;
  align-items: center;
  gap: 0.875rem;
  padding: 1rem 1.25rem;
  font-size: 1rem;
  font-weight: 500;
  color: $text-dark;
  text-decoration: none;
  border-radius: 12px;
  transition: all 0.2s ease;

  &:hover, &.active {
    background: rgba($primary-slate, 0.1);
    color: $primary-slate;
  }

  &.profile {
    color: $primary-slate;
    font-weight: 600;
  }

  .v-icon {
    opacity: 0.7;
  }
}

.mobile-divider {
  height: 1px;
  margin: 0.5rem 1rem;
  background: rgba($primary-slate, 0.15);
}

// Slide down animation
.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.3s ease;
}

.slide-down-enter-from,
.slide-down-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>

