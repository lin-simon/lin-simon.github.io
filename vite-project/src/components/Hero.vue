<template>
  <section id="hero" class="hero">

    <div class="hero-bg">
      <FaultyTerminal
        :scale="2.5"
        :grid-mul="[2, 1]"
        :digit-size="1.4"
        :time-scale="0.5"
        :pause="false"
        :scanline-intensity="0"
        :glitch-amount="2"
        :flicker-amount="2"
        :noise-amp="1"
        :chromatic-aberration="20"
        :dither="0"
        :curvature="0.2"
        tint="#A7EF9E"
        :mouse-react="true"
        :mouse-strength="0.5"
        :page-load-animation="true"
        :brightness="0.4"
      />
    </div>

    <div class="hero-content">
      <div class="terminal-window">
        <div class="terminal-header">
          <div class="terminal-controls">
            <span class="ctrl red"></span>
            <span class="ctrl yellow"></span>
            <span class="ctrl green"></span>
          </div>
          <div class="terminal-title">my_portfolio</div>
        </div>
        <div class="terminal-body">
          <p class="terminal-content">
            <span v-html="terminalText"></span><span class="cursor"></span>
          </p>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import FaultyTerminal from './FaultyTerminal.vue' 
import { ref, onMounted } from 'vue'

const rawLines = [
  '> C:\\Users\\Simon Lin\\>type university.txt',
  '<span class="green-text">CS</span> @ <span class="blue-text">Toronto Metropolitan University</span>',
  '',
  '> C:\\Users\\Simon Lin\\>type work_exp.json',
  '<span class="brace">{</span>',
  "   <span class='key'>company</span>: <span class='json-value'>'Magna International'</span>,",
  "   <span class='key'>position</span>: <span class='json-value'>'Software Engineer'</span>,",
  '<span class="brace">}</span>',
  '> C:\\Users\\Simon Lin\\>'
]

const lines = rawLines.map(line => {
  if (line.startsWith('>')) {
    const first = line.indexOf('>')
    const second = line.indexOf('>', first + 1)
    return { isCommand: true, prefix: line.slice(0, second + 1), text: line.slice(second + 1) }
  }
  return { isCommand: false, text: line }
})

const terminalText = ref('')

function typeLines() {
  let li = 0, ci = -1
  function next() {
    if (li >= lines.length) return
    const { isCommand, prefix = '', text = '' } = lines[li]
    if (isCommand) {
      if (ci < 0) { terminalText.value += prefix; ci = 0; setTimeout(next, 300) }
      else if (ci < text.length) { terminalText.value += text.charAt(ci++); setTimeout(next, 55) }
      else { if (li < lines.length - 1) terminalText.value += '<br>'; li++; ci = -1; setTimeout(next, 200) }
    } else { terminalText.value += text + '<br>'; li++; setTimeout(next, 200) }
  }
  setTimeout(next, 600)
}
onMounted(typeLines)
</script>

<style scoped>

.hero { position: relative; display: flex; align-items: center; justify-content: center; height: 100vh; background: transparent; color: #fff; }


.hero-bg { position: absolute; inset: 0; z-index: 0; pointer-events: none; }


.hero-content { position: relative; z-index: 1; max-width: 800px; width: 100%; text-align: center; }


.terminal-window { background:#000; margin:2rem auto; width:80%; max-width:1200px; height:50vh; border:1px solid #333; border-radius:6px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.5); transform-origin: bottom center; opacity:0; transform:translateY(100vh) scaleY(0); animation:openWindow .8s cubic-bezier(.25,1.25,.5,1) forwards; }
@keyframes openWindow { 0%{opacity:0;transform:translateY(100vh) scaleY(0)} 60%{opacity:1;transform:translateY(-10px) scaleY(1.1)} 100%{opacity:1;transform:translateY(0) scaleY(1)} }

.terminal-header { background:#222; padding:.5rem 1rem; display:flex; align-items:center; justify-content:center; position:relative; font-family:Menlo,Monaco,'Courier New',monospace; }
.terminal-controls { position:absolute; left:1rem; display:flex; gap:.5rem; }
.ctrl { width:.75rem; height:.75rem; border-radius:50%; }
.ctrl.red { background:#FC605C; } .ctrl.yellow { background:#FDBC40; } .ctrl.green { background:#34C84A; }
.terminal-title { color:#aaa; font-size:.875rem; font-family:Menlo,Monaco,'Courier New',monospace; margin:0; }

.terminal-body { padding:1rem; font-family:Menlo,Monaco,'Courier New',monospace; font-size:1.25rem; line-height:1.5; color:#fff; height:calc(100% - 2.5rem); overflow-y:auto; text-align:left; scrollbar-width:none; -ms-overflow-style:none; }
.terminal-body::-webkit-scrollbar { width:0; height:0; }
.terminal-content { margin:0; white-space:pre-wrap; }

.cursor { display:inline-block; width:.75ch; height:1em; background:#ffffffaf; animation:blink 1.2s step-start infinite; vertical-align:baseline; margin-left:2px; margin-bottom:-2px; }
@keyframes blink { 0%,50%{opacity:.9} 50.01%,100%{opacity:0} }

/* JSON coloring */
:deep(.brace){ color:#ffb730 } :deep(.key){ color:#61afef } :deep(.json-value){ color:#ca813d } :deep(.blue-text){ color:#40a9ff } :deep(.green-text){ color:#54bc7c }

@media (max-width:600px){ .terminal-window{ height:60vh; width:90% } }

/* selection */
::selection { background:#ffffff98; color:#000 } ::-moz-selection { background:#ffffff98; color:#000 }
</style>
