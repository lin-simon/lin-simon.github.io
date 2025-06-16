<template>
    <a-layout style="min-height: 100vh">
      <a-content>
        <Hero />
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

<style>
body {
  margin: 0;
  background: #000000;
}
</style>