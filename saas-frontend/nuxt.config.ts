import { fileURLToPath } from 'node:url'
import vuetify from 'vite-plugin-vuetify'
import svgLoader from 'vite-svg-loader'

// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({

  app: {
    head: {
      titleTemplate: '%s',
      title: 'SaaS Boilerplate',
      link: [
        { rel: 'manifest', href: '/manifest.json' },
        { rel: 'apple-touch-icon', href: '/icons/apple-touch-icon.png' },
        {
          rel: 'icon',
          type: 'image/x-icon',
          href: `/favicon.ico`,
        }, {
          rel: 'preconnect',
          href: 'https://fonts.googleapis.com',
        }, {
          rel: 'preconnect',
          href: 'https://fonts.gstatic.com',
          crossorigin: '',
        }, {
          rel: 'stylesheet',
          href: 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Instrument+Serif:ital@0;1&display=swap',
        }
      ],
      meta: [
        { name: 'theme-color', content: '#7367F0' }
      ]
    }
  },

  ssr: true, // Enable SSR globally

  // Hybrid Rendering Strategy
  routeRules: {
    // Marketing pages: Server-Side Rendered (SEO)
    '/': { ssr: true },
    '/marketing/**': { ssr: true },
    '/blog/**': { ssr: true },
    
    // Authenticated workspace: Client-Side Only for app-like interactions
    '/manage/**': { ssr: false },
    
    // Auth: Client-side
    '/login': { ssr: false },
    '/register': { ssr: false },
  },

  devServer: {
    host: '0.0.0.0',
    port: 3000
  },

  devtools: {
    enabled: true,
  },

  css: [
    '@core/scss/template/index.scss',
    '~/assets/styles/styles.scss', // Fixed alias issue
    '@/plugins/iconify/icons.css',
  ],

  /*
    ❗ Please read the docs before updating runtimeConfig
    https://nuxt.com/docs/guide/going-further/runtime-config
  */
  runtimeConfig: {
    // Private keys are only available on the server
    AUTH_ORIGIN: process.env.NUXT_PUBLIC_API_BASE_URL,
    AUTH_SECRET: process.env.AUTH_SECRET,

    // Public keys that are exposed to the client.
    public: {
      apiBaseUrl: process.env.NUXT_PUBLIC_API_BASE_URL || '/api',
      mapboxAccessToken: process.env.MAPBOX_ACCESS_TOKEN,
    },
    baseURL: process.env.NUXT_PUBLIC_BASE_URL
  },

  components: {
    dirs: [{
      path: '@/@core/components',
      pathPrefix: false,
    }, {
      path: '~/components/global',
      global: true,
    }, {
      path: '~/components',
      pathPrefix: false,
    }],
  },

  auth: {
    baseURL: process.env.NUXT_PUBLIC_API_BASE_URL || '/api',
    provider: {
      type: 'local',
      endpoints: {
        signIn: { path: '/login', method: 'post' },
        getSession: { path: '/profile' }
      },
      pages: {
        login: '/login'
      },
      token: {
        signInResponseTokenPointer: '/token',
        maxAgeInSeconds: 60 * 60 * 24 * 30, // 30 days
        cookieName: 'auth.token',
        sameSiteAttribute: 'lax',
        secureCookieAttribute: process.env.NODE_ENV === 'production',
        // Explicitly set token header config
        type: 'Bearer',
        headerName: 'Authorization',
      },
      session: {
        dataType: { id: 'string', email: 'string', name: 'string', role: '\'admin\' | \'guest\' | \'account\'', subscriptions: '{ id: number, status: \'ACTIVE\' | \'INACTIVE\' }[]' },
        dataResponsePointer: '/'
      },
      refresh: {
        isEnabled: false, // Disabled - using longer token TTL instead
        endpoint: { path: '/auth/refresh', method: 'post' },
        token: {
          signInResponseRefreshTokenPointer: '/refresh_token',
          refreshResponseTokenPointer: '/access_token',
          refreshRequestTokenPointer: '/refresh_token',
          cookieName: 'auth.refresh_token',
          maxAgeInSeconds: 60 * 60 * 24 * 30,
        },
      }
    },
    sessionRefresh: {
      // Disable periodic refresh to prevent logout loops
      enableOnWindowFocus: false,
      enablePeriodically: false,
    },
    globalAppMiddleware: {
      isEnabled: false,
    },
  },

  plugins: [
    '@/plugins/casl/index.ts',
    '@/plugins/vuetify/index.ts',
    '@/plugins/iconify/index.ts',
  ],

  imports: {
    dirs: ['./@core/utils', './@core/composable/', './plugins/*/composables/*', './types/'],
    autoImport: true
  },

  hooks: {
    'prerender:routes' ({ routes }) {
      routes.clear() // Do not generate any routes (except the defaults)
    }
  },

  experimental: {
    typedPages: true,
  },

  typescript: {
    tsConfig: {
      compilerOptions: {
        paths: {
          '@/*': ['../*'],
          '@themeConfig': ['../themeConfig.ts'],
          '@layouts/*': ['../@layouts/*'],
          '@layouts': ['../@layouts'],
          '@core/*': ['../@core/*'],
          '@core': ['../@core'],
          '@images/*': ['../assets/images/*'],
          '@styles/*': ['../assets/styles/*'],
          '@validators': ['../@core/utils/validators'],
          '@db/*': ['../server/fake-db/*'],
          '@api-utils/*': ['../server/utils/*'],
          '@types/*': ['../types/*'],
          '@stores/*': ['../stores/*'],
        },
      },
    },
  },

  // ℹ️ Disable source maps until this is resolved: https://github.com/vuetifyjs/vuetify-loader/issues/290
  sourcemap: {
    server: false,
    client: false,
  },

  vue: {
    compilerOptions: {
      isCustomElement: tag => tag === 'swiper-container' || tag === 'swiper-slide',
    },
  },

  vite: {
    server: {
      allowedHosts: true, // Allow all hosts for local dev with subdomains
    },
    define: { 'process.env': {} },

    resolve: {
      alias: {
        '@': fileURLToPath(new URL('.', import.meta.url)),
        '@themeConfig': fileURLToPath(new URL('./themeConfig.ts', import.meta.url)),
        '@core': fileURLToPath(new URL('./@core', import.meta.url)),
        '@layouts': fileURLToPath(new URL('./@layouts', import.meta.url)),
        '@images': fileURLToPath(new URL('./assets/images/', import.meta.url)),
        '@styles': fileURLToPath(new URL('./assets/styles/', import.meta.url)),
        '@configured-variables': fileURLToPath(new URL('./assets/styles/variables/_template.scss', import.meta.url)),
        '@db': fileURLToPath(new URL('./server/fake-db/', import.meta.url)),
        '@api-utils': fileURLToPath(new URL('./server/utils/', import.meta.url)),
      },
    },

    build: {
      chunkSizeWarningLimit: 5000,
      manifest: true,
    },

    optimizeDeps: {
      // exclude: ['vuetify'],
      entries: [
        './**/*.vue',
      ],
    },

    plugins: [
      svgLoader(),
      vuetify({
        autoImport: true,
        styles: {
          configFile: 'assets/styles/variables/_vuetify.scss',
        },
      }),
    ],

    css: {
      preprocessorOptions: {
        scss: {
          quietDeps: true // ✅ Suppresses Sass warnings from dependencies
        }
      }
    }
  },

  build: {
    transpile: ['vuetify'],
  },

  i18n: {
    vueI18n: './i18n.config.ts',
    bundle: {
      optimizeTranslationDirective: false
    }
  },

  modules: ['@vueuse/nuxt', '@nuxtjs/i18n', '@nuxtjs/device', '@sidebase/nuxt-auth',
  '@pinia/nuxt'],

  compatibilityDate: '2025-03-16',
})
