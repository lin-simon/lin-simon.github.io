<template>
  <div
    ref="containerRef"
    class="ft-root"
    :class="[className, isDisabled ? 'ft-disabled' : '']"
    :style="style"
    v-bind="$attrs"
  >
    <!-- Fallback when disabled -->
    <div v-if="isDisabled" class="ft-fallback" :class="fallbackClass"></div>
  </div>
</template>

<script setup lang="ts">
import { Renderer, Program, Mesh, Triangle } from 'ogl'
import { ref, onMounted, onBeforeUnmount, watch, computed } from 'vue'

type Vec2 = [number, number]

interface FaultyTerminalProps {
  enabled?: boolean
  autoDisable?: boolean
  disableIfSoftware?: boolean
  minFps?: number
  fpsSampleMs?: number
  fallbackClass?: string

  scale?: number
  gridMul?: Vec2
  digitSize?: number
  timeScale?: number
  pause?: boolean
  scanlineIntensity?: number
  glitchAmount?: number
  flickerAmount?: number
  noiseAmp?: number
  chromaticAberration?: number
  dither?: number | boolean
  curvature?: number
  tint?: string
  mouseReact?: boolean
  mouseStrength?: number
  dpr?: number
  pageLoadAnimation?: boolean
  brightness?: number
  className?: string
  style?: Record<string, string | number>
}

/* ==================== Shaders (unchanged effect) ==================== */

const vertexShader = `
attribute vec2 position;
attribute vec2 uv;
varying vec2 vUv;
void main() {
  vUv = uv;
  gl_Position = vec4(position, 0.0, 1.0);
}
`

const fragmentShader = `
precision mediump float;

varying vec2 vUv;

uniform float iTime;
uniform vec3  iResolution;   // (width, height, aspect)
uniform float uScale;

uniform vec2  uGridMul;
uniform float uDigitSize;
uniform float uScanlineIntensity;
uniform float uGlitchAmount;
uniform float uFlickerAmount;
uniform float uNoiseAmp;
uniform float uChromaticAberration;
uniform float uDither;
uniform float uCurvature;
uniform vec3  uTint;
uniform vec2  uMouse;
uniform float uMouseStrength;
uniform float uUseMouse;
uniform float uPageLoadProgress;
uniform float uUsePageLoadAnimation;
uniform float uBrightness;

float time;

float hash21(vec2 p){
  p = fract(p * 234.56);
  p += dot(p, p + 34.56);
  return fract(p.x * p.y);
}

float noise(vec2 p){
  return sin(p.x * 10.0) * sin(p.y * (3.0 + sin(time * 0.090909))) + 0.2;
}

mat2 rotate(float a){
  float c = cos(a), s = sin(a);
  return mat2(c, -s, s, c);
}

float fbm(vec2 p){
  p *= 1.1;
  float f = 0.0;
  float amp = 0.5 * uNoiseAmp;

  mat2 r0 = rotate(time * 0.02);
  f += amp * noise(p);
  p = r0 * p * 2.0;
  amp *= 0.454545;

  mat2 r1 = rotate(time * 0.02);
  f += amp * noise(p);
  p = r1 * p * 2.0;
  amp *= 0.454545;

  mat2 r2 = rotate(time * 0.08);
  f += amp * noise(p);

  return f;
}

float pattern(vec2 p, out vec2 q, out vec2 r) {
  vec2 offset1 = vec2(1.0);
  vec2 offset0 = vec2(0.0);
  mat2 rot01 = rotate(0.1 * time);
  mat2 rot1 = rotate(0.1);

  q = vec2(fbm(p + offset1), fbm(rot01 * p + offset1));
  r = vec2(fbm(rot1 * q + offset0), fbm(q + offset0));
  return fbm(p + r);
}

float digit(vec2 p){
  vec2 grid = uGridMul * 15.0;
  vec2 s = floor(p * grid) / grid;
  p = p * grid;

  vec2 q, r;
  float intensity = pattern(s * 0.1, q, r) * 1.3 - 0.03;

  if(uUseMouse > 0.5){
    vec2 mouseWorld = uMouse * uScale;
    float distToMouse = distance(s, mouseWorld);
    float mouseInfluence = exp(-distToMouse * 8.0) * uMouseStrength * 10.0;
    intensity += mouseInfluence;
    float ripple = sin(distToMouse * 20.0 - iTime * 5.0) * 0.1 * mouseInfluence;
    intensity += ripple;
  }

  if(uUsePageLoadAnimation > 0.5){
    float cellRandom = fract(sin(dot(s, vec2(12.9898, 78.233))) * 43758.5453);
    float cellDelay = cellRandom * 0.8;
    float cellProgress = clamp((uPageLoadProgress - cellDelay) / 0.2, 0.0, 1.0);
    float fadeAlpha = smoothstep(0.0, 1.0, cellProgress);
    intensity *= fadeAlpha;
  }

  p = fract(p);
  p *= uDigitSize;

  float px5 = p.x * 5.0;
  float py5 = (1.0 - p.y) * 5.0;
  float x = fract(px5);
  float y = fract(py5);

  float i = floor(py5) - 2.0;
  float j = floor(px5) - 2.0;
  float n = i * i + j * j;
  float f = n * 0.0625;

  float isOn = step(0.1, intensity - f);
  float brightness = isOn * (0.2 + y * 0.8) * (0.75 + x * 0.25);

  return step(0.0, p.x) * step(p.x, 1.0) * step(0.0, p.y) * step(p.y, 1.0) * brightness;
}

float onOff(float a, float b, float c){
  return step(c, sin(iTime + a * cos(iTime * b))) * uFlickerAmount;
}

float displace(vec2 look){
  float y = look.y - mod(iTime * 0.25, 1.0);
  float wnd = 1.0 / (1.0 + 50.0 * y * y);
  return sin(look.y * 20.0 + iTime) * 0.0125 * onOff(4.0, 2.0, 0.8) * (1.0 + cos(iTime * 60.0)) * wnd;
}

vec3 getColor(vec2 p){
  float bar = step(mod(p.y + time * 20.0, 1.0), 0.2) * 0.4 + 1.0;
  bar *= uScanlineIntensity;

  float displacement = displace(p);
  p.x += displacement;

  if (uGlitchAmount != 1.0) {
    float extra = displacement * (uGlitchAmount - 1.0);
    p.x += extra;
  }

  float middle = digit(p);

  const float off = 0.002;
  float sum = digit(p + vec2(-off, -off)) + digit(p + vec2(0.0, -off)) + digit(p + vec2(off, -off)) +
              digit(p + vec2(-off, 0.0))  + digit(p + vec2(0.0, 0.0))  + digit(p + vec2(off, 0.0))  +
              digit(p + vec2(-off, off))  + digit(p + vec2(0.0, off))  + digit(p + vec2(off, off));

  vec3 baseColor = vec3(0.9) * middle + sum * 0.1 * vec3(1.0) * bar;
  return baseColor;
}

vec2 barrel(vec2 uv){
  vec2 c = uv * 2.0 - 1.0;
  float r2 = dot(c, c);
  c *= 1.0 + uCurvature * r2;
  return c * 0.5 + 0.5;
}

void main() {
  time = iTime * 0.333333;
  vec2 uv = vUv;

  if(uCurvature != 0.0){
    uv = barrel(uv);
  }

  vec2 p = uv * uScale;
  vec3 col = getColor(p);

  if(uChromaticAberration != 0.0){
    vec2 ca = vec2(uChromaticAberration) / iResolution.xy;
    col.r = getColor(p + ca).r;
    col.b = getColor(p - ca).b;
  }

  col *= uTint;
  col *= uBrightness;

  if(uDither > 0.0){
    float rnd = hash21(gl_FragCoord.xy);
    col += (rnd - 0.5) * (uDither * 0.003922);
  }

  gl_FragColor = vec4(col, 1.0);
}
`

/* ==================== Utils & Props ==================== */

function hexToRgb(hex: string): [number, number, number] {
  let h = hex.replace('#', '').trim()
  if (h.length === 3) h = h.split('').map(c => c + c).join('')
  const num = parseInt(h, 16)
  return [((num >> 16) & 255) / 255, ((num >> 8) & 255) / 255, (num & 255) / 255]
}

const props = withDefaults(defineProps<FaultyTerminalProps>(), {
  enabled: true,
  autoDisable: true,
  disableIfSoftware: true,
  minFps: 24,
  fpsSampleMs: 900,
  fallbackClass: '',

  scale: 1,
  gridMul: () => [2, 1],
  digitSize: 1.5,
  timeScale: 0.3,
  pause: false,
  scanlineIntensity: 0.3,
  glitchAmount: 1,
  flickerAmount: 1,
  noiseAmp: 1,
  chromaticAberration: 0,
  dither: 0,
  curvature: 0.2,
  tint: '#ffffff',
  mouseReact: true,
  mouseStrength: 0.2,
  dpr: Math.min(window.devicePixelRatio || 1, 2),
  pageLoadAnimation: true,
  brightness: 1,
  className: '',
  style: () => ({})
})

const containerRef = ref<HTMLDivElement | null>(null)
const isDisabled = ref(false)

const mouse = ref({ x: 0.5, y: 0.5 })          // viewport-normalized
const smoothMouse = ref({ x: 0.5, y: 0.5 })
const frozenTime = ref(0)
const rafId = ref(0)
const loadStart = ref(0)
const timeSeed = ref(Math.random() * 100)

const ditherValue = computed(() =>
  typeof props.dither === 'boolean' ? (props.dither ? 1 : 0) : props.dither
)

// Stable uniforms
const U = {
  iTime: { value: 0 },
  iResolution: { value: new Float32Array([1, 1, 1]) },
  uScale: { value: props.scale },
  uGridMul: { value: new Float32Array([props.gridMul[0], props.gridMul[1]]) },
  uDigitSize: { value: props.digitSize },
  uScanlineIntensity: { value: props.scanlineIntensity },
  uGlitchAmount: { value: props.glitchAmount },
  uFlickerAmount: { value: props.flickerAmount },
  uNoiseAmp: { value: props.noiseAmp },
  uChromaticAberration: { value: props.chromaticAberration },
  uDither: { value: ditherValue.value },
  uCurvature: { value: props.curvature },
  uTint: { value: new Float32Array(hexToRgb(props.tint)) },
  uMouse: { value: new Float32Array([0.5, 0.5]) },
  uMouseStrength: { value: props.mouseStrength },
  uUseMouse: { value: props.mouseReact ? 1 : 0 },
  uPageLoadProgress: { value: props.pageLoadAnimation ? 0 : 1 },
  uUsePageLoadAnimation: { value: props.pageLoadAnimation ? 1 : 0 },
  uBrightness: { value: props.brightness }
}

/* ==================== OGL Setup ==================== */

let renderer: Renderer | null = null
let mesh: Mesh | null = null
let program: Program | null = null
let resizeObs: ResizeObserver | null = null
let fpsFrames = 0
let fpsStart = 0
let fpsChecked = false

function shouldDisableForRenderer(gl: WebGLRenderingContext | WebGL2RenderingContext): boolean {
  try {
    const ext = gl.getExtension('WEBGL_debug_renderer_info') as any
    if (!ext) return false
    const rendererStr = gl.getParameter(ext.UNMASKED_RENDERER_WEBGL) as string
    // Common software renderers
    if (/swiftshader|software|llvmpipe|softpipe|mesa|basic render/i.test(rendererStr)) return true
  } catch {}
  return false
}

function fit() {
  const ctn = containerRef.value
  if (!renderer || !ctn) return
  const rect = ctn.getBoundingClientRect()
  const w = Math.max(1, Math.floor(rect.width))
  const h = Math.max(1, Math.floor(rect.height))
  renderer.setSize(w, h)
  const res = U.iResolution.value
  res[0] = renderer.gl.canvas.width
  res[1] = renderer.gl.canvas.height
  res[2] = res[0] / Math.max(1.0, res[1])
}

function onPointerMove(e: MouseEvent | PointerEvent | TouchEvent) {
  let clientX: number, clientY: number
  if ('touches' in e && e.touches.length) {
    clientX = e.touches[0].clientX
    clientY = e.touches[0].clientY
  } else {
    const me = e as MouseEvent
    clientX = me.clientX
    clientY = me.clientY
  }
  const w = Math.max(1, window.innerWidth)
  const h = Math.max(1, window.innerHeight)
  mouse.value.x = clientX / w
  mouse.value.y = 1 - (clientY / h)
}

function disableEffect(reason = '') {
  teardown()
  isDisabled.value = true
  // (optional) console.warn('FaultyTerminal disabled:', reason)
}

function setup() {
  if (!props.enabled) { isDisabled.value = true; return }
  const ctn = containerRef.value
  if (!ctn) return

  // clear any previous canvases (HMR / remount safety)
  ctn.querySelectorAll('canvas').forEach(el => el.remove())

  // Try WebGL via OGL
  try {
    renderer = new Renderer({ dpr: props.dpr })
  } catch {
    // No WebGL at all
    disableEffect('no-webgl')
    return
  }

  const gl = renderer!.gl

  // Context lost handling -> disable
  const onLost = (e: Event) => { e.preventDefault?.(); disableEffect('context-lost') }
  gl.canvas.addEventListener('webglcontextlost', onLost, { passive: false })

  // Software renderer check
  if (props.autoDisable && props.disableIfSoftware && shouldDisableForRenderer(gl)) {
    disableEffect('software-renderer')
    gl.canvas.removeEventListener('webglcontextlost', onLost as any)
    return
  }

  gl.clearColor(0, 0, 0, 1)

  const geom = new Triangle(gl)
  program = new Program(gl, {
    vertex: vertexShader,
    fragment: fragmentShader,
    uniforms: U as unknown as Record<string, unknown>
  })
  mesh = new Mesh(gl, { geometry: geom, program })

  ctn.appendChild(gl.canvas)
  fit()

  if (!resizeObs) {
    resizeObs = new ResizeObserver(() => fit())
    resizeObs.observe(ctn)
  }

  // Global input (works behind overlays)
  if (props.mouseReact) {
    window.addEventListener('pointermove', onPointerMove, { passive: true })
    window.addEventListener('mousemove', onPointerMove as any, { passive: true })
    window.addEventListener('touchmove', onPointerMove as any, { passive: true })
  }

  // Initial FPS sampling window
  fpsFrames = 0
  fpsStart = performance.now()
  fpsChecked = false

  const loop = (t: number) => {
    rafId.value = requestAnimationFrame(loop)

    if (props.pageLoadAnimation && loadStart.value === 0) loadStart.value = t

    if (!props.pause) {
      const elapsed = (t * 0.001 + timeSeed.value) * (props.timeScale ?? 1)
      U.iTime.value = elapsed
      frozenTime.value = elapsed
    } else {
      U.iTime.value = frozenTime.value
    }

    // FPS sampling
    if (props.autoDisable && !fpsChecked) {
      fpsFrames++
      const elapsedMs = performance.now() - fpsStart
      if (elapsedMs >= props.fpsSampleMs) {
        const fps = (fpsFrames / elapsedMs) * 1000
        fpsChecked = true
        if (fps < props.minFps) {
          disableEffect(`low-fps:${fps.toFixed(1)}`)
          return
        }
      }
    }

    if (props.pageLoadAnimation && loadStart.value > 0) {
      const dur = 2000
      const prog = Math.min((t - loadStart.value) / dur, 1)
      U.uPageLoadProgress.value = prog
    }

    if (props.mouseReact) {
      const d = 0.08
      smoothMouse.value.x += (mouse.value.x - smoothMouse.value.x) * d
      smoothMouse.value.y += (mouse.value.y - smoothMouse.value.y) * d
      const mu = U.uMouse.value
      mu[0] = smoothMouse.value.x
      mu[1] = smoothMouse.value.y
    }

    renderer!.render({ scene: mesh! })
  }
  rafId.value = requestAnimationFrame(loop)
}

function teardown() {
  cancelAnimationFrame(rafId.value)

  const ctn = containerRef.value

  if (props.mouseReact) {
    window.removeEventListener('pointermove', onPointerMove as any)
    window.removeEventListener('mousemove', onPointerMove as any)
    window.removeEventListener('touchmove', onPointerMove as any)
  }

  if (renderer) {
    try {
      const gl = renderer.gl
      gl.canvas.removeEventListener('webglcontextlost', () => {})
      if (gl?.canvas?.parentElement === ctn) ctn?.removeChild(gl.canvas)
      gl.getExtension('WEBGL_lose_context')?.loseContext()
    } catch {}
  }

  if (resizeObs && ctn) {
    try { resizeObs.unobserve(ctn) } catch {}
  }
  resizeObs = null

  mesh = null
  program = null
  renderer = null

  loadStart.value = 0
  timeSeed.value = Math.random() * 100
}

/* ==================== Lifecycle ==================== */

onMounted(() => {
  isDisabled.value = !props.enabled
  if (!isDisabled.value) setup()
})

onBeforeUnmount(teardown)
if (import.meta.hot) import.meta.hot.dispose(() => teardown())

/* ==================== Prop → Uniform sync ==================== */

watch(() => props.enabled, v => {
  if (v && isDisabled.value) { isDisabled.value = false; setup() }
  else if (!v && !isDisabled.value) { disableEffect('props-disabled') }
})

watch(() => props.scale, v => { U.uScale.value = v })
watch(() => props.gridMul, v => { const g = U.uGridMul.value; g[0] = v[0]; g[1] = v[1] }, { deep: true })
watch(() => props.digitSize, v => { U.uDigitSize.value = v })
watch(() => props.scanlineIntensity, v => { U.uScanlineIntensity.value = v })
watch(() => props.glitchAmount, v => { U.uGlitchAmount.value = v })
watch(() => props.flickerAmount, v => { U.uFlickerAmount.value = v })
watch(() => props.noiseAmp, v => { U.uNoiseAmp.value = v })
watch(() => props.chromaticAberration, v => { U.uChromaticAberration.value = v })
watch(ditherValue, v => { U.uDither.value = v })
watch(() => props.curvature, v => { U.uCurvature.value = v })
watch(() => props.tint, v => { const c = hexToRgb(v); const t = U.uTint.value; t[0]=c[0]; t[1]=c[1]; t[2]=c[2] })
watch(() => props.mouseReact, v => { U.uUseMouse.value = v ? 1 : 0 })
watch(() => props.mouseStrength, v => { U.uMouseStrength.value = v })
watch(() => props.brightness, v => { U.uBrightness.value = v })
watch(() => props.pageLoadAnimation, v => { U.uUsePageLoadAnimation.value = v ? 1 : 0; U.uPageLoadProgress.value = v ? 0 : 1 })
</script>

<style scoped>
.ft-root {
  position: relative;
  width: 100%;
  height: 100%;
  overflow: hidden;
}

/* absolutely position canvas so it never affects layout */
.ft-root canvas {
  position: absolute;
  inset: 0;
  display: block;
  width: 100%;
  height: 100%;
}
.ft-fallback {
  position: absolute;
  inset: 0;
  pointer-events: none;
  background:
    radial-gradient(circle at center,
      rgba(40,40,40,0.9) 0%,
      rgba(0,0,0,0.99) 50%
    ),
    linear-gradient(180deg,
      rgba(0,0,0,0.7) 10%,
      rgba(0,0,0,1) 100%
    );

}

.ft-disabled { }
</style>
