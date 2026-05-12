<template>
  <section
    class="terminal"
    :class="[`theme-${theme}`, { booting: isBooting }]"
    @click="focusInput"
  >
    <!-- macOS-style header -->
    <header class="tw-header">
      <div class="tw-controls" aria-hidden="true">
        <span class="ctrl red"></span>
        <span class="ctrl yellow"></span>
        <span class="ctrl green"></span>
      </div>
      <div class="tw-title">
        <span class="tw-title-text">simon@portfolio</span>
        <span class="tw-title-sep">—</span>
        <span class="tw-title-cmd">{{ lastCommand || 'zsh' }}</span>
      </div>
      <div class="tw-tabs" aria-hidden="true">
        <span class="tab active">~</span>
      </div>
    </header>

    <!-- Body -->
    <div ref="bodyRef" class="tw-body" @scroll="onScroll">
      <div class="tw-stream">
        <div
          v-for="(line, i) in stream"
          :key="i"
          class="tw-line"
          :class="[line.kind, { 'fade-in': line.fade }]"
        >
          <template v-if="line.kind === 'input'">
            <span class="prompt">
              <span class="user">simon</span><span class="at">@</span><span class="host">portfolio</span>
              <span class="path"> ~</span>
              <span class="sigil"> %</span>
            </span>
            <span class="cmd">{{ line.text }}</span>
          </template>
          <template v-else>
            <span v-html="line.html"></span>
          </template>
        </div>

        <!-- Active prompt -->
        <div v-if="!isBooting" class="tw-line tw-active">
          <span class="prompt">
            <span class="user">simon</span><span class="at">@</span><span class="host">portfolio</span>
            <span class="path"> ~</span>
            <span class="sigil"> %</span>
          </span>
          <span v-if="isTouchOnly" class="input-wrap touch-hint">
            <span class="dim">tap a button below ↓</span>
            <span class="cursor"></span>
          </span>
          <span v-else class="input-wrap">
            <span class="input-shadow">{{ input }}</span>
            <input
              ref="inputRef"
              v-model="input"
              type="text"
              autocomplete="off"
              autocapitalize="off"
              autocorrect="off"
              spellcheck="false"
              aria-label="Terminal input"
              :disabled="typing"
              @keydown="onKey"
              @blur="onBlur"
              @focus="inputFocused = true"
            />
            <span class="cursor" :class="{ idle: !inputFocused }"></span>
          </span>
        </div>
      </div>
    </div>

    <!-- Suggestion chips -->
    <div class="tw-suggest" v-if="!isBooting">
      <button
        v-for="s in suggestions"
        :key="s"
        class="chip"
        @click.stop="runChip(s)"
        :disabled="typing"
        :aria-label="`Run ${s}`"
      >{{ s }}</button>
    </div>
  </section>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, nextTick, watch } from 'vue'

/* ============================================================
 *  Content — edit data; the shell renders it.
 * ============================================================ */
const me = {
  name: 'Simon Lin',
  role: 'Software Engineer',
  school: 'Toronto Metropolitan University',
  program: 'B.Sc. Computer Science (Co-op)',
  location: 'Toronto, ON',
  email: 'lin.simon189@gmail.com',
  gpa: '4.26 / 4.33',
  honors: "Dean's List (2021 – 2025)",
  github: 'lin-simon',
  linkedin: 'lin-simon',
  blurb:
    "I build for the web — fast UIs, clean APIs, the occasional shader. Currently shipping at Magna International while finishing my CS degree at TMU."
}

const skills = {
  Languages:  ['Python', 'TypeScript', 'JavaScript', 'C', 'C#', 'Java', 'SQL', 'Bash'],
  Frameworks: ['React', 'Vue.js', 'Three.js', 'Flask', '.NET', 'OpenCV', 'Selenium'],
  Tools:      ['AWS', 'Azure', 'Docker', 'Redis', 'Git', 'Jira', 'Azure DevOps'],
}

const experience = [
  {
    role: 'Software Engineer Co-op',
    company: 'Magna International',
    where: 'Newmarket, ON',
    period: 'May 2025 — Dec 2025',
    bullets: [
      '<v>Developed</v> and deployed a <hl>full-stack web app</hl> (<b class="t-py">Python</b> + <b class="t-fl">Flask</b> + <b class="t-rt">React</b>) on <b class="t-aws">AWS</b> to accelerate <hl2>Finite Element Analysis</hl2> workflows for <em class="num">15+</em> global engineers.',
      '<v>Built</v> a <hl>3D FEA model viewer</hl> with <b class="t-3js">Three.js</b> to visualize <hl2>complex tensile stresses</hl2> directly in the browser.',
      '<v>Shipped</v> a <hl>CATIA automation tool</hl> that cut <hl2>manual CAD data entry</hl2> time by <em class="num">70%</em> for cross-functional engineering teams.'
    ]
  },
  {
    role: 'Software Engineer Co-op',
    company: 'SOTI',
    where: 'Mississauga, ON',
    period: 'Apr 2024 — Aug 2024',
    bullets: [
      '<v>Developed</v> <hl>web application features</hl> in <b class="t-cs">C#</b> and <b class="t-net">.NET</b> against technical and customer specs.',
      '<v>Implemented</v> <b class="t-api">REST APIs</b> that improved <hl2>client–backend response times</hl2> by <em class="num">35%</em>.',
      '<v>Designed</v> <hl>normalized relational schemas</hl> in <b class="t-sql">Microsoft SQL Server</b>, improving query performance across <em class="num">5+</em> core product tables.',
      '<v>Collaborated</v> in an <b class="t-pl">Agile CI/CD</b> pipeline with weekly planning and code reviews.'
    ]
  },
  {
    role: 'Software Automation Engineer Co-op',
    company: 'SOTI',
    where: 'Mississauga, ON',
    period: 'Sep 2023 — Apr 2024',
    bullets: [
      '<v>Authored</v> <hl>automated test plans</hl>, cases, and unit tests in <b class="t-cs">C#</b>, <b class="t-sel">Selenium</b>, and <b class="t-az">Azure DevOps</b>.',
      '<v>Discovered</v> and <v>resolved</v> <em class="num">155+</em> defects across <hl2>product UI and dashboards</hl2>.',
      '<v>Reduced</v> <hl>SQL retrieval time</hl> by <em class="num">27%</em> by optimizing legacy queries.'
    ]
  },
  {
    role: 'Python Coding Instructor',
    company: 'The STEAM Project',
    where: 'Richmond Hill, ON',
    period: 'Jul 2021 — Aug 2021',
    bullets: [
      '<v>Taught</v> <em class="num">20</em> grade 7–8 summer-camp students a creative <hl>Python fundamentals</hl> curriculum.',
      '<v>Co-built</v> <hl2>weekly interactive lessons</hl2> with fellow instructors.'
    ]
  },
  {
    role: 'Embedded Firmware Engineer · TMU Formula SAE',
    company: 'Extracurricular',
    where: 'Toronto, ON',
    period: 'Sep 2021 — Present',
    bullets: [
      '<v>Wrote</v> <b class="t-c">C</b> firmware for <hl>Arduino microcontrollers</hl> to control and poll vehicle subsystems.',
      '<v>Built</v> a <hl>Python telemetry dashboard</hl> supporting <hl2>testing workflows and real-time diagnostics</hl2>.'
    ]
  }
]

const projects = [
  {
    name: 'NBAction',
    period: 'Sep 2023 — Dec 2024',
    blurb: 'Deep-learning model that detects basketball actions to analyze live games.',
    bullets: [
      '<v>Trained</v> a <hl>deep-learning action-detection model</hl> on live basketball footage.',
      '<v>Optimized</v> the <hl>video processing pipeline</hl> with <hl2>dynamic frame sampling</hl2>, cutting live latency by <em class="num">68%</em>.',
      '<v>Fine-tuned</v> the model to <em class="num">92%</em> detection accuracy across all object classes.'
    ],
    stack: ['Python', 'PyTorch', 'OpenCV'],
    link: 'https://github.com/lin-simon'
  },
  {
    name: 'linsimon.com',
    period: '2025',
    blurb: 'This site — a Vue + WebGL terminal portfolio with an interactive shell and glitch shader. Zero framework bloat.',
    bullets: [
      '<v>Wrote</v> a custom <hl>GLSL fragment shader</hl> for the glitch CRT background.',
      '<v>Built</v> a hand-rolled <hl>interactive zsh-style shell</hl> with history, autocomplete, and themes.'
    ],
    stack: ['Vue 3', 'Vite', 'OGL', 'GLSL'],
    link: 'https://github.com/lin-simon/lin-simon.github.io'
  }
]

/* ============================================================
 *  Shell state
 * ============================================================ */
const themes = ['green', 'amber', 'matrix', 'mono']
const theme = ref(localStorage.getItem('term-theme') || 'mono')
watch(theme, v => localStorage.setItem('term-theme', v))

const stream = ref([])
const input = ref('')
const inputRef = ref(null)
const bodyRef = ref(null)
const inputFocused = ref(false)
const isBooting = ref(true)
const cmdHistory = ref([])
const historyIdx = ref(-1)
const lastCommand = ref('')
const typing = ref(false)
const isTouchOnly = ref(false)
let stickToBottom = true
let skipRequested = false

const suggestions = ['about_me', 'experience', 'projects', 'skills', 'contact', 'resume', 'clear terminal']

/* ============================================================
 *  Helpers
 * ============================================================ */
const esc = (s) => String(s)
  .replace(/&/g, '&amp;')
  .replace(/</g, '&lt;')
  .replace(/>/g, '&gt;')

const cmdSpan = (name) => `<button class="inline-cmd" data-cmd="${name}">${name}</button>`

const link = (label, url) => {
  const safe = esc(label)
  const ext = url.startsWith('http') || url.endsWith('.pdf')
  return `<a class="link" href="${url}" ${ext ? 'target="_blank" rel="noopener noreferrer"' : ''}>${safe}</a>`
}

let pending = []
let pinPromptTop = false

function out(html, opts = {}) { pending.push({ html, ...opts }) }
function blank() { pending.push({ html: '&nbsp;' }) }

async function flush() {
  if (typing.value) return
  typing.value = true
  skipRequested = false
  const batch = pending
  pending = []
  const pin = pinPromptTop
  for (let i = 0; i < batch.length; i++) {
    const item = batch[i]
    if (skipRequested) {
      stream.value.push({ kind: 'output', html: item.html, fade: false })
    } else {
      stream.value.push({ kind: 'output', html: item.html, fade: true })
      await nextTick()
      if (pin && i === 0) {
        // anchor the first card near the top so subsequent ones stack visibly below
        const all = bodyRef.value?.querySelectorAll('.tw-line.output')
        const firstCard = all?.[all.length - 1]
        firstCard?.scrollIntoView({ block: 'start', behavior: 'smooth' })
      } else if (!pin) {
        scrollToBottom()
      }
      await sleep(item.delay ?? 55)
    }
  }
  typing.value = false
  if (!pin) scrollToBottom()
  pinPromptTop = false
  await nextTick()
  focusInput()
}

function skip() { skipRequested = true }

const scrollToBottom = () => {
  if (bodyRef.value && stickToBottom) {
    bodyRef.value.scrollTop = bodyRef.value.scrollHeight
  }
}

const onScroll = () => {
  if (!bodyRef.value) return
  const el = bodyRef.value
  stickToBottom = el.scrollHeight - el.scrollTop - el.clientHeight < 40
}

const sleep = (ms) => new Promise(r => setTimeout(r, ms))

/* ============================================================
 *  Commands
 * ============================================================ */
const commands = {
  help: () => {
    out(`<span class="dim">commands — click or type:</span>`)
    const entries = [
      ['about_me',   'a snapshot of who I am'],
      ['experience', 'work history with detail'],
      ['projects',   'featured projects'],
      ['skills',     'tech stack'],
      ['contact',    'how to reach me + resume'],
      ['resume',     'open my resume (PDF)'],
      ['theme',      'theme <green|amber|matrix|mono>'],
      ['banner',     'show the banner again'],
      ['clear terminal', 'wipe the screen']
    ]
    entries.forEach(([c, d]) => out(`  ${cmdSpan(c).padEnd(46, ' ')}<span class="dim">${esc(d)}</span>`))
  },

  about_me: () => {
    const row = (label, value) => `<div class="me-row"><span class="me-label">${label}</span><span class="me-value">${value}</span></div>`
    out(`<div class="me-card">
      <div class="me-head"><span class="me-name">${esc(me.name)}</span></div>
      <div class="me-body">
        ${row('role',     `<span class="amber">${esc(me.role)}</span>`)}
        ${row('program',  `<span class="purple">${esc(me.program)}</span>`)}
        ${row('school',   `<span class="school">${esc(me.school)}</span>`)}
        ${row('location', `<span class="loc">${esc(me.location)} 🇨🇦</span>`)}
        ${row('gpa',      `<span class="yellow">${esc(me.gpa)}</span> <span class="dim">·</span> <span class="ok">${esc(me.honors)}</span>`)}
        ${row('github',   `<span class="link-static">@${esc(me.github)}</span>`)}
      </div>
    </div>`)
    blank()
    out(`<span class="dim">try:</span> ${cmdSpan('experience')}  ${cmdSpan('projects')}  ${cmdSpan('contact')}`)
  },
  whoami: function () { return commands.about_me() },

  skills: () => {
    for (const [group, items] of Object.entries(skills)) {
      const tags = items.map(s => `<span class="tag">${esc(s)}</span>`).join(' ')
      out(`<span class="key">${esc(group).padEnd(11, ' ')}</span>${tags}`)
    }
  },

  experience: () => {
    experience.forEach((job, idx) => {
      const bullets = job.bullets.map(b => `<div class="card-row"><span class="card-bullet">›</span><span>${b}</span></div>`).join('')
      out(
        `<div class="job-card">
          <div class="card-head">
            <span class="card-title">▸ ${esc(job.role)}</span>
            <span class="card-period">${esc(job.period)}</span>
          </div>
          <div class="card-sub">
            <span class="cyan">${esc(job.company)}</span>
            <span class="card-dot">·</span>
            <span class="loc">${esc(job.where)}</span>
          </div>
          <div class="card-body">${bullets}</div>
        </div>`,
        { delay: idx === 0 ? 200 : 950 }
      )
    })
  },

  projects: () => {
    projects.forEach((p, idx) => {
      const bullets = p.bullets.map(b => `<div class="card-row"><span class="card-bullet">›</span><span>${b}</span></div>`).join('')
      const tags = p.stack.map(s => `<span class="tag">${esc(s)}</span>`).join(' ')
      out(
        `<div class="job-card project-card">
          <div class="card-head">
            <span class="card-title">◆ ${esc(p.name)}</span>
            <span class="card-period">${esc(p.period)}</span>
          </div>
          <div class="card-sub">
            <span class="dim">${esc(p.blurb)}</span>
          </div>
          <div class="card-body">${bullets}</div>
          <div class="card-foot">
            <span class="card-tags">${tags}</span>
            ${link('→ repository', p.link)}
          </div>
        </div>`,
        { delay: idx === 0 ? 200 : 950 }
      )
    })
  },

  contact: () => {
    out(`<span class="dim">reach me at:</span>`)
    out(`  <span class="dim">email   </span> ${link(me.email, `mailto:${me.email}`)}`)
    out(`  <span class="dim">github  </span> ${link(`github.com/${me.github}`, `https://github.com/${me.github}`)}`)
    out(`  <span class="dim">linkedin</span> ${link(`linkedin.com/in/${me.linkedin}`, `https://www.linkedin.com/in/${me.linkedin}/`)}`)
    out(`  <span class="dim">resume  </span> ${link('Simons_Resume.pdf', '/Simons_Resume.pdf')}`)
  },

  resume: () => {
    out(`<span class="dim">opening</span> <span class="cyan">Simons_Resume.pdf</span> <span class="dim">in a new tab…</span>`)
    setTimeout(() => window.open('/Simons_Resume.pdf', '_blank', 'noopener'), 200)
  },

  theme: (arg) => {
    if (!arg) {
      out(`<span class="dim">current:</span> <span class="cyan">${theme.value}</span>`)
      out(`<span class="dim">usage:</span> theme ${themes.map(t => `<button class="inline-cmd" data-cmd="theme ${t}">${t}</button>`).join(' ')}`)
      return
    }
    if (!themes.includes(arg)) { out(`<span class="err">theme: '${esc(arg)}' is not a theme</span>`); return }
    theme.value = arg
    out(`<span class="dim">theme →</span> <span class="cyan">${arg}</span>`)
  },

  banner: () => printBanner(),

  clear: () => { stream.value = []; pending = [] },
  cls:   () => { stream.value = []; pending = [] },
}

/* ============================================================
 *  Runner
 * ============================================================ */
const PIN_TOP_CMDS = new Set(['experience', 'projects'])

async function run(raw) {
  const text = raw.trim()
  stream.value.push({ kind: 'input', text: raw })
  lastCommand.value = text.split(/\s+/)[0] || 'zsh'
  await nextTick()
  if (!text) { scrollToBottom(); return }

  const [name, ...args] = text.split(/\s+/)
  const key = name.toLowerCase()
  const fn = commands[key]

  if (PIN_TOP_CMDS.has(key)) {
    pinPromptTop = true
    stickToBottom = false
  } else {
    scrollToBottom()
  }

  if (!fn) {
    out(`<span class="err">command not found:</span> ${esc(name)} <span class="dim">— try</span> ${cmdSpan('help')}`)
  } else {
    fn(args.join(' '))
  }
  await flush()
}

function onKey(e) {
  if (typing.value) {
    if (e.key === 'Enter' || e.key === 'Escape') { e.preventDefault(); skip() }
    return
  }
  if (e.key === 'Enter') {
    e.preventDefault()
    const val = input.value
    if (val.trim()) {
      cmdHistory.value.push(val)
      historyIdx.value = cmdHistory.value.length
    }
    input.value = ''
    run(val)
  } else if (e.key === 'ArrowUp') {
    e.preventDefault()
    if (cmdHistory.value.length === 0) return
    historyIdx.value = Math.max(0, historyIdx.value - 1)
    input.value = cmdHistory.value[historyIdx.value] || ''
  } else if (e.key === 'ArrowDown') {
    e.preventDefault()
    if (cmdHistory.value.length === 0) return
    historyIdx.value = Math.min(cmdHistory.value.length, historyIdx.value + 1)
    input.value = cmdHistory.value[historyIdx.value] || ''
  } else if (e.key === 'Tab') {
    e.preventDefault()
    const prefix = input.value.trim()
    if (!prefix) return
    const matches = Object.keys(commands).filter(c => c && c.startsWith(prefix))
    if (matches.length === 1) input.value = matches[0]
  } else if (e.key === 'l' && (e.ctrlKey || e.metaKey)) {
    e.preventDefault()
    commands.clear()
  }
}

function runChip(s) {
  if (typing.value) { skip(); return }
  input.value = ''
  run(s)
  focusInput()
}

function focusInput() {
  if (isBooting.value || typing.value || isTouchOnly.value) return
  inputRef.value?.focus({ preventScroll: true })
}

function onBlur() { inputFocused.value = false }

/* ============================================================
 *  Boot + Banner
 * ============================================================ */
function printBanner() {
  const banner =
` ███████╗██╗███╗   ███╗ ██████╗ ███╗   ██╗     ██╗     ██╗███╗   ██╗
 ██╔════╝██║████╗ ████║██╔═══██╗████╗  ██║     ██║     ██║████╗  ██║
 ███████╗██║██╔████╔██║██║   ██║██╔██╗ ██║     ██║     ██║██╔██╗ ██║
 ╚════██║██║██║╚██╔╝██║██║   ██║██║╚██╗██║     ██║     ██║██║╚██╗██║
 ███████║██║██║ ╚═╝ ██║╚██████╔╝██║ ╚████║     ███████╗██║██║ ╚████║
 ╚══════╝╚═╝╚═╝     ╚═╝ ╚═════╝╚═╝  ╚═══╝     ╚══════╝╚═╝╚═╝  ╚═══╝`
  out(`<pre class="banner">${banner}</pre>`, { delay: 80 })
  out(`<span class="amber">${esc(me.role)}</span> <span class="dim">·</span> <span class="school">${esc(me.school)}</span> <span class="dim">·</span> <span class="loc">${esc(me.location)}</span>`)
  blank()
  out(`<span class="dim">type</span> ${cmdSpan('help')} <span class="dim">for commands, or jump to</span> ${cmdSpan('about_me')} <span class="dim">/</span> ${cmdSpan('projects')}`)
  blank()
}

async function boot() {
  typing.value = true
  const lines = [
    `<span class="dim">booting simon.os v4.2.6 …</span>`,
    `<span class="ok">✓</span> <span class="dim">kernel loaded</span>`,
    `<span class="ok">✓</span> <span class="dim">mounting /home/simon</span>`,
    `<span class="ok">✓</span> <span class="dim">last login: ${new Date().toLocaleString()}</span>`,
  ]
  for (const l of lines) {
    stream.value.push({ kind: 'output', html: l, fade: true })
    await nextTick()
    scrollToBottom()
    await sleep(130)
  }
  stream.value.push({ kind: 'output', html: '&nbsp;', fade: false })
  typing.value = false
  printBanner()
  await flush()
  isBooting.value = false
  await nextTick()
  focusInput()
  inputFocused.value = true
}

/* ============================================================
 *  Lifecycle
 * ============================================================ */
function onBodyClick(e) {
  const t = e.target.closest('[data-cmd]')
  if (t) { runChip(t.dataset.cmd) }
}

function onWindowFocus() { if (!isBooting.value && !typing.value) focusInput() }

onMounted(async () => {
  await nextTick()
  isTouchOnly.value = window.matchMedia('(pointer: coarse)').matches
  window.addEventListener('focus', onWindowFocus)
  bodyRef.value?.addEventListener('click', onBodyClick)
  boot()
})

onBeforeUnmount(() => {
  window.removeEventListener('focus', onWindowFocus)
})
</script>

<style scoped>
/* ============================================================
 *  Theme tokens — varied palette per theme
 * ============================================================ */
.terminal {
  /* base */
  --bg: #06100a;
  --bg-2: #0a1a12;
  --header: #0d1c14;
  --border: #1f3a2a;
  --fg: #e3f4e6;
  --dim: #6a8a72;

  /* accents */
  --accent: #74e08a;     /* primary green */
  --cyan:   #74c7ff;     /* schools, names */
  --amber:  #ffb84d;     /* roles, headlines */
  --pink:   #ff9ec7;     /* location */
  --purple: #c794f0;     /* periods/dates */
  --yellow: #ffd966;     /* numbers / metrics */
  --ok:     #74e08a;
  --err:    #ff7a7a;
  --key:    #8fd4ff;
  --link:   #74e08a;
  --tag-bg: rgba(116,224,138,0.10);
  --tag-fg: #b5ecbf;

  --shadow: 0 30px 80px -20px rgba(0,0,0,0.7), 0 0 0 1px rgba(116,224,138,0.06);
}
.theme-amber {
  --bg: #100a05; --bg-2: #1a1208; --header: #1c1409; --border: #3a2e1f;
  --fg: #ffe8c0; --dim: #8a7a55; --accent: #ffb84d;
  --cyan:   #6cd0c2;  --amber: #ffd380;  --pink: #ffa07a;  --purple: #d9a05b; --yellow: #ffe066;
  --ok: #ffb84d; --link: #ffb84d; --tag-fg: #ffd98a; --tag-bg: rgba(255,184,77,0.10); --key: #ffd98a;
}
.theme-matrix {
  --bg: #000; --bg-2: #03100a; --header: #061b10; --border: #1a4a25;
  --fg: #bfffc8; --dim: #4a8a55; --accent: #00ff66;
  --cyan: #66ffd6; --amber: #aeff75; --pink: #d6ff8a; --purple: #88ffae; --yellow: #f0ff66;
  --ok: #00ff66; --link: #00ff66; --tag-fg: #99ffb0; --tag-bg: rgba(0,255,102,0.10); --key: #66ffd6;
}
.theme-mono {
  --bg: #0a0a0a; --bg-2: #111; --header: #141414; --border: #2a2a2a;
  --fg: #e6e6e6; --dim: #8a8a8a; --accent: #ffffff;
  --cyan: #cfcfcf; --amber: #ffffff; --pink: #b8b8b8; --purple: #a8a8a8; --yellow: #f0f0f0;
  --ok: #ffffff; --link: #ffffff; --tag-fg: #d6d6d6; --tag-bg: rgba(255,255,255,0.06); --key: #d6d6d6;
}

/* ============================================================
 *  Shell
 * ============================================================ */
.terminal {
  position: relative;
  display: flex; flex-direction: column;
  background: linear-gradient(180deg, var(--bg-2), var(--bg));
  border: 1px solid var(--border);
  border-radius: 12px;
  box-shadow: var(--shadow);
  color: var(--fg);
  font-family: 'JetBrains Mono', 'SF Mono', Menlo, Monaco, 'Courier New', monospace;
  overflow: hidden;
  height: min(82dvh, 680px);
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
  animation: rise .7s cubic-bezier(.2,.9,.2,1) both;
}
@keyframes rise { from { opacity:0; transform: translateY(28px) scale(.985); } to { opacity:1; transform:none; } }

/* Header */
.tw-header {
  display: flex; align-items: center;
  background: var(--header);
  border-bottom: 1px solid var(--border);
  padding: 0.55rem 0.9rem;
  gap: 0.75rem;
  user-select: none;
  position: relative;
}
.tw-controls { display: flex; gap: .4rem; }
.ctrl { width: .7rem; height: .7rem; border-radius: 50%; box-shadow: inset 0 0 0 0.5px rgba(0,0,0,.25); }
.ctrl.red { background: #ff5f57; }
.ctrl.yellow { background: #febc2e; }
.ctrl.green { background: #28c840; }
.tw-title {
  position: absolute; left: 50%; transform: translateX(-50%);
  font-size: .8rem; color: var(--dim); display: flex; gap: .45rem; align-items: baseline; white-space: nowrap;
  max-width: 60%; overflow: hidden; text-overflow: ellipsis;
}
.tw-title-text { color: var(--fg); opacity: .8; }
.tw-title-cmd { color: var(--amber); }
.tw-title-size { font-size: .72rem; opacity: .7; }
.tw-title-sep { opacity: .35; }
.tw-tabs { margin-left: auto; display: flex; gap: .25rem; font-size: .75rem; }
.tab { padding: 1px 8px; border-radius: 6px; color: var(--dim); }
.tab.active { background: rgba(255,255,255,0.04); color: var(--fg); }

/* Body */
.tw-body {
  flex: 1; min-height: 0;
  overflow-y: auto;
  overflow-x: hidden;
  padding: 1rem clamp(.75rem, 2vw, 1.25rem) 1.25rem;
  font-size: clamp(.82rem, 1.6vw, 0.98rem);
  line-height: 1.55;
}
.tw-body :deep(pre) { max-width: 100%; overflow-x: auto; scrollbar-width: thin; }
.tw-stream { display: flex; flex-direction: column; }
.tw-line { white-space: pre-wrap; word-break: break-word; }
.tw-line + .tw-line { margin-top: 2px; }
.tw-line.fade-in { animation: line-in .22s ease both; }
@keyframes line-in {
  from { opacity: 0; transform: translateY(3px); filter: blur(0.5px); }
  to   { opacity: 1; transform: none; filter: none; }
}

/* Prompt */
.prompt { color: var(--dim); margin-right: .5ch; }
.prompt .user { color: var(--accent); }
.prompt .at { color: var(--dim); }
.prompt .host { color: var(--cyan); }
.prompt .path { color: var(--amber); }
.prompt .sigil { color: var(--accent); }

.cmd { color: var(--fg); }

/* Active input */
.tw-active { position: relative; }
.input-wrap { position: relative; display: inline-flex; align-items: center; flex: 1; }
.input-wrap input {
  position: absolute; inset: 0;
  width: 100%; height: 100%;
  background: transparent; border: 0; outline: 0;
  color: transparent; caret-color: transparent;
  font: inherit; padding: 0;
}
.input-wrap input:disabled { cursor: progress; }
.touch-hint { display: inline-flex; align-items: center; gap: 0.5ch; }
.input-shadow { color: var(--fg); white-space: pre; }
.cursor {
  display: inline-block; width: .55ch; height: 1.05em;
  background: var(--accent); margin-left: 1px;
  animation: blink 1.05s steps(2) infinite;
  vertical-align: -2px;
}
.cursor.idle { animation: none; opacity: .35; }
@keyframes blink { 50% { opacity: 0; } }

/* Output token styles */
:deep(.dim)    { color: var(--dim); }
:deep(.accent) { color: var(--accent); }
:deep(.amber)  { color: var(--amber); }
:deep(.cyan)   { color: var(--cyan); }
:deep(.pink)   { color: var(--pink); }
:deep(.loc)    { color: #eaf1ec; font-weight: 500; }
:deep(.school) {
  color: #ffd966;
  font-weight: 700;
  text-shadow: 0 0 14px rgba(255, 217, 102, 0.35);
  letter-spacing: 0.2px;
}
:deep(.purple) { color: var(--purple); }
:deep(.yellow), :deep(.num) { color: var(--yellow); font-weight: 600; font-style: normal; }

/* In-bullet content highlights */
:deep(v) { color: var(--accent); font-weight: 600; font-style: normal; }            /* action verbs */
:deep(hl) { color: var(--pink); font-weight: 500; font-style: normal; }             /* key artifact / what */
:deep(hl2) { color: #9ad8ff; font-weight: 500; font-style: normal; opacity: 0.95; } /* secondary domain term */
:deep(.key)    { color: var(--key); }
:deep(.ok)     { color: var(--ok); }
:deep(.err)    { color: var(--err); }
:deep(.bold)   { font-weight: 700; }

/* Tech tokens — distinct hues for resume callouts */
:deep(.t-py)   { color: #4584c4; font-weight: 600; }   /* Python blue */
:deep(.t-fl)   { color: #e0e0e0; font-weight: 600; }   /* Flask */
:deep(.t-rt)   { color: #61dafb; font-weight: 600; }   /* React */
:deep(.t-aws)  { color: #ff9900; font-weight: 600; }   /* AWS */
:deep(.t-3js)  { color: #e8e8e8; font-weight: 600; }   /* Three.js */
:deep(.t-cs)   { color: #9b6dff; font-weight: 600; }   /* C# */
:deep(.t-c)    { color: #88a0c0; font-weight: 600; }
:deep(.t-net)  { color: #b58aff; font-weight: 600; }
:deep(.t-api)  { color: #74c7ff; font-weight: 600; }
:deep(.t-sql)  { color: #ff8a65; font-weight: 600; }
:deep(.t-sel)  { color: #65c466; font-weight: 600; }
:deep(.t-az)   { color: #4cc2ff; font-weight: 600; }
:deep(.t-pl)   { color: #ffd966; font-weight: 600; }   /* generic platform/tooling */

:deep(.link)   { color: var(--link); text-decoration: none; border-bottom: 1px dashed currentColor; }
:deep(.link:hover) { background: var(--tag-bg); }
:deep(.link-static) { color: var(--cyan); }

:deep(.tag) {
  display: inline-block; padding: 1px 8px; margin: 0 2px 2px 0;
  border-radius: 999px; background: var(--tag-bg); color: var(--tag-fg);
  font-size: .82em; border: 1px solid rgba(255,255,255,0.04);
}
:deep(.banner) {
  color: var(--accent); margin: 0; line-height: 1.1;
  font-size: clamp(.5rem, 1.25vw, .78rem);
  text-shadow: 0 0 14px rgba(116,224,138,0.4);
}
:deep(.card) { color: var(--fg); margin: 0; }

/* about_me card (CSS, no monospace box-drawing) */
:deep(.me-card) {
  display: flex; flex-direction: column;
  margin: 0.25rem 0 0.5rem;
  padding: 0.7rem 0.9rem 0.75rem;
  background: rgba(255,255,255,0.018);
  border: 1px solid var(--border);
  border-left: 3px solid var(--accent);
  border-radius: 8px;
  max-width: 100%;
}
:deep(.me-head) {
  padding-bottom: 0.5rem;
  margin-bottom: 0.55rem;
  border-bottom: 1px dashed var(--border);
}
:deep(.me-name) {
  color: var(--cyan); font-weight: 700; font-size: 1.05em; letter-spacing: 0.2px;
}
:deep(.me-body) { display: grid; grid-template-columns: auto 1fr; gap: 4px 1rem; }
:deep(.me-row) { display: contents; }
:deep(.me-label) { color: var(--dim); white-space: nowrap; }
:deep(.me-value) { color: var(--fg); min-width: 0; word-break: break-word; }

/* Job + project cards */
:deep(.job-card) {
  display: flex; flex-direction: column;
  margin: 0.5rem 0 0.75rem;
  padding: 0.7rem 0.9rem 0.75rem;
  background: rgba(255,255,255,0.018);
  border: 1px solid var(--border);
  border-left: 3px solid var(--amber);
  border-radius: 8px;
  position: relative;
  transition: background .2s ease, border-color .2s ease, transform .2s ease;
}
:deep(.job-card:hover) {
  background: rgba(255,255,255,0.035);
  border-color: rgba(255,184,77,0.45);
  border-left-color: var(--amber);
  transform: translateX(2px);
}
:deep(.job-card.project-card) { border-left-color: var(--cyan); }
:deep(.job-card.project-card:hover) { border-color: rgba(116,199,255,0.45); }

:deep(.job-card .card-head) {
  display: flex; align-items: baseline; justify-content: space-between; gap: 0.75rem;
  flex-wrap: wrap;
}
:deep(.job-card .card-title) { color: var(--amber); font-weight: 700; }
:deep(.job-card.project-card .card-title) { color: var(--cyan); }
:deep(.job-card .card-period) {
  color: var(--purple); font-size: 0.85em; white-space: nowrap;
  background: rgba(199,148,240,0.08); padding: 1px 8px; border-radius: 999px;
  border: 1px solid rgba(199,148,240,0.2);
}
:deep(.job-card .card-sub) {
  margin-top: 2px; display: flex; gap: 0.4rem; align-items: baseline; flex-wrap: wrap;
}
:deep(.job-card .card-dot) { color: var(--dim); }
:deep(.job-card .card-body) {
  margin-top: 0.5rem; display: flex; flex-direction: column; gap: 3px;
  padding-top: 0.5rem; border-top: 1px dashed var(--border);
}
:deep(.job-card .card-row) {
  display: flex; gap: 0.55rem; align-items: flex-start;
}
:deep(.job-card .card-bullet) { color: var(--dim); flex-shrink: 0; padding-top: 1px; }
:deep(.job-card .card-foot) {
  display: flex; gap: 0.6rem; align-items: center; justify-content: space-between;
  margin-top: 0.55rem; padding-top: 0.5rem; border-top: 1px dashed var(--border);
  flex-wrap: wrap;
}
:deep(.job-card .card-tags) { display: flex; flex-wrap: wrap; }

@media (max-width: 480px) {
  :deep(.job-card) { padding: 0.6rem 0.7rem 0.65rem; }
  :deep(.job-card .card-period) { font-size: 0.78em; }
}

:deep(.inline-cmd) {
  display: inline-block;
  background: transparent; border: 1px solid var(--border);
  color: var(--amber); padding: 0 7px; border-radius: 6px;
  font: inherit; cursor: pointer;
  transition: background .15s ease, transform .15s ease, color .15s ease, border-color .15s ease;
}
:deep(.inline-cmd:hover) {
  background: var(--tag-bg); transform: translateY(-1px);
  border-color: rgba(255,184,77,0.5); color: var(--amber);
}

/* Suggestion chips */
.tw-suggest {
  display: flex; gap: .35rem; flex-wrap: wrap;
  padding: .55rem clamp(.75rem, 2vw, 1.25rem);
  border-top: 1px solid var(--border);
  background: rgba(0,0,0,0.25);
  overflow-x: auto;
  scrollbar-width: none;
}
.tw-suggest::-webkit-scrollbar { display: none; }
.chip {
  font: inherit;
  font-size: .8rem;
  padding: .35rem .7rem;
  border-radius: 999px;
  background: var(--tag-bg);
  color: var(--tag-fg);
  border: 1px solid var(--border);
  white-space: nowrap;
  transition: transform .15s ease, background .15s ease, color .15s ease;
}
.chip:hover:not(:disabled) { background: var(--amber); color: #0a1a12; transform: translateY(-1px); }
.chip:disabled { opacity: .4; cursor: progress; }
.chip:active { transform: translateY(0); }

/* Mobile */
@media (max-width: 640px) {
  .terminal { height: 88dvh; border-radius: 10px; }
  .tw-title { font-size: .68rem; gap: .3rem; max-width: 55%; }
  .tw-body {
    padding: .7rem .65rem .85rem;
    font-size: .74rem;
    line-height: 1.5;
  }
  .tw-suggest { padding: .5rem .55rem; gap: .3rem; }
  .chip { font-size: .78rem; padding: .38rem .75rem; }
  :deep(.banner) { font-size: 6.5px; line-height: 1.1; text-shadow: 0 0 8px rgba(116,224,138,0.3); }
  :deep(.job-card) { padding: 0.55rem 0.65rem 0.6rem; }
  :deep(.job-card .card-period) { font-size: 0.75em; padding: 1px 6px; }
  :deep(.me-card) { padding: 0.6rem 0.7rem 0.65rem; }
  :deep(.me-body) { gap: 3px 0.7rem; }
  :deep(.tag) { font-size: .72em; padding: 0 6px; }
  :deep(.inline-cmd) { padding: 0 5px; }
}
@media (max-width: 420px) {
  .tw-body { font-size: .68rem; padding: .6rem .55rem .8rem; }
  :deep(.banner) { font-size: 5.4px; }
  :deep(.me-name) { font-size: 1em; }
  .chip { font-size: .74rem; padding: .35rem .65rem; }
}
@media (max-width: 360px) {
  :deep(.banner) { font-size: 4.8px; }
  .tw-body { font-size: .65rem; }
}

/* No-keyboard mode: chips are the only input, make them bigger / more obvious */
@media (pointer: coarse) {
  .tw-suggest {
    padding: 0.65rem 0.6rem calc(0.65rem + env(safe-area-inset-bottom, 0px));
    background: rgba(0,0,0,0.4);
  }
  .chip {
    font-size: .82rem;
    padding: .45rem .8rem;
    background: var(--tag-bg);
    border-color: rgba(255,255,255,0.12);
  }
  .chip:active { background: var(--amber); color: #0a1a12; }
  .touch-hint { opacity: 0.7; }
}
</style>
