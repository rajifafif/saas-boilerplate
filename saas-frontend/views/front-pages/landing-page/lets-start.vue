<script setup lang="ts">

</script>

<template>
  <VContainer id="lets-start">
    <div class="">
      <div style="height: 100px"></div>
      <div class="headers d-flex justify-center flex-column align-center">
      
        <h4 class="d-flex align-center text-h4 mb-1 flex-wrap justify-center">
          <div class="position-relative me-2">
            <div class="section-title">
              Let's
            </div>
          </div>
          Start
        </h4>
      </div>
      <div class="w-100 d-flex gap-4 justify-center mb-12 mt-8">
        <VBtn to="/register">Create Organization</VBtn>
        <VBtn to="/login">Open Dashboard</VBtn>
      </div>

      <div style="height: 100px"></div>
    </div>
  </VContainer>
</template>

<style lang="scss" scoped>

.headers {
  margin-block-end: 1rem;
}

.our-team {
  margin-block: 5.25rem;
}

@media (max-width: 1264px) {
  .our-team {
    margin-block-end: 1rem;
  }
}

.team-card {
  border-radius: 90px 20px 6px 6px;
}

.section-title {
  font-size: 24px;
  font-weight: 800;
  line-height: 36px;
}

.section-title::after {
  position: absolute;
  background: url("../../../assets/images/front-pages/icons/section-title-icon.png") no-repeat left bottom;
  background-size: contain;
  block-size: 100%;
  content: "";
  font-weight: 800;
  inline-size: 120%;
  inset-block-end: 12%;
  inset-inline-start: -12%;
}

.team-image {
  max-width: 180px;
  width: 100%;
  height: auto;
  aspect-ratio: 1/1.7;
  object-fit: cover;
  border-radius: 5%;
  border: 4px solid #eee;
  box-shadow: 0 2px 12px rgba(0,0,0,0.07);
}
</style>
