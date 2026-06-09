<script setup lang="ts">
import { useConfigStore } from '@core/stores/config'

const store = useConfigStore()

store.skin = 'default'
definePageMeta({
  auth: false, // Disable sidebase-auth
  layout: 'front-page-layout',
  public: true,
})
</script>

<template>
  <div class="landing-page-wrapper">

    <div style="height: 90px;"></div>

    <VContainer>

    <VCard flat>
      <VCardText>
        <h2 class="text-h4 mb-4">Privacy Policy</h2>
        <p class="text-caption text-disabled mb-6">Effective Date: 01 May 2025</p>

        <p>
          At <strong>SaaS Boilerplate</strong>, your privacy is important to us. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our website, applications, and services.
        </p>

        <h4 class="text-h6 mt-6 mb-2">1. Information We Collect</h4>
        <ul>
          <li><strong>Personal Information:</strong> Name, email, phone number, date of birth, billing information, etc.</li>
          <li><strong>Usage Data:</strong> IP address, browser, access times, pages viewed.</li>
          <li><strong>Device Info:</strong> Device type, OS, mobile identifiers.</li>
        </ul>

        <h4 class="text-h6 mt-6 mb-2">2. How We Use Your Information</h4>
        <ul>
          <li>To provide and manage accounts, organizations, teams, branches, and subscriptions.</li>
          <li>To send notifications and respond to feedback.</li>
          <li>To improve user experience and ensure compliance with laws.</li>
        </ul>

        <h4 class="text-h6 mt-6 mb-2">3. Sharing Your Information</h4>
        <p>
          We do <strong>not</strong> sell your personal information. We may share it with:
        </p>
        <ul>
          <li>Trusted service providers (for example hosting, email, and billing providers).</li>
          <li>Authorities when required by law.</li>
        </ul>

        <h4 class="text-h6 mt-6 mb-2">4. Data Security</h4>
        <p>
          We implement standard security measures. However, no method is 100% secure. Please use strong passwords and keep your credentials safe.
        </p>

        <h4 class="text-h6 mt-6 mb-2">5. Your Rights and Choices</h4>
        <p>You may:</p>
        <ul>
          <li>Access, update, or delete your data.</li>
          <li>Withdraw consent or opt out of marketing.</li>
        </ul>
        <p>Email us at <a href="mailto:hello@example.com">hello@example.com</a> to request these actions.</p>

        <h4 class="text-h6 mt-6 mb-2">6. Cookies</h4>
        <p>We use cookies to remember preferences and analyze traffic. You can control cookies via your browser settings.</p>

        <h4 class="text-h6 mt-6 mb-2">7. Third-Party Links</h4>
        <p>We may link to other sites. We're not responsible for their privacy practices.</p>

        <h4 class="text-h6 mt-6 mb-2">8. Children’s Privacy</h4>
        <p>We do not knowingly collect data from children under 13. If we learn that we have, we will delete it immediately.</p>

        <h4 class="text-h6 mt-6 mb-2">9. Changes to This Policy</h4>
        <p>We may update this policy occasionally. Changes will be posted on this page with an updated effective date.</p>

        <h4 class="text-h6 mt-6 mb-2">10. Contact Us</h4>
        <p>If you have questions, reach out to:</p>
        <p>
          <strong>SaaS Boilerplate</strong><br>
          Email: <a href="mailto:hello@example.com">hello@example.com</a><br>
          Website: <a href="https://example.com" target="_blank">example.com</a>
        </p>
      </VCardText>
    </VCard>
    </VContainer>
    
  </div>
</template>

<style scoped lang="scss">

p, li {
  margin-bottom: 0.75rem;
}
ul {
  padding-left: 1.2rem;
}
</style>
