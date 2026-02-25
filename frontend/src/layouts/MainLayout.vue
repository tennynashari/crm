<template>
  <div class="flex h-screen bg-gray-50">
    <!-- Sidebar -->
    <aside
      class="w-64 bg-white shadow-lg hidden lg:block"
      :class="{ 'hidden': !sidebarOpen }"
    >
      <div class="p-6 border-b border-gray-200">
        <div class="flex items-center justify-center mb-2">
          <img src="/logo.png" alt="Logo" class="h-12" />
        </div>
        <h1 class="text-xl font-bold text-primary-600 text-center">FlowCRM</h1>
      </div>
      <nav class="mt-6">
        <router-link
          to="/"
          class="flex items-center px-6 py-3 text-gray-700 hover:bg-primary-50 hover:text-primary-600 transition-colors"
          active-class="bg-primary-50 text-primary-600 border-r-4 border-primary-600"
        >
          <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
          </svg>
          {{ $t('sidebar.dashboard') }}
        </router-link>
        <router-link
          to="/customers"
          class="flex items-center px-6 py-3 text-gray-700 hover:bg-primary-50 hover:text-primary-600 transition-colors"
          active-class="bg-primary-50 text-primary-600 border-r-4 border-primary-600"
        >
          <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
          {{ $t('sidebar.customers') }}
        </router-link>
        
        <!-- Broadcast Email Dropdown -->
        <div>
          <button
            @click="broadcastOpen = !broadcastOpen"
            class="flex items-center justify-between w-full px-6 py-3 text-gray-700 hover:bg-primary-50 hover:text-primary-600 transition-colors"
            :class="{ 'bg-primary-50 text-primary-600': broadcastOpen }"
          >
            <div class="flex items-center">
              <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
              </svg>
              {{ $t('sidebar.broadcastEmail') }}
            </div>
            <svg
              class="w-4 h-4 transition-transform"
              :class="{ 'rotate-180': broadcastOpen }"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>
          
          <div v-show="broadcastOpen" class="bg-gray-50">
            <router-link
              to="/broadcast-email"
              class="flex items-center px-12 py-2 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-600 transition-colors"
              active-class="bg-primary-50 text-primary-600"
            >
              {{ $t('sidebar.sendBroadcast') }}
            </router-link>
            <router-link
              to="/broadcast-email/drafts"
              class="flex items-center px-12 py-2 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-600 transition-colors"
              active-class="bg-primary-50 text-primary-600"
            >
              {{ $t('sidebar.drafts') }}
            </router-link>
            <router-link
              to="/broadcast-email/history"
              class="flex items-center px-12 py-2 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-600 transition-colors"
              active-class="bg-primary-50 text-primary-600"
            >
              {{ $t('sidebar.history') }}
            </router-link>
          </div>
        </div>
        
        <!-- Settings Dropdown -->
        <div>
          <button
            @click="settingsOpen = !settingsOpen"
            class="flex items-center justify-between w-full px-6 py-3 text-gray-700 hover:bg-primary-50 hover:text-primary-600 transition-colors"
            :class="{ 'bg-primary-50 text-primary-600': settingsOpen }"
          >
            <div class="flex items-center">
              <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              {{ $t('sidebar.settings') }}
            </div>
            <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': settingsOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>
          
          <!-- Submenu -->
          <div v-show="settingsOpen" class="bg-gray-50">
            <router-link
              v-if="user?.role === 'admin'"
              to="/areas"
              class="flex items-center px-6 py-2 pl-14 text-sm text-gray-600 hover:bg-primary-50 hover:text-primary-600 transition-colors"
              active-class="bg-primary-100 text-primary-600"
            >
              <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              {{ $t('sidebar.areas') }}
            </router-link>
            <router-link
              v-if="user?.role === 'admin'"
              to="/sales"
              class="flex items-center px-6 py-2 pl-14 text-sm text-gray-600 hover:bg-primary-50 hover:text-primary-600 transition-colors"
              active-class="bg-primary-100 text-primary-600"
            >
              <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
              </svg>
              {{ $t('sidebar.sales') }}
            </router-link>
            <router-link
              to="/settings"
              class="flex items-center px-6 py-2 pl-14 text-sm text-gray-600 hover:bg-primary-50 hover:text-primary-600 transition-colors"
              active-class="bg-primary-100 text-primary-600"
            >
              <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
              </svg>
              {{ $t('sidebar.emailConfig') }}
            </router-link>
          </div>
        </div>
      </nav>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
      <!-- Header -->
      <header class="bg-white shadow-sm z-10">
        <div class="flex items-center justify-between px-4 py-4 lg:px-8">
          <button
            @click="sidebarOpen = !sidebarOpen"
            class="lg:hidden text-gray-600"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </button>

          <h2 class="text-xl font-semibold text-gray-800 lg:block hidden">
            {{ currentPageTitle }}
          </h2>

          <div class="flex items-center space-x-4">
            <!-- Language Switcher -->
            <div class="flex items-center space-x-2 border-r pr-4">
              <button
                @click="changeLanguage('en')"
                class="px-2 py-1 text-xs rounded transition-colors"
                :class="currentLocale === 'en' ? 'bg-primary-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'"
              >
                EN
              </button>
              <button
                @click="changeLanguage('id')"
                class="px-2 py-1 text-xs rounded transition-colors"
                :class="currentLocale === 'id' ? 'bg-primary-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'"
              >
                ID
              </button>
            </div>
            
            <span class="text-sm text-gray-600">{{ user?.name }}</span>
            <button
              @click="handleLogout"
              class="text-sm text-red-600 hover:text-red-700"
            >
              {{ $t('header.logout') }}
            </button>
          </div>
        </div>
      </header>

      <!-- Mobile Sidebar -->
      <div
        v-if="sidebarOpen"
        class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden"
        @click="sidebarOpen = false"
      >
        <aside class="w-64 bg-white h-full shadow-lg" @click.stop>
          <div class="p-6">
            <h1 class="text-2xl font-bold text-primary-600">FlowCRM</h1>
          </div>
          <nav class="mt-6">
            <router-link
              to="/"
              class="flex items-center px-6 py-3 text-gray-700 hover:bg-primary-50 hover:text-primary-600 transition-colors"
              active-class="bg-primary-50 text-primary-600 border-r-4 border-primary-600"
              @click="sidebarOpen = false"
            >
              <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
              </svg>
              {{ $t('sidebar.dashboard') }}
            </router-link>
            <router-link
              to="/customers"
              class="flex items-center px-6 py-3 text-gray-700 hover:bg-primary-50 hover:text-primary-600 transition-colors"
              active-class="bg-primary-50 text-primary-600 border-r-4 border-primary-600"
              @click="sidebarOpen = false"
            >
              <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
              {{ $t('sidebar.customers') }}
            </router-link>
            
            <!-- Broadcast Email Dropdown Mobile -->
            <div>
              <button
                @click="broadcastOpenMobile = !broadcastOpenMobile"
                class="flex items-center justify-between w-full px-6 py-3 text-gray-700 hover:bg-primary-50 hover:text-primary-600 transition-colors"
                :class="{ 'bg-primary-50 text-primary-600': broadcastOpenMobile }"
              >
                <div class="flex items-center">
                  <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                  </svg>
                  {{ $t('sidebar.broadcastEmail') }}
                </div>
                <svg
                  class="w-4 h-4 transition-transform"
                  :class="{ 'rotate-180': broadcastOpenMobile }"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </button>
              
              <div v-show="broadcastOpenMobile" class="bg-gray-50">
                <router-link
                  to="/broadcast-email"
                  class="flex items-center px-12 py-2 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-600 transition-colors"
                  active-class="bg-primary-50 text-primary-600"
                  @click="sidebarOpen = false"
                >
                  {{ $t('sidebar.sendBroadcast') }}
                </router-link>
                <router-link
                  to="/broadcast-email/drafts"
                  class="flex items-center px-12 py-2 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-600 transition-colors"
                  active-class="bg-primary-50 text-primary-600"
                  @click="sidebarOpen = false"
                >
                  {{ $t('sidebar.drafts') }}
                </router-link>
                <router-link
                  to="/broadcast-email/history"
                  class="flex items-center px-12 py-2 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-600 transition-colors"
                  active-class="bg-primary-50 text-primary-600"
                  @click="sidebarOpen = false"
                >
                  {{ $t('sidebar.history') }}
                </router-link>
              </div>
            </div>
            
            <!-- Settings Dropdown Mobile -->
            <div>
              <button
                @click="settingsOpenMobile = !settingsOpenMobile"
                class="flex items-center justify-between w-full px-6 py-3 text-gray-700 hover:bg-primary-50 hover:text-primary-600 transition-colors"
                :class="{ 'bg-primary-50 text-primary-600': settingsOpenMobile }"
              >
                <div class="flex items-center">
                  <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  {{ $t('sidebar.settings') }}
                </div>
                <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': settingsOpenMobile }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </button>
              
              <!-- Submenu Mobile -->
              <div v-show="settingsOpenMobile" class="bg-gray-50">
                <router-link
                  v-if="user?.role === 'admin'"
                  to="/areas"
                  class="flex items-center px-6 py-2 pl-14 text-sm text-gray-600 hover:bg-primary-50 hover:text-primary-600 transition-colors"
                  active-class="bg-primary-100 text-primary-600"
                  @click="sidebarOpen = false"
                >
                  <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  {{ $t('sidebar.areas') }}
                </router-link>
                <router-link
                  v-if="user?.role === 'admin'"
                  to="/sales"
                  class="flex items-center px-6 py-2 pl-14 text-sm text-gray-600 hover:bg-primary-50 hover:text-primary-600 transition-colors"
                  active-class="bg-primary-100 text-primary-600"
                  @click="sidebarOpen = false"
                >
                  <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                  </svg>
                  {{ $t('sidebar.sales') }}
                </router-link>
                <router-link
                  to="/settings"
                  class="flex items-center px-6 py-2 pl-14 text-sm text-gray-600 hover:bg-primary-50 hover:text-primary-600 transition-colors"
                  active-class="bg-primary-100 text-primary-600"
                  @click="sidebarOpen = false"
                >
                  <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                  </svg>
                  {{ $t('sidebar.emailConfig') }}
                </router-link>
              </div>
            </div>
          </nav>
        </aside>
      </div>

      <!-- Page Content -->
      <main class="flex-1 overflow-y-auto bg-gray-50 p-4 lg:p-8">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useI18n } from 'vue-i18n'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const { locale } = useI18n()

const sidebarOpen = ref(false)
const settingsOpen = ref(false)
const broadcastOpen = ref(false)
const settingsOpenMobile = ref(false)
const broadcastOpenMobile = ref(false)

const user = computed(() => authStore.user)

const currentLocale = computed(() => locale.value)

const currentPageTitle = computed(() => {
  return route.name || 'FlowCRM'
})

const changeLanguage = (lang) => {
  locale.value = lang
  localStorage.setItem('locale', lang)
}

const handleLogout = async () => {
  try {
    await authStore.logout()
  } catch (error) {
    console.error('Logout error:', error)
  } finally {
    // Always redirect to login after logout attempt
    router.push('/login')
  }
}
</script>
