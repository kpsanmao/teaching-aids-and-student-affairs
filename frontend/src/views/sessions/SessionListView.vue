<script setup lang="ts">
import { computed, ref } from 'vue'
import { ElMessage } from 'element-plus'
import { Check, Location, Refresh, Timer } from '@element-plus/icons-vue'
import VChart from '@/components/common/VChart.vue'
import {
  attendances as mockAttendances,
  courses,
  getCourseById,
  getCourseStudents,
  sessions,
} from '@/mock'
import type { AttendanceStatus, MockCourseSession } from '@/mock'

const selectedCourse = ref<number>(1)
const dialogVisible = ref(false)
const currentSession = ref<MockCourseSession | null>(null)
const tabView = ref<'list' | 'calendar'>('calendar')

interface RollRow { id: number; name: string; no: string; class_name: string; status: AttendanceStatus; note: string }
const roll = ref<RollRow[]>([])

const courseSessions = computed(() => sessions.filter((s) => s.course_id === selectedCourse.value))

const courseSummary = computed(() => {
  const cs = courseSessions.value
  const total = cs.length
  const done = cs.filter((s) => s.status === 'done').length
  const upcoming = cs.filter((s) => s.status === 'scheduled').length
  const canceled = cs.filter((s) => s.status === 'canceled').length

  // 出勤总数
  const ids = cs.map((s) => s.id)
  const records = mockAttendances.filter((a) => ids.includes(a.session_id))
  const present = records.filter((a) => a.status === 'present').length
  const rate = records.length ? ((present / records.length) * 100).toFixed(1) : '0'
  return { total, done, upcoming, canceled, rate }
})

// 按周分组（月历视图）
const weeks = computed(() => {
  const cs = [...courseSessions.value].sort((a, b) => a.date.localeCompare(b.date))
  if (!cs.length) return []
  const result: { week: number; sessions: MockCourseSession[] }[] = []
  let cur: { week: number; sessions: MockCourseSession[] } | null = null
  cs.forEach((s, i) => {
    const wk = Math.floor(i / 2) + 1 // 每 2 次算一周
    if (!cur || cur.week !== wk) {
      cur = { week: wk, sessions: [] }
      result.push(cur)
    }
    cur.sessions.push(s)
  })
  return result
})

// 近期 5 次课次考勤趋势
const trendOption = computed(() => {
  const cs = courseSessions.value.filter((s) => s.status === 'done').slice(-8)
  return {
    tooltip: { trigger: 'axis' },
    legend: { data: ['出勤', '迟到', '请假', '缺勤'], bottom: 0, textStyle: { fontSize: 11 } },
    grid: { left: 32, right: 10, top: 16, bottom: 40 },
    xAxis: { type: 'category', data: cs.map((s) => `第${s.session_index}次`), axisLabel: { fontSize: 11 } },
    yAxis: { type: 'value', splitLine: { lineStyle: { color: '#f1f5f9' } } },
    series: ['present', 'late', 'leave', 'absent'].map((st, i) => {
      const name = ['出勤', '迟到', '请假', '缺勤'][i]
      const color = ['#10b981', '#f59e0b', '#6b7280', '#ef4444'][i]
      const data = cs.map((s) => {
        return mockAttendances.filter((a) => a.session_id === s.id && a.status === st).length
      })
      return {
        name, type: 'bar', stack: 'total', data,
        itemStyle: { color, borderRadius: i === 0 ? [0, 0, 0, 0] : i === 3 ? [4, 4, 0, 0] : [0, 0, 0, 0] },
        barWidth: 20,
      }
    }),
  }
})

const statusMap: Record<string, { text: string; type: string; color: string }> = {
  scheduled: { text: '待上课', type: 'primary', color: '#4a7dff' },
  done: { text: '已结课', type: 'success', color: '#10b981' },
  canceled: { text: '已调停', type: 'danger', color: '#ef4444' },
  makeup: { text: '调课', type: 'warning', color: '#f59e0b' },
}

function sessionAttendanceLabel(s: MockCourseSession) {
  if (s.status !== 'done') return ''
  const rs = mockAttendances.filter((a) => a.session_id === s.id)
  const p = rs.filter((a) => a.status === 'present').length
  const total = rs.length
  if (!total) return ''
  return `${p}/${total}`
}

function openRoll(s: MockCourseSession) {
  currentSession.value = s
  const stus = getCourseStudents(s.course_id)
  const existing = new Map(mockAttendances.filter((a) => a.session_id === s.id).map((a) => [a.student_id, a]))
  roll.value = stus.map((st) => {
    const rec = existing.get(st.id)
    return {
      id: st.id,
      name: st.name,
      no: st.student_no,
      class_name: st.class_name,
      status: rec?.status || 'present',
      note: rec?.note || '',
    }
  })
  dialogVisible.value = true
}

function setAll(status: AttendanceStatus) {
  roll.value.forEach((r) => (r.status = status))
}

function saveRoll() {
  ElMessage.success(`第 ${currentSession.value?.session_index} 次课次点名已保存`)
  dialogVisible.value = false
}

const rollStats = computed(() => {
  const p = roll.value.filter((r) => r.status === 'present').length
  const l = roll.value.filter((r) => r.status === 'late').length
  const leave = roll.value.filter((r) => r.status === 'leave').length
  const ab = roll.value.filter((r) => r.status === 'absent').length
  return { present: p, late: l, leave, absent: ab, total: roll.value.length }
})

const statusOptions: { value: AttendanceStatus; label: string; color: string }[] = [
  { value: 'present', label: '出勤', color: '#10b981' },
  { value: 'late', label: '迟到', color: '#f59e0b' },
  { value: 'leave', label: '请假', color: '#6b7280' },
  { value: 'absent', label: '缺勤', color: '#ef4444' },
]
</script>

<template>
  <div class="sessions-page">
    <!-- 顶部：课程选择 + 概览 -->
    <div class="panel">
      <div class="top-bar">
        <div class="top-left">
          <span class="top-label">选择课程：</span>
          <el-radio-group v-model="selectedCourse">
            <el-radio-button v-for="c in courses" :key="c.id" :value="c.id">
              <span class="course-dot" :style="{ background: c.cover_color }"></span>
              {{ c.name }}
            </el-radio-button>
          </el-radio-group>
        </div>
        <el-radio-group v-model="tabView" size="default">
          <el-radio-button value="calendar">周历</el-radio-button>
          <el-radio-button value="list">列表</el-radio-button>
        </el-radio-group>
      </div>

      <el-row :gutter="12" class="stat-row">
        <el-col :xs="12" :sm="6"><div class="mini-stat"><div class="ms-label">课次总数</div><div class="ms-value">{{ courseSummary.total }}</div></div></el-col>
        <el-col :xs="12" :sm="6"><div class="mini-stat"><div class="ms-label">已完成</div><div class="ms-value" style="color:#10b981">{{ courseSummary.done }}</div></div></el-col>
        <el-col :xs="12" :sm="6"><div class="mini-stat"><div class="ms-label">待上课</div><div class="ms-value" style="color:#4a7dff">{{ courseSummary.upcoming }}</div></div></el-col>
        <el-col :xs="12" :sm="6"><div class="mini-stat"><div class="ms-label">出勤率</div><div class="ms-value" style="color:#f59e0b">{{ courseSummary.rate }}%</div></div></el-col>
      </el-row>
    </div>

    <!-- 出勤趋势 -->
    <div class="panel">
      <div class="panel-title">
        <span class="panel-dot" style="background:#00b4a6"></span>近 8 次课次考勤结构
      </div>
      <VChart :option="trendOption" height="220px" />
    </div>

    <!-- 日历/列表 -->
    <div class="panel">
      <div class="panel-title-bar">
        <div class="panel-title">
          <span class="panel-dot" style="background:#4a7dff"></span>
          {{ tabView === 'calendar' ? '课次周历' : '课次列表' }}
        </div>
      </div>

      <div v-if="tabView === 'calendar'" class="weeks-grid">
        <div v-for="w in weeks" :key="w.week" class="week-block">
          <div class="week-title">第 {{ w.week }} 周</div>
          <div class="week-sessions">
            <div v-for="s in w.sessions" :key="s.id" class="session-card" :class="s.status" @click="openRoll(s)">
              <div class="sc-idx">#{{ String(s.session_index).padStart(2, '0') }}</div>
              <div class="sc-date">{{ s.date.slice(5) }}</div>
              <div class="sc-topic">{{ s.topic }}</div>
              <div class="sc-meta">
                <span><el-icon><Timer /></el-icon>{{ s.time_slot.split('（')[0] }}</span>
                <span><el-icon><Location /></el-icon>{{ s.location }}</span>
              </div>
              <div class="sc-footer">
                <el-tag :type="statusMap[s.status].type as any" size="small">{{ statusMap[s.status].text }}</el-tag>
                <span v-if="sessionAttendanceLabel(s)" class="sc-rate">出勤 {{ sessionAttendanceLabel(s) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <el-table v-else :data="courseSessions" stripe style="width: 100%" @row-click="(row: any) => openRoll(row)"
        :row-style="{ cursor: 'pointer' }">
        <el-table-column label="课次" width="80">
          <template #default="{ row }">
            <span class="idx-cell">#{{ String(row.session_index).padStart(2, '0') }}</span>
          </template>
        </el-table-column>
        <el-table-column label="日期" prop="date" width="120" />
        <el-table-column label="时段" prop="time_slot" width="200" />
        <el-table-column label="地点" prop="location" width="140" />
        <el-table-column label="主题" prop="topic" />
        <el-table-column label="出勤" width="110">
          <template #default="{ row }">
            <span v-if="row.status === 'done'" class="attendance-cell">{{ sessionAttendanceLabel(row) }}</span>
            <span v-else class="attendance-empty">—</span>
          </template>
        </el-table-column>
        <el-table-column label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="statusMap[row.status].type as any" size="small">{{ statusMap[row.status].text }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="140">
          <template #default="{ row }">
            <el-button size="small" type="primary" plain @click.stop="openRoll(row)">
              {{ row.status === 'done' ? '查看/修改' : '课次点名' }}
            </el-button>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <!-- 点名 Dialog -->
    <el-dialog v-model="dialogVisible" title="课次点名" width="860px" destroy-on-close top="5vh">
      <template v-if="currentSession">
        <div class="roll-head">
          <div class="roll-info">
            <div class="roll-course">
              <span class="course-dot" :style="{ background: getCourseById(currentSession.course_id)?.cover_color }"></span>
              {{ getCourseById(currentSession.course_id)?.name }}
            </div>
            <div class="roll-sub">
              <el-tag size="small" type="info">第 {{ currentSession.session_index }} 次</el-tag>
              <span>{{ currentSession.date }}</span>
              <span>{{ currentSession.time_slot }}</span>
              <span>{{ currentSession.location }}</span>
            </div>
            <div class="roll-topic">今日主题：{{ currentSession.topic }}</div>
          </div>
          <div class="roll-stats">
            <div class="rs-item" style="color:#10b981"><div class="rs-v">{{ rollStats.present }}</div><div class="rs-l">出勤</div></div>
            <div class="rs-item" style="color:#f59e0b"><div class="rs-v">{{ rollStats.late }}</div><div class="rs-l">迟到</div></div>
            <div class="rs-item" style="color:#6b7280"><div class="rs-v">{{ rollStats.leave }}</div><div class="rs-l">请假</div></div>
            <div class="rs-item" style="color:#ef4444"><div class="rs-v">{{ rollStats.absent }}</div><div class="rs-l">缺勤</div></div>
          </div>
        </div>

        <div class="roll-toolbar">
          <span>批量操作：</span>
          <el-button-group>
            <el-button v-for="o in statusOptions" :key="o.value" size="small" @click="setAll(o.value)">
              全部{{ o.label }}
            </el-button>
          </el-button-group>
          <el-button size="small" @click="setAll('present')" type="primary" plain>
            <el-icon><Refresh /></el-icon>重置为全勤
          </el-button>
        </div>

        <div class="roll-grid">
          <div v-for="r in roll" :key="r.id" class="roll-item" :class="'status-' + r.status">
            <div class="ri-head">
              <el-avatar :size="32" class="ri-avatar">{{ r.name.slice(0, 1) }}</el-avatar>
              <div class="ri-meta">
                <div class="ri-name">{{ r.name }}</div>
                <div class="ri-no">{{ r.no }}</div>
              </div>
            </div>
            <el-radio-group v-model="r.status" size="small" class="ri-radio">
              <el-radio-button v-for="o in statusOptions" :key="o.value" :value="o.value">{{ o.label }}</el-radio-button>
            </el-radio-group>
          </div>
        </div>
      </template>

      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="saveRoll">
          <el-icon><Check /></el-icon>
          保存点名 ({{ rollStats.present + rollStats.late }}/{{ rollStats.total }} 到课)
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<style scoped lang="scss">
.sessions-page {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.panel {
  background: #fff;
  border-radius: 10px;
  padding: 14px 18px;
  border: 1px solid #f1f5f9;
}

.panel-title {
  font-size: 14px;
  font-weight: 600;
  color: #1f2937;
  display: flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 10px;
}
.panel-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
}
.panel-title-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 14px;
}

.top-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
  flex-wrap: wrap;
  gap: 12px;

  .top-label {
    font-size: 13px;
    color: #6b7280;
    margin-right: 6px;
  }
}

.course-dot {
  display: inline-block;
  width: 8px;
  height: 12px;
  border-radius: 2px;
  margin-right: 5px;
  vertical-align: middle;
}

.stat-row .el-col {
  margin-bottom: 8px;
}

.mini-stat {
  background: linear-gradient(135deg, #f8fafc, #fff);
  border: 1px solid #f1f5f9;
  border-radius: 8px;
  padding: 14px 16px;
  text-align: center;

  .ms-label { font-size: 12px; color: #6b7280; }
  .ms-value { font-size: 24px; font-weight: 700; color: #1f2937; margin-top: 4px; line-height: 1; }
}

.weeks-grid {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.week-block {
  .week-title {
    font-size: 13px;
    font-weight: 600;
    color: #6b7280;
    padding: 6px 10px;
    background: #f8fafc;
    border-left: 3px solid #4a7dff;
    border-radius: 4px;
    margin-bottom: 10px;
  }
  .week-sessions {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 10px;
  }
}

.session-card {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-left: 3px solid #4a7dff;
  border-radius: 8px;
  padding: 12px;
  cursor: pointer;
  transition: all 0.15s;

  &:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  }

  &.done {
    background: #f0fdf4;
    border-left-color: #10b981;
  }
  &.canceled {
    background: #fef2f2;
    border-left-color: #ef4444;
    opacity: 0.7;
  }

  .sc-idx {
    font-size: 11px;
    color: #9ca3af;
    font-weight: 600;
  }
  .sc-date {
    font-size: 16px;
    font-weight: 700;
    color: #1f2937;
    margin-top: 2px;
  }
  .sc-topic {
    font-size: 12.5px;
    color: #4b5563;
    margin-top: 6px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .sc-meta {
    display: flex;
    flex-direction: column;
    gap: 3px;
    font-size: 11px;
    color: #6b7280;
    margin-top: 8px;

    span { display: flex; align-items: center; gap: 3px; }
  }
  .sc-footer {
    margin-top: 10px;
    display: flex;
    justify-content: space-between;
    align-items: center;

    .sc-rate {
      font-size: 11px;
      color: #10b981;
      font-weight: 500;
    }
  }
}

.idx-cell {
  font-size: 12.5px;
  font-weight: 600;
  color: #4a7dff;
}
.attendance-cell {
  color: #10b981;
  font-weight: 500;
}
.attendance-empty {
  color: #d1d5db;
}

.roll-head {
  display: flex;
  justify-content: space-between;
  padding: 12px 16px;
  background: linear-gradient(90deg, #eff6ff, #fff);
  border-radius: 8px;
  border: 1px solid #dbeafe;

  .roll-info {
    flex: 1;
  }
  .roll-course {
    font-size: 17px;
    font-weight: 600;
    color: #1f2937;
  }
  .roll-sub {
    display: flex;
    gap: 10px;
    align-items: center;
    font-size: 12px;
    color: #6b7280;
    margin-top: 6px;
  }
  .roll-topic {
    font-size: 13px;
    color: #4b5563;
    margin-top: 6px;
  }
  .roll-stats {
    display: flex;
    gap: 14px;
  }
  .rs-item {
    text-align: center;
    .rs-v { font-size: 22px; font-weight: 700; line-height: 1; }
    .rs-l { font-size: 11px; color: #6b7280; margin-top: 2px; }
  }
}

.roll-toolbar {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 4px;
  font-size: 12px;
  color: #6b7280;
  flex-wrap: wrap;
}

.roll-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 10px;
  max-height: 56vh;
  overflow-y: auto;
  padding: 4px;
}

.roll-item {
  border: 1px solid #e5e7eb;
  border-left: 3px solid #10b981;
  border-radius: 8px;
  padding: 10px 12px;
  background: #fff;
  transition: background 0.15s;

  &.status-late { border-left-color: #f59e0b; background: #fffbeb; }
  &.status-leave { border-left-color: #6b7280; background: #f9fafb; }
  &.status-absent { border-left-color: #ef4444; background: #fef2f2; }

  .ri-head {
    display: flex;
    align-items: center;
    gap: 8px;

    .ri-avatar {
      background: linear-gradient(135deg, #4a7dff, #00b4a6);
      color: #fff;
      font-size: 13px;
    }
    .ri-name { font-size: 13.5px; font-weight: 500; color: #1f2937; line-height: 1.2; }
    .ri-no { font-size: 11px; color: #9ca3af; line-height: 1.2; }
  }
  .ri-radio {
    margin-top: 8px;
    width: 100%;

    :deep(.el-radio-button) {
      flex: 1;
    }
    :deep(.el-radio-button__inner) {
      width: 100%;
      padding: 5px 0;
      font-size: 12px;
    }
  }
}
</style>
