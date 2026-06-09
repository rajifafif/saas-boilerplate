<script setup lang="ts">
import { useSnackbar } from '@/composables/useSnackbar'
import QRCode from 'qrcode'

const { showSnackbar } = useSnackbar()

interface Props {
  isDialogVisible: boolean
}
interface Emit {
  (e: 'update:isDialogVisible', value: boolean): void
}

const props = defineProps<Props>()

const { userData } = useUserData()
const qrCodeDataUrl = ref('')

const generateQR = async () => {
  // Guard: ensure we have user data with code
  if (!userData.value?.code) {
    console.warn('No user code available for QR generation')
    return
  }
  
  try {
    qrCodeDataUrl.value = await QRCode.toDataURL(userData.value.code, {
      width: 400,
      margin: 2,
    })
  } catch (err) {
    console.error('QR generation failed:', err)
  }
}

// Generate QR when dialog opens (not on mount, since dialog might not be visible initially)
watch(() => props.isDialogVisible, (visible) => {
  if (visible && !qrCodeDataUrl.value) {
    generateQR()
  }
}, { immediate: true })

</script>

<template>
  <VDialog
    :width="$vuetify.display.smAndDown ? 'auto' : 900 "
    :model-value="props.isDialogVisible"
    @update:model-value="val => $emit('update:isDialogVisible', val)"
  >
    <!-- 👉 Dialog close btn -->
    <DialogCloseBtn @click="$emit('update:isDialogVisible', false)" />
    <VCard class="pa-2 pa-sm-10">
      <VCardText>
        <!-- 👉 Title -->
        <h4 class="text-h4 text-center mb-2">
          QR Code
        </h4>

        <div class="d-flex justify-center">
          <!-- <img src="/assets/images/QR.png" style="max-width: 75vh; max-height: 75vh;"/> -->
          <div v-if="qrCodeDataUrl">
            <img :src="qrCodeDataUrl" alt="QR Code" style="max-width: 100%; height: auto; object-fit: contain;"  />
          </div>
        </div>
      </VCardText>
    </VCard>
  </VDialog>
</template>
