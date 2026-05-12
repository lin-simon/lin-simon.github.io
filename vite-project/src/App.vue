<template>
  <main class="app">
    <div class="bg" aria-hidden="true">
      <FaultyTerminal
        :scale="2.2"
        :grid-mul="[2, 1]"
        :digit-size="1.4"
        :time-scale="reduceMotion ? 0 : 0.4"
        :scanline-intensity="0"
        :glitch-amount="reduceMotion ? 0 : 1"
        :flicker-amount="reduceMotion ? 0 : 1"
        :noise-amp="reduceMotion ? 0 : 0.7"
        :chromatic-aberration="reduceMotion ? 0 : 6"
        :dither="0"
        :curvature="0.15"
        tint="#A7EF9E"
        :mouse-react="!isTouch"
        :mouse-strength="0.35"
        :page-load-animation="true"
        :brightness="0.35"
      />
      <div class="bg-vignette"></div>
    </div>

    <Terminal class="hero-terminal" />

    <SocialDock />
  </main>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import FaultyTerminal from './components/FaultyTerminal.vue'
import Terminal from './components/Terminal.vue'
import SocialDock from './components/SocialDock.vue'

const reduceMotion = ref(false)
const isTouch = ref(false)

onMounted(() => {
  reduceMotion.value = window.matchMedia('(prefers-reduced-motion: reduce)').matches
  isTouch.value = window.matchMedia('(hover: none)').matches
})
</script>

<style scoped>
.app {
  position: relative;
  min-height: 100dvh;
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: clamp(1rem, 4vw, 3rem);
  padding-bottom: max(clamp(1rem, 4vw, 3rem), env(safe-area-inset-bottom));
  overflow: hidden;
}
.bg { position: fixed; inset: 0; z-index: 0; pointer-events: none; }
.bg-vignette {
  position: absolute; inset: 0;
  background: radial-gradient(ellipse at center, transparent 40%, rgba(0,0,0,0.55) 100%);
  pointer-events: none;
}
.hero-terminal { position: relative; z-index: 1; width: 100%; max-width: 920px; }
</style>
