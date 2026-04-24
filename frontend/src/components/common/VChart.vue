<script setup lang="ts">
import * as echarts from 'echarts'
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'

const props = defineProps<{
  // 使用 any 以兼容 ECharts 严格泛型（字面量字符串会被窄化为 string）
  option: Record<string, any>
  height?: string
  theme?: 'light' | 'dark'
}>()

const el = ref<HTMLDivElement>()
let chart: echarts.ECharts | null = null

function resize() {
  chart?.resize()
}

onMounted(() => {
  if (!el.value) return
  chart = echarts.init(el.value, props.theme || 'light')
  chart.setOption(props.option)
  window.addEventListener('resize', resize)
})

watch(
  () => props.option,
  (val) => {
    chart?.setOption(val, true)
  },
  { deep: true },
)

onBeforeUnmount(() => {
  window.removeEventListener('resize', resize)
  chart?.dispose()
  chart = null
})
</script>

<template>
  <div ref="el" class="v-chart" :style="{ height: height || '300px' }"></div>
</template>

<style scoped>
.v-chart {
  width: 100%;
}
</style>
