<script setup lang="ts">
  const reportTypes = [
    { text: "Sales Summary Report", value: "sales" },
    { text: "Inventory Stock Report", value: "inventory" },
    { text: "Customer Activity Report", value: "customer" }
  ]

  const selectedDateStart = ref<Date | null>(null)
  const selectedDateEnd = ref<Date | null>(null)
  const selectedReport = ref<string | null>(null)

  const showData = () => {
    console.log("Selected Report Type:", selectedReport.value)
    console.log("Selected Date Start:", selectedDateStart.value)
    console.log("Selected Date End:", selectedDateEnd.value)
  }
  const downloadExcel = () => {
    console.log("Downloading Excel for Report Type:", selectedReport.value)
    // Implement your Excel download logic here
  }
</script>
<template>
  <div>
    <!-- 👉 report filters -->
    <VCard>

      <div class="d-flex flex-wrap gap-4 my-4 mx-6">
        <div class="font-weight-medium text-lg align-self-center">Report</div>

        <VSpacer />
        <div class="d-flex gap-4 flex-wrap align-center">
          <!-- 👉 Export button -->
          <!-- <VBtn color="primary" prepend-icon="tabler-plus" @click="() => { isCardAddDialogVisible = true }">
            Add Member
          </VBtn>

          <AddEditMemberDialog v-model:is-dialog-visible="isCardAddDialogVisible" @submit="fetchData()" />

          <AddEditMemberDialog v-model:is-dialog-visible="isCardEditDialogVisible" :resource="editedResource"
            @submit="fetchData()" /> -->
        </div>
      </div>

      <VDivider />

      <div class="px-6 py-6">
        <VRow>
          <VCol cols="12" md="4">
            <VSelect
              v-model="selectedReport"
              :items="reportTypes"
              item-title="text"
              item-value="value"
              label="Select Report Type"
              outlined
              clearable
            />
          </VCol>
        </VRow>

        <VRow class="mt-4">
          <VCol class="w-full">
            <AppDateTimePicker
              v-model="selectedDateStart"
              placeholder="Date Start"
              clearable
              clear-icon="tabler-x"
              style="min-width: 150px;"
            />
            <AppDateTimePicker
              v-model="selectedDateEnd"
              placeholder="Date End"
              clearable
              clear-icon="tabler-x"
              style="min-width: 150px;"
            />
            <VInput label="End Date" />
            <VInput label="Type" />
          </VCol>

          <VCol>
            <div  style="background : green">
            <VBtn color="primary" @click="showData">Show Data</VBtn>
            <VBtn color="secondary" @click="downloadExcel">Download Excel</VBtn>
            </div>
          </VCol>
        </VRow>

      </div>

      <div class="px-6 py-4">
        asdasdasd
        {{ selectedDateStart }}
        {{ selectedDateEnd }}
        {{ selectedReport }}
      </div>
  </VCard>
  </div>
</template>
