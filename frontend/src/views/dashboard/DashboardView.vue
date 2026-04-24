<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import {
  ArrowRight,
  Calendar,
  Collection,
  EditPen,
  TrendCharts,
  UserFilled,
  Warning,
} from '@element-plus/icons-vue'
import VChart from '@/components/common/VChart.vue'
import {
  alerts,
  assignments,
  attendances,
  courses,
  currentTeacher,
  getCourseById,
  getStudentById,
  sessions,
  students,
} from '@/mock'

const router = useRouter()
const todayStr = '2026-04-21'

import { dashboardText as T } from './i18n'

const greeting = computed(() => {
  const h = new Date().getHours() || 10
  if (h < 11) return T.hi_morning
  if (h < 13) return T.hi_noon
  if (h < 18) return T.hi_afternoon
  return T.hi_evening
})

const stats = computed(() => {
  const openAssignments = assignments.filter((a) => a.status === 'open').length
  const totalStudents = students.length
  const unread = alerts.filter((a) => a.status === 'unread').length
  const totalCourses = courses.length

  const doneSessions = sessions.filter((s) => s.status === 'done').length
  const totalSessions = sessions.length

  const presentCount = attendances.filter((a) => a.status === 'present').length
  const attendanceRate = attendances.length
    ? ((presentCount / attendances.length) * 100).toFixed(1)
    : '0'

  return [
    { label: T.s1_label, value: totalCourses, suffix: T.s1_suffix, icon: Collection, color: '#4a7dff', bg: 'rgba(74,125,255,0.1)', sub: doneSessions + '/' + totalSessions + ' ' + T.s1_sub_a },
    { label: T.s2_label, value: totalStudents, suffix: T.s2_suffix, icon: UserFilled, color: '#00b4a6', bg: 'rgba(0,180,166,0.1)', sub: T.s2_sub },
    { label: T.s3_label, value: openAssignments, suffix: T.s3_suffix, icon: EditPen, color: '#f57c00', bg: 'rgba(245,124,0,0.12)', sub: T.s3_sub },
    { label: T.s4_label, value: unread, suffix: T.s4_suffix, icon: Warning, color: '#ef4444', bg: 'rgba(239,68,68,0.1)', sub: T.s4_sub_a + attendanceRate + T.s4_sub_b },
  ]
})

const todaySessions = computed(() =>
  sessions
    .filter((s) => s.date === todayStr || s.date > todayStr)
    .slice(0, 5)
    .map((s) => ({ ...s, course: getCourseById(s.course_id) })),
)

const recentAlerts = computed(() =>
  alerts
    .filter((a) => a.status !== 'resolved')
    .slice(0, 5)
    .map((a) => ({
      ...a,
      student: getStudentById(a.student_id),
      course: getCourseById(a.course_id),
    })),
)

const trendOption = computed(() => {
  const byDate = new Map<string, { present: number; total: number }>()
  sessions
    .filter((s) => s.status === 'done')
    .forEach((s) => {
      const list = attendances.filter((a) => a.session_id === s.id)
      const present = list.filter((a) => a.status === 'present').length
      const cur = byDate.get(s.date) || { present: 0, total: 0 }
      cur.present += present
      cur.total += list.length
      byDate.set(s.date, cur)
    })
  const entries = Array.from(byDate.entries()).sort().slice(-14)
  const dates = entries.map(([d]) => d.slice(5))
  const rates = entries.map(([, v]) => (v.total ? +((v.present / v.total) * 100).toFixed(1) : 0))

  return {
    tooltip: { trigger: 'axis' },
    grid: { left: 40, right: 20, top: 20, bottom: 28 },
    xAxis: { type: 'category', data: dates, axisLabel: { color: '#6b7280', fontSize: 11 } },
    yAxis: { type: 'value', min: 70, max: 100, axisLabel: { formatter: '{value}%', color: '#6b7280', fontSize: 11 }, splitLine: { lineStyle: { color: '#f1f5f9' } } },
    series: [{
      type: 'line', data: rates, smooth: true, symbol: 'circle', symbolSize: 7,
      lineStyle: { color: '#4a7dff', width: 3 },
      itemStyle: { color: '#4a7dff' },
      areaStyle: {
        color: { type: 'linear', x: 0, y: 0, x2: 0, y2: 1,
          colorStops: [{ offset: 0, color: 'rgba(74,125,255,0.35)' }, { offset: 1, color: 'rgba(74,125,255,0)' }] },
      },
    }],
  }
})

const assignmentOption = computed(() => ({
  tooltip: { trigger: 'axis', axisPointer: { type: 'shadow' } },
  legend: { right: 0, top: 0, textStyle: { fontSize: 12 } },
  grid: { left: 40, right: 20, top: 30, bottom: 40 },
  xAxis: {
    type: 'category',
    data: assignments.map((a) => a.title.length > 8 ? a.title.slice(0, 7) + '...' : a.title),
    axisLabel: { color: '#6b7280', fontSize: 11, interval: 0, rotate: -10 },
  },
  yAxis: { type: 'value', splitLine: { lineStyle: { color: '#f1f5f9' } } },
  series: [
    { name: T.legend_submitted, type: 'bar', stack: 'a', data: assignments.map((a) => a.submitted), itemStyle: { color: '#00b4a6', borderRadius: [4, 4, 0, 0] }, barWidth: 22 },
    { name: T.legend_unsubmitted, type: 'bar', stack: 'a', data: assignments.map((a) => a.total - a.submitted), itemStyle: { color: '#e5e7eb', borderRadius: [4, 4, 0, 0] }, barWidth: 22 },
  ],
}))

function alertLevelText(level: string) {
  return level === 'danger' ? T.lvl_danger : level === 'warning' ? T.lvl_warning : T.lvl_info
}
function alertLevelColor(level: string) {
  return level === 'danger' ? 'danger' : level === 'warning' ? 'warning' : 'info'
}

const sessionStatusMap = computed<Record<string, { text: string; type: string }>>(() => ({
  scheduled: { text: T.st_scheduled, type: 'primary' },
  done: { text: T.st_done, type: 'success' },
  canceled: { text: T.st_canceled, type: 'danger' },
  makeup: { text: T.st_makeup, type: 'warning' },
}))
</script>

<template>
  <div class="dashboard">
    <div class="welcome-card">
      <div class="welcome-left">
        <div class="welcome-greeting">{{ greeting }} {{ currentTeacher.name }}</div>
        <div class="welcome-date">{{ T.date_text }}</div>
        <div class="welcome-quick">
          <el-button type="primary" @click="router.push('/sessions')">
            <el-icon class="el-icon--left"><Calendar /></el-icon>{{ T.btn_today }}
          </el-button>
          <el-button @click="router.push('/alerts')">
            <el-icon class="el-icon--left"><Warning /></el-icon>{{ T.btn_alerts }} ({{ alerts.filter((a) => a.status === 'unread').length }})
          </el-button>
          <el-button @click="router.push('/analytics')">
            <el-icon class="el-icon--left"><TrendCharts /></el-icon>{{ T.btn_analytics }}
          </el-button>
        </div>
      </div>
      <div class="welcome-right">
        <div class="metric-hero-value">92.1<span>%</span></div>
        <div class="metric-hero-label">{{ T.hero_label }}</div>
        <div class="metric-hero-trend">{{ T.hero_trend }}</div>
      </div>
    </div>

    <el-row :gutter="16" class="stats-row">
      <el-col v-for="s in stats" :key="s.label" :xs="12" :sm="12" :md="6">
        <div class="stat-card">
          <div class="stat-icon" :style="{ background: s.bg, color: s.color }">
            <el-icon :size="22"><component :is="s.icon" /></el-icon>
          </div>
          <div class="stat-content">
            <div class="stat-value">{{ s.value }}<span class="stat-suffix">{{ s.suffix }}</span></div>
            <div class="stat-label">{{ s.label }}</div>
            <div class="stat-sub">{{ s.sub }}</div>
          </div>
        </div>
      </el-col>
    </el-row>

    <el-row :gutter="16" class="chart-row">
      <el-col :xs="24" :lg="14">
        <div class="panel">
          <div class="panel-header">
            <div class="panel-title"><span class="panel-dot" style="background:#4a7dff"></span>{{ T.card_trend }}</div>
            <el-link type="primary" underline="never" @click="router.push('/analytics')">
              {{ T.link_detail }} <el-icon><ArrowRight /></el-icon>
            </el-link>
          </div>
          <VChart :option="trendOption" height="280px" />
        </div>
      </el-col>
      <el-col :xs="24" :lg="10">
        <div class="panel">
          <div class="panel-header">
            <div class="panel-title"><span class="panel-dot" style="background:#00b4a6"></span>{{ T.card_assignment }}</div>
            <el-link type="primary" underline="never" @click="router.push('/assignments')">
              {{ T.link_detail }} <el-icon><ArrowRight /></el-icon>
            </el-link>
          </div>
          <VChart :option="assignmentOption" height="280px" />
        </div>
      </el-col>
    </el-row>

    <el-row :gutter="16" class="list-row">
      <el-col :xs="24" :lg="14">
        <div class="panel">
          <div class="panel-header">
            <div class="panel-title"><span class="panel-dot" style="background:#f57c00"></span>{{ T.card_sessions }}</div>
            <el-link type="primary" underline="never" @click="router.push('/sessions')">
              {{ T.link_all }} <el-icon><ArrowRight /></el-icon>
            </el-link>
          </div>
          <el-table :data="todaySessions" style="width:100%">
            <el-table-column :label="T.col_date" width="100">
              <template #default="{ row }">
                <div class="date-cell">
                  <div class="date-day">{{ row.date.slice(-2) }}</div>
                  <div class="date-month">{{ row.date.slice(5, 7) }}{{ T.col_month }}</div>
                </div>
              </template>
            </el-table-column>
            <el-table-column :label="T.col_course">
              <template #default="{ row }">
                <div class="course-cell">
                  <span class="course-dot" :style="{ background: row.course?.cover_color }"></span>
                  <span class="course-name">{{ row.course?.name }}</span>
                </div>
              </template>
            </el-table-column>
            <el-table-column :label="T.col_time" prop="time_slot" width="180" />
            <el-table-column :label="T.col_location" prop="location" width="130" />
            <el-table-column :label="T.col_topic">
              <template #default="{ row }"><span class="topic">{{ row.topic }}</span></template>
            </el-table-column>
            <el-table-column :label="T.col_status" width="90">
              <template #default="{ row }">
                <el-tag :type="(sessionStatusMap[row.status]?.type ?? 'info') as any" size="small" effect="light">
                  {{ sessionStatusMap[row.status]?.text || row.status }}
                </el-tag>
              </template>
            </el-table-column>
          </el-table>
        </div>
      </el-col>

      <el-col :xs="24" :lg="10">
        <div class="panel">
          <div class="panel-header">
            <div class="panel-title"><span class="panel-dot" style="background:#ef4444"></span>{{ T.card_alerts }}</div>
            <el-link type="primary" underline="never" @click="router.push('/alerts')">
              {{ T.link_alerts }} <el-icon><ArrowRight /></el-icon>
            </el-link>
          </div>
          <div class="alert-list">
            <div v-for="a in recentAlerts" :key="a.id" class="alert-item">
              <el-tag :type="alertLevelColor(a.level) as any" size="small" effect="dark" class="alert-tag">
                {{ alertLevelText(a.level) }}
              </el-tag>
              <div class="alert-body">
                <div class="alert-title">{{ a.title }}</div>
                <div class="alert-meta">
                  <span>{{ a.student?.name }}</span><el-divider direction="vertical" />
                  <span>{{ a.course?.name }}</span><el-divider direction="vertical" />
                  <span>{{ a.created_at.slice(5) }}</span>
                </div>
                <div class="alert-detail">{{ a.detail }}</div>
              </div>
            </div>
          </div>
        </div>
      </el-col>
    </el-row>
  </div>
</template>

<style scoped lang="scss">
.dashboard { display: flex; flex-direction: column; gap: 16px; }
.welcome-card {
  background: linear-gradient(135deg, #4a7dff 0%, #6a5cff 60%, #00b4a6 100%);
  border-radius: 12px; color: #fff; padding: 24px 28px;
  display: flex; justify-content: space-between; align-items: center;
  box-shadow: 0 8px 24px rgba(74,125,255,0.25);
  flex-wrap: wrap; gap: 16px;
}
.welcome-greeting { font-size: 22px; font-weight: 600; margin-bottom: 6px; }
.welcome-date { font-size: 13px; opacity: 0.85; margin-bottom: 14px; }
.welcome-quick { display: flex; gap: 10px; flex-wrap: wrap; }
.welcome-quick :deep(.el-button) {
  background: rgba(255,255,255,0.2); color: #fff;
  border: 1px solid rgba(255,255,255,0.3); backdrop-filter: blur(8px);
}
.welcome-quick :deep(.el-button):hover { background: rgba(255,255,255,0.3); }
.welcome-quick :deep(.el-button--primary) { background: #fff; color: #4a7dff; border: none; }
.welcome-quick :deep(.el-button--primary):hover { background: #f0f4ff; }
.welcome-right { text-align: right; padding-right: 10px; }
.metric-hero-value { font-size: 46px; font-weight: 700; line-height: 1; letter-spacing: -1px; }
.metric-hero-value span { font-size: 22px; margin-left: 2px; opacity: 0.9; }
.metric-hero-label { font-size: 13px; opacity: 0.9; margin-top: 6px; }
.metric-hero-trend { font-size: 12px; margin-top: 4px; color: #d4ffe8; }
.stats-row .el-col { margin-bottom: 12px; }
.stat-card {
  background: #fff; border-radius: 10px; padding: 18px 20px;
  display: flex; align-items: center; gap: 14px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.04); border: 1px solid #f1f5f9;
}
.stat-card:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,0,0,0.06); }
.stat-icon { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.stat-content { flex: 1; min-width: 0; }
.stat-value { font-size: 24px; font-weight: 700; color: #1f2937; line-height: 1; }
.stat-suffix { font-size: 13px; font-weight: 500; color: #9ca3af; margin-left: 2px; }
.stat-label { font-size: 13px; color: #6b7280; margin-top: 4px; }
.stat-sub { font-size: 11px; color: #9ca3af; margin-top: 4px; }
.chart-row :deep(.el-col), .list-row :deep(.el-col) { margin-bottom: 12px; }
.panel { background: #fff; border-radius: 10px; padding: 16px 20px; border: 1px solid #f1f5f9; box-shadow: 0 1px 3px rgba(0,0,0,0.03); height: 100%; }
.panel-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
.panel-title { font-size: 15px; font-weight: 600; color: #1f2937; display: flex; align-items: center; gap: 8px; }
.panel-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
.date-cell { text-align: center; line-height: 1.1; }
.date-day { font-size: 20px; font-weight: 700; color: #4a7dff; }
.date-month { font-size: 11px; color: #9ca3af; }
.course-cell { display: flex; align-items: center; gap: 8px; }
.course-dot { width: 8px; height: 18px; border-radius: 2px; }
.course-name { font-weight: 500; color: #1f2937; }
.topic { color: #6b7280; font-size: 13px; }
.alert-list { display: flex; flex-direction: column; gap: 10px; max-height: 360px; overflow-y: auto; padding-right: 4px; }
.alert-item { display: flex; gap: 10px; padding: 10px 12px; border-radius: 8px; background: #fafbfc; border: 1px solid #f1f5f9; }
.alert-item:hover { background: #f5f7fa; }
.alert-tag { flex-shrink: 0; align-self: flex-start; }
.alert-body { flex: 1; min-width: 0; }
.alert-title { font-size: 13.5px; font-weight: 500; color: #1f2937; }
.alert-meta { font-size: 11px; color: #9ca3af; margin-top: 2px; }
.alert-detail { font-size: 12.5px; color: #6b7280; margin-top: 4px; line-height: 1.5; }
</style>
