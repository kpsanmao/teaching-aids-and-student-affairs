<script setup lang="ts">
import { computed, ref } from 'vue'
import { MagicStick, TrendCharts } from '@element-plus/icons-vue'
import VChart from '@/components/common/VChart.vue'
import {
  alerts,
  assignmentScores,
  assignments,
  attendances,
  courses,
  getCourseStudents,
  sessions,
  students,
} from '@/mock'

const selectedCourse = ref<number | 'all'>('all')

const cs = computed(() => {
  if (selectedCourse.value === 'all') return sessions
  return sessions.filter((s) => s.course_id === selectedCourse.value)
})

// ---- 概览 ----
const overview = computed(() => {
  const done = cs.value.filter((s) => s.status === 'done')
  const ids = done.map((s) => s.id)
  const records = attendances.filter((a) => ids.includes(a.session_id))
  const present = records.filter((a) => a.status === 'present').length
  const absent = records.filter((a) => a.status === 'absent').length
  const attRate = records.length ? +((present / records.length) * 100).toFixed(1) : 0
  const absentRate = records.length ? +((absent / records.length) * 100).toFixed(1) : 0

  const assn = selectedCourse.value === 'all' ? assignments : assignments.filter((a) => a.course_id === selectedCourse.value)
  const scored = assignmentScores
    .filter((s) => assn.some((a) => a.id === s.assignment_id) && s.score !== null)
    .map((s) => s.score as number)
  const avgScore = scored.length ? +(scored.reduce((a, b) => a + b, 0) / scored.length).toFixed(1) : 0

  return { attRate, absentRate, avgScore, studentsCount: selectedCourse.value === 'all' ? students.length : getCourseStudents(selectedCourse.value).length, alertCount: alerts.filter((a) => selectedCourse.value === 'all' || a.course_id === selectedCourse.value).length }
})

// ---- 出勤热力图（周几 × 节次） ----
const heatmapOption = computed(() => {
  const xData = ['第一节', '第二节', '第三节', '第四节', '第五节', '第六节', '第七节', '第八节']
  const yData = ['周一', '周二', '周三', '周四', '周五']
  const data: [number, number, number][] = []
  for (let y = 0; y < yData.length; y++) {
    for (let x = 0; x < xData.length; x++) {
      // 随机但稳定的出勤率（88~99）
      const seed = (y * 17 + x * 31 + 11) % 11
      const val = 88 + seed
      data.push([x, y, val])
    }
  }
  return {
    tooltip: { position: 'top', formatter: (p: any) => `${yData[p.data[1]]} ${xData[p.data[0]]}: ${p.data[2]}%` },
    grid: { left: 60, right: 10, top: 20, bottom: 30 },
    xAxis: { type: 'category', data: xData, splitArea: { show: true }, axisLabel: { fontSize: 11 } },
    yAxis: { type: 'category', data: yData, splitArea: { show: true }, axisLabel: { fontSize: 11 } },
    visualMap: {
      min: 85, max: 100, calculable: true, orient: 'horizontal', left: 'center', bottom: 0,
      inRange: { color: ['#fef2f2', '#fee2e2', '#fef3c7', '#d1fae5', '#10b981'] },
      textStyle: { fontSize: 11 },
    },
    series: [{
      name: '出勤率', type: 'heatmap', data,
      label: { show: true, formatter: '{@[2]}', fontSize: 10 },
      emphasis: { itemStyle: { shadowBlur: 10, shadowColor: 'rgba(0,0,0,0.4)' } },
    }],
  }
})

// ---- 成绩分布 ----
const scoreDistOption = computed(() => {
  const assn = selectedCourse.value === 'all' ? assignments : assignments.filter((a) => a.course_id === selectedCourse.value)
  const scored = assignmentScores.filter((s) => assn.some((a) => a.id === s.assignment_id) && s.score !== null).map((s) => s.score as number)
  const bins = [0, 0, 0, 0, 0]
  scored.forEach((s) => {
    if (s < 60) bins[0]++
    else if (s < 70) bins[1]++
    else if (s < 80) bins[2]++
    else if (s < 90) bins[3]++
    else bins[4]++
  })
  return {
    tooltip: { trigger: 'axis' },
    grid: { left: 32, right: 10, top: 20, bottom: 26 },
    xAxis: { type: 'category', data: ['<60', '60-70', '70-80', '80-90', '90-100'] },
    yAxis: { type: 'value', splitLine: { lineStyle: { color: '#f1f5f9' } } },
    series: [{
      type: 'bar', data: bins, barWidth: 30,
      label: { show: true, position: 'top', fontSize: 11 },
      itemStyle: {
        color: (p: any) => ['#ef4444', '#f59e0b', '#fbbf24', '#4a7dff', '#10b981'][p.dataIndex],
        borderRadius: [6, 6, 0, 0],
      },
    }],
  }
})

// ---- 作业趋势 ----
const trendOption = computed(() => {
  const assn = (selectedCourse.value === 'all' ? assignments : assignments.filter((a) => a.course_id === selectedCourse.value))
    .slice()
    .sort((a, b) => a.published_at.localeCompare(b.published_at))
  return {
    tooltip: { trigger: 'axis' },
    legend: { data: ['平均分', '提交率'], bottom: 0, textStyle: { fontSize: 11 } },
    grid: { left: 32, right: 36, top: 20, bottom: 40 },
    xAxis: { type: 'category', data: assn.map((a) => a.title.slice(0, 8)), axisLabel: { rotate: -10, fontSize: 11 } },
    yAxis: [
      { type: 'value', name: '分数', min: 60, max: 100, splitLine: { lineStyle: { color: '#f1f5f9' } } },
      { type: 'value', name: '提交率', min: 0, max: 100, axisLabel: { formatter: '{value}%' } },
    ],
    series: [
      {
        name: '平均分', type: 'line', smooth: true, data: assn.map((a) => {
          const scs = assignmentScores.filter((s) => s.assignment_id === a.id && s.score !== null).map((s) => s.score as number)
          return scs.length ? +(scs.reduce((x, y) => x + y, 0) / scs.length).toFixed(1) : 0
        }),
        itemStyle: { color: '#4a7dff' }, lineStyle: { color: '#4a7dff', width: 3 }, symbolSize: 8,
        areaStyle: {
          color: { type: 'linear', x: 0, y: 0, x2: 0, y2: 1, colorStops: [{ offset: 0, color: 'rgba(74,125,255,0.3)' }, { offset: 1, color: 'rgba(74,125,255,0)' }] },
        },
      },
      {
        name: '提交率', type: 'bar', yAxisIndex: 1, barWidth: 22,
        data: assn.map((a) => +((a.submitted / a.total) * 100).toFixed(0)),
        itemStyle: { color: '#00b4a6', borderRadius: [4, 4, 0, 0] },
      },
    ],
  }
})

// ---- 学生能力雷达 ----
const radarOption = computed(() => {
  const stus = (selectedCourse.value === 'all' ? students : getCourseStudents(selectedCourse.value)).slice(0, 4)
  return {
    tooltip: {},
    legend: { bottom: 0, textStyle: { fontSize: 11 } },
    radar: {
      indicator: [
        { name: '出勤率', max: 100 },
        { name: '作业完成', max: 100 },
        { name: '课堂表现', max: 100 },
        { name: '实验能力', max: 100 },
        { name: '知识掌握', max: 100 },
      ],
      radius: '62%',
      axisName: { color: '#4b5563', fontSize: 11 },
      splitArea: { areaStyle: { color: ['#f8fafc', '#f1f5f9'] } },
      splitLine: { lineStyle: { color: '#e5e7eb' } },
    },
    series: [{
      type: 'radar', data: stus.map((s, i) => {
        const seed = (s.id * 13) % 30
        return {
          name: s.name,
          value: [90 - seed, 85 + (seed % 10), 80 + (seed % 20), 82 + (seed % 15), 84 + (seed % 15)],
          itemStyle: { color: ['#4a7dff', '#00b4a6', '#f59e0b', '#9c27b0'][i] },
          areaStyle: { opacity: 0.2 },
        }
      }),
    }],
  }
})

// ---- AI 解读 ----
const aiStreaming = ref(false)
const aiText = ref('')

const aiTemplate = `整体学情解读（AI 分析结果）：

【学情亮点】
• 本周整体出勤率 92.1%，较上一周提升 2.3%，说明考勤管理制度发挥了积极作用；
• 作业均分 84.6 分，位于优良区间，且分布集中于 80-90 分段，表明知识点整体掌握良好；
• 学生能力雷达显示"实验能力"维度全班得分偏高，实践教学成效显著。

【重点关注】
• 连续 3 次作业《线性表 → 二叉树 → 表达式求值》有 5 名学生成绩下滑 > 10 分，建议一对一沟通；
• 周二下午的第 7-8 节课出勤波动较大（平均 88%），与其他时段相差 4-7 个百分点，建议调整教学节奏；
• 高危预警 3 条均与出勤相关，建议启用自动提醒并通知辅导员。

【教学建议】
1. 针对成绩下滑学生，布置一份针对性巩固作业（难度可设为"基础"与"进阶"组合）；
2. 在第 10 周期中复习课上增加"二叉树遍历 + 哈夫曼编码"的综合练习；
3. 对作业提交率 <60% 的两项作业启用逾期自动预警，并给未提交学生推送消息。`

async function runAiAnalyze() {
  aiStreaming.value = true
  aiText.value = ''
  for (let i = 0; i < aiTemplate.length; i += 2) {
    aiText.value = aiTemplate.slice(0, i + 2)
    await new Promise((r) => setTimeout(r, 12))
  }
  aiStreaming.value = false
}
</script>

<template>
  <div class="analytics-page">
    <div class="panel">
      <div class="top-bar">
        <span class="top-label">分析范围：</span>
        <el-radio-group v-model="selectedCourse">
          <el-radio-button value="all">全部课程</el-radio-button>
          <el-radio-button v-for="c in courses" :key="c.id" :value="c.id">{{ c.name }}</el-radio-button>
        </el-radio-group>
      </div>
    </div>

    <el-row :gutter="12" class="stat-row">
      <el-col :xs="12" :sm="8" :md="4"><div class="mini-stat stat-blue"><div class="ms-label">出勤率</div><div class="ms-value">{{ overview.attRate }}<span>%</span></div></div></el-col>
      <el-col :xs="12" :sm="8" :md="4"><div class="mini-stat stat-red"><div class="ms-label">缺勤率</div><div class="ms-value">{{ overview.absentRate }}<span>%</span></div></div></el-col>
      <el-col :xs="12" :sm="8" :md="4"><div class="mini-stat stat-teal"><div class="ms-label">作业均分</div><div class="ms-value">{{ overview.avgScore }}</div></div></el-col>
      <el-col :xs="12" :sm="8" :md="4"><div class="mini-stat"><div class="ms-label">学生总数</div><div class="ms-value">{{ overview.studentsCount }}</div></div></el-col>
      <el-col :xs="12" :sm="8" :md="4"><div class="mini-stat stat-orange"><div class="ms-label">预警条数</div><div class="ms-value">{{ overview.alertCount }}</div></div></el-col>
      <el-col :xs="12" :sm="8" :md="4"><div class="mini-stat stat-purple"><div class="ms-label">教学周</div><div class="ms-value">第 9<span>周</span></div></div></el-col>
    </el-row>

    <el-row :gutter="12">
      <el-col :xs="24" :md="12">
        <div class="panel">
          <div class="panel-title"><span class="panel-dot" style="background:#4a7dff"></span>出勤热力图（周几 × 节次）</div>
          <VChart :option="heatmapOption" height="280px" />
        </div>
      </el-col>
      <el-col :xs="24" :md="12">
        <div class="panel">
          <div class="panel-title"><span class="panel-dot" style="background:#00b4a6"></span>作业成绩分布</div>
          <VChart :option="scoreDistOption" height="280px" />
        </div>
      </el-col>
    </el-row>

    <el-row :gutter="12">
      <el-col :xs="24" :md="14">
        <div class="panel">
          <div class="panel-title"><span class="panel-dot" style="background:#f59e0b"></span>作业完成趋势 · 平均分 vs 提交率</div>
          <VChart :option="trendOption" height="300px" />
        </div>
      </el-col>
      <el-col :xs="24" :md="10">
        <div class="panel">
          <div class="panel-title"><span class="panel-dot" style="background:#9c27b0"></span>学生能力雷达（样例 4 名）</div>
          <VChart :option="radarOption" height="300px" />
        </div>
      </el-col>
    </el-row>

    <div class="panel ai-panel">
      <div class="panel-title-bar">
        <div class="panel-title">
          <span class="panel-dot" style="background:linear-gradient(135deg,#6a5cff,#4a7dff)"></span>AI 学情解读
        </div>
        <el-button type="primary" :loading="aiStreaming" @click="runAiAnalyze" class="ai-btn">
          <el-icon class="el-icon--left"><MagicStick /></el-icon>
          {{ aiText ? '重新解读' : '生成 AI 解读' }}
        </el-button>
      </div>
      <div v-if="!aiText && !aiStreaming" class="ai-empty">
        <el-icon :size="40" color="#d1d5db"><TrendCharts /></el-icon>
        <div class="ai-empty-text">点击"生成 AI 解读"让模型根据上方图表数据给出教学建议</div>
      </div>
      <div v-else class="ai-text">
        {{ aiText }}<span v-if="aiStreaming" class="cursor-blink">▊</span>
      </div>
    </div>
  </div>
</template>

<style scoped lang="scss">
.analytics-page { display: flex; flex-direction: column; gap: 12px; }

.panel {
  background: #fff;
  border-radius: 10px;
  padding: 14px 18px;
  border: 1px solid #f1f5f9;
  height: 100%;
}

.panel-title {
  font-size: 14px;
  font-weight: 600;
  color: #1f2937;
  display: flex; align-items: center; gap: 6px;
  margin-bottom: 8px;
}
.panel-dot { width: 8px; height: 8px; border-radius: 50%; display:inline-block; }
.panel-title-bar {
  display: flex; justify-content: space-between; align-items: center;
  margin-bottom: 12px;
}

.top-bar { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.top-label { font-size: 13px; color: #6b7280; }

.stat-row .el-col { margin-bottom: 8px; }
.mini-stat {
  background: #fff;
  border: 1px solid #f1f5f9;
  border-radius: 8px;
  padding: 14px 10px;
  text-align: center;

  .ms-label { font-size: 12px; color: #6b7280; }
  .ms-value {
    font-size: 22px;
    font-weight: 700;
    color: #1f2937;
    margin-top: 4px;
    line-height: 1;
    span { font-size: 12px; color: #9ca3af; font-weight: 400; margin-left: 2px; }
  }

  &.stat-blue { border-top: 3px solid #4a7dff; .ms-value { color: #4a7dff; } }
  &.stat-red { border-top: 3px solid #ef4444; .ms-value { color: #ef4444; } }
  &.stat-teal { border-top: 3px solid #00b4a6; .ms-value { color: #00b4a6; } }
  &.stat-orange { border-top: 3px solid #f59e0b; .ms-value { color: #f59e0b; } }
  &.stat-purple { border-top: 3px solid #9c27b0; .ms-value { color: #9c27b0; } }
}

.ai-panel .ai-btn {
  background: linear-gradient(135deg, #6a5cff, #4a7dff);
  border: none;
}

.ai-empty {
  text-align: center;
  padding: 40px 0;

  .ai-empty-text {
    font-size: 13px;
    color: #9ca3af;
    margin-top: 8px;
  }
}

.ai-text {
  white-space: pre-wrap;
  font-size: 13.5px;
  line-height: 1.8;
  color: #374151;
  padding: 16px 20px;
  background: linear-gradient(135deg, #f8fafc, #fff);
  border-radius: 8px;
  border: 1px dashed #dbeafe;
  max-height: 420px;
  overflow-y: auto;
}

.cursor-blink {
  color: #6a5cff;
  animation: blink 1s infinite;
}
@keyframes blink { 50% { opacity: 0; } }

.el-row + .el-row { margin-top: 12px; }
.el-row .el-col { margin-bottom: 8px; }
</style>
