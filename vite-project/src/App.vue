<template>
  <a-layout style="min-height: 100vh">
    <a-content>
      <Hero />

      <!-- Sized wrapper so the effect has space but doesn't affect the page height elsewhere -->
    </a-content>

    <a-footer>
      <SocialButtons />
    </a-footer>
  </a-layout>
</template>

<script setup>
import Hero from './components/Hero.vue'
import SocialButtons from './components/SocialButtons.vue'

</script>

<style scoped>

</style>
