<template>
  <nav class="dock" aria-label="Social links">
    <a
      v-for="link in links"
      :key="link.name"
      :href="link.url"
      :target="link.external ? '_blank' : undefined"
      :rel="link.external ? 'noopener noreferrer' : undefined"
      :aria-label="link.name"
      class="dock-item"
    >
      <span class="icon" v-html="link.svg"></span>
      <span class="label">{{ link.name }}</span>
    </a>
  </nav>
</template>

<script setup>
const links = [
  {
    name: 'github',
    url: 'https://github.com/lin-simon',
    external: true,
    svg: `<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 .5C5.65.5.5 5.65.5 12c0 5.08 3.29 9.39 7.86 10.91.58.11.79-.25.79-.56v-2c-3.2.7-3.88-1.36-3.88-1.36-.52-1.34-1.28-1.69-1.28-1.69-1.05-.72.08-.7.08-.7 1.16.08 1.77 1.19 1.77 1.19 1.03 1.77 2.71 1.26 3.37.97.1-.76.41-1.27.74-1.56-2.55-.29-5.24-1.28-5.24-5.7 0-1.26.45-2.29 1.18-3.1-.12-.29-.51-1.46.11-3.04 0 0 .97-.31 3.18 1.18a11.1 11.1 0 0 1 5.79 0c2.21-1.49 3.18-1.18 3.18-1.18.62 1.58.23 2.75.11 3.04.74.81 1.18 1.84 1.18 3.1 0 4.43-2.7 5.4-5.26 5.69.42.36.8 1.08.8 2.18v3.24c0 .31.21.68.8.56A11.5 11.5 0 0 0 23.5 12C23.5 5.65 18.35.5 12 .5Z"/></svg>`
  },
  {
    name: 'linkedin',
    url: 'https://www.linkedin.com/in/lin-simon/',
    external: true,
    svg: `<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.45 20.45h-3.55v-5.57c0-1.33-.02-3.04-1.85-3.04-1.85 0-2.13 1.45-2.13 2.95v5.66H9.36V9h3.41v1.56h.05c.48-.9 1.65-1.85 3.39-1.85 3.62 0 4.29 2.38 4.29 5.49v6.25ZM5.34 7.43a2.06 2.06 0 1 1 0-4.12 2.06 2.06 0 0 1 0 4.12ZM7.12 20.45H3.56V9h3.56v11.45ZM22.22 0H1.77C.79 0 0 .77 0 1.72v20.56C0 23.23.79 24 1.77 24h20.45c.98 0 1.78-.77 1.78-1.72V1.72C24 .77 23.2 0 22.22 0Z"/></svg>`
  },
  {
    name: 'resume',
    url: '/Simons_Resume.pdf',
    external: true,
    svg: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 13h6"/><path d="M9 17h6"/></svg>`
  },
  {
    name: 'email',
    url: 'mailto:lin.simon189@gmail.com',
    external: false,
    svg: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>`
  }
]
</script>

<style scoped>
.dock {
  position: fixed;
  top: 50%;
  left: clamp(0.75rem, 2.5vw, 1.75rem);
  transform: translateY(-50%);
  display: flex;
  flex-direction: column;
  gap: 0.7rem;
  padding: 0.7rem;
  background: rgba(10, 26, 18, 0.55);
  border: 1px solid rgba(116, 224, 138, 0.18);
  border-radius: 16px;
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  z-index: 5;
  animation: fade-in .8s ease .3s both;
}
@keyframes fade-in { from { opacity: 0; transform: translate(-8px, -50%); } to { opacity: 1; transform: translate(0, -50%); } }

.dock-item {
  position: relative;
  width: 3.2rem;
  height: 3.2rem;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #d4f5d4;
  border-radius: 12px;
  background: rgba(0, 0, 0, 0.25);
  border: 1px solid rgba(116, 224, 138, 0.12);
  transition: transform .18s ease, background .18s ease, color .18s ease, border-color .18s ease;
}
.dock-item:hover {
  background: rgba(116, 224, 138, 0.12);
  color: #74e08a;
  border-color: rgba(116, 224, 138, 0.45);
  transform: translateY(-2px);
}
.icon { width: 1.5rem; height: 1.5rem; display: inline-flex; }
.icon :deep(svg) { width: 100%; height: 100%; }

.label {
  position: absolute;
  left: calc(100% + 0.6rem);
  top: 50%;
  transform: translateY(-50%) translateX(-4px);
  font-size: 0.75rem;
  font-family: 'JetBrains Mono', Menlo, monospace;
  color: #d4f5d4;
  background: rgba(10, 26, 18, 0.9);
  border: 1px solid rgba(116, 224, 138, 0.2);
  padding: 0.2rem 0.5rem;
  border-radius: 6px;
  opacity: 0;
  pointer-events: none;
  white-space: nowrap;
  transition: opacity .15s ease, transform .15s ease;
}
.dock-item:hover .label,
.dock-item:focus-visible .label {
  opacity: 1;
  transform: translateY(-50%) translateX(0);
}

/* Mobile / touch: horizontal dock pinned to the bottom, fixed so it
   does not push terminal layout. */
@media (max-width: 720px), (hover: none) {
  .dock {
    top: auto;
    left: 50%;
    bottom: calc(0.6rem + env(safe-area-inset-bottom));
    transform: translateX(-50%);
    flex-direction: row;
    gap: 0.5rem;
    padding: 0.45rem;
    border-radius: 14px;
    animation: fade-in-mobile .8s ease .3s both;
  }
  @keyframes fade-in-mobile {
    from { opacity: 0; transform: translate(-50%, 8px); }
    to   { opacity: 1; transform: translate(-50%, 0); }
  }
  .dock-item { width: 2.5rem; height: 2.5rem; border-radius: 10px; }
  .icon { width: 1.15rem; height: 1.15rem; }
  .label { display: none; }
}
</style>
