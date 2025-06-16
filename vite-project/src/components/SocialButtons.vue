<template>
  <div class="socials">
    <div
      v-for="(link, idx) in links"
      :key="link.name"
      class="social-app"
      :style="{ top: positions[idx].y + 'px', left: positions[idx].x + 'px' }"
      @mousedown="startDrag(idx, $event)"
    >
      <a-button
        type="text"
        class="app-button"
        :href="link.url"
        target="_blank"
        @click.stop
      >
        <component :is="link.icon" class="app-icon" />
      </a-button>
      <div class="app-label">{{ link.name }}</div>
    </div>
  </div>
</template>

<script setup>
import { reactive, onMounted } from 'vue'
import { GithubOutlined, LinkedinOutlined, FileTextOutlined } from '@ant-design/icons-vue'

const links = [
  { name: 'github.exe',   url: 'https://github.com/lin-simon',     icon: GithubOutlined },
  { name: 'linked_in.exe', url: 'https://www.linkedin.com/in/lin-simon/', icon: LinkedinOutlined },
  { name: 'resume.pdf',   url: 'vite-project/src/assets/Simons_Resume.pdf',                     icon: FileTextOutlined }
]

// store x/y for each icon
const positions = reactive(links.map(() => ({ x: 32, y: 0 })))
let dragInfo = null

onMounted(() => {
  const gap = 2 * 30// 2rem
  const buttonHeight = 3.5 * 16 // 3.5rem
  const totalHeight = links.length * (buttonHeight + gap) - gap
  const startY = window.innerHeight / 2 - totalHeight / 2

  links.forEach((_, idx) => {
    positions[idx].x = 40
    positions[idx].y = startY + idx * (buttonHeight + gap)
  })
})

function startDrag(idx, event) {
  event.preventDefault()
  dragInfo = {
    idx,
    startX: event.clientX,
    startY: event.clientY,
    origX: positions[idx].x,
    origY: positions[idx].y
  }
  window.addEventListener('mousemove', onDrag)
  window.addEventListener('mouseup', endDrag)
}

function onDrag(e) {
  if (!dragInfo) return
  const dx = e.clientX - dragInfo.startX
  const dy = e.clientY - dragInfo.startY
  positions[dragInfo.idx].x = dragInfo.origX + dx
  positions[dragInfo.idx].y = dragInfo.origY + dy
}

function endDrag() {
  window.removeEventListener('mousemove', onDrag)
  window.removeEventListener('mouseup', endDrag)
  dragInfo = null
}
</script>

<style scoped>
.socials {
  position: fixed;
  top: 0;
  left: 0;
  width: 0;
  height: 100vh;
  pointer-events: none;
}

.social-app {
  position: fixed;
  display: flex;
  flex-direction: column;
  align-items: center;
  cursor: grab;
  pointer-events: all;
  width: 3.5rem; /* fixed width so icons stack aligned */
}


.app-button {
  width: 4rem;
  height: 4rem;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #1e1e1e;
  border: 1.5px solid #333;
  border-radius: 6px;
  box-shadow: 0 2px 5px rgba(0,0,0,0.5);
  transition: transform 0.2s, box-shadow 0.2s;
}



.social-app:hover .app-button {
  box-shadow: 0 2px 5px rgba(0,0,0,0.5);
  cursor: grab;
}

.app-button:hover {
  transform: translateY(-4px);
}

.app-icon {
  font-size: 1.75rem;
  color: #fff;
}

.app-label {
  margin-top: 0.5rem;
  font-size: 0.875rem;
  color: #aaa;
  font-family: Menlo, Monaco, 'Courier New', monospace;
}
</style>
