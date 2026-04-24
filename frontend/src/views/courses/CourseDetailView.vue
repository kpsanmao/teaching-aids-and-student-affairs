<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ArrowLeft, Calendar, DataLine, EditPen, Reading, UserFilled, Warning } from '@element-plus/icons-vue'
import VChart from '@/components/common/VChart.vue'
import {
  alerts,
  assignmentScores,
  assignments,
  attendances,
  getCourseById,
  getCourseStudents,
  getStudentById,
  lessonPlans,
  sessions,
} from '@/mock'

const route = useRoute()
const router = useRouter()
const courseId = computed(() => Number(route.params.id))
const course = computed(() => getCourseById(courseId.value))
const activeTab = ref('overview')

const courseSessions = computed(() => sessions.filter((s) => s.course_id === courseId.value))
const courseAssignments = computed(() => assignments.filter((a) => a.course_id === courseId.value))
const courseStudentsList = computed(() => getCourseStudents(courseId.value))
const coursePlans = computed(() => lessonPlans.filter((l) => l.course_id === courseId.value))
const courseAlerts = computed(() =>
  alerts
    .filter((a) => a.course_id === courseId.value)
    .map((a) => ({ ...a, student: getStudentById(a.student_id) })),
)

const stats = computed(() => {
  const done = courseSessions.value.filter((s) => s.status === 'done').length
  const att = attendances.filter((a) => courseSessions.value.some((s) => s.id === a.session_id))
  const present = att.filter((a) => a.status === 'present').length
  const rate = att.length ? +((present / att.length) * 100).toFixed(1) : 0
  const openAssn = courseAssignments.value.filter((a) => a.status === 'open').length

  const scs = assignmentScores
    .filter((s) => courseAssignments.value.some((a) => a.id === s.assignment_id) && s.score !== null)
    .map((s) => s.score as number)
  const avg = scs.length ? +(scs.reduce((a, b) => a + b, 0) / scs.length).toFixed(1) : 0

  return {
    progress: courseSessions.value.length ? Math.round((done / courseSessions.value.length) * 100) : 0,
    done,
    total: courseSessions.value.length,
    att_rate: rate,
    open_assn: openAssn,
    assn_total: courseAssignments.value.length,
    avg_score: avg,
    students: courseStudentsList.value.length,
    alerts: alerts.filter((a) => a.course_id === courseId.value && a.status !== 'resolved').length,
  }
})

const trendOption = computed(() => {
  const done = courseSessions.value.filter((s) => s.status === 'done').slice(-10)
  return {
    tooltip: { trigger: 'axis' },
    grid: { left: 40, right: 16, top: 20, bottom: 28 },
    xAxis: { type: 'category', data: done.map((s) => `第${s.session_index}次`), axisLabel: { fontSize: 11 } },
    yAxis: { type: 'value', min: 80, max: 100, axisLabel: { formatter: '{value}%' }, splitLine: { lineStyle: { color: '#f1f5f9' } } },
    series: [{
      type: 'line', smooth: true,
      data: done.map((s) => {
        const rs = attendances.filter((a) => a.session_id === s.id)
        const p = rs.filter((a) => a.status === 'present').length
        return rs.length ? +((p / rs.length) * 100).toFixed(1) : 0
      }),
      lineStyle: { color: course.value?.cover_color, width: 3 },
      itemStyle: { color: course.value?.cover_color },
      symbolSize: 8,
      areaStyle: {
        color: {
          type: 'linear', x: 0, y: 0, x2: 0, y2: 1,
          colorStops: [
            { offset: 0, color: course.value?.cover_color + '66' as any },
            { offset: 1, color: course.value?.cover_color + '00' as any },
          ],
        },
      },
    }],
  }
})

const assnOption = computed(() => ({
  tooltip: { trigger: 'axis', axisPointer: { type: 'shadow' } },
  legend: { bottom: 0, textStyle: { fontSize: 11 } },
  grid: { left: 30, right: 16, top: 20, bottom: 40 },
  xAxis: { type: 'category', data: courseAssignments.value.map((a) => a.title.slice(0, 8)), axisLabel: { fontSize: 11, rotate: -15 } },
  yAxis: { type: 'value', splitLine: { lineStyle: { color: '#f1f5f9' } } },
  series: [
    { name: '已提交', type: 'bar', stack: 'a', barWidth: 24, data: courseAssignments.value.map((a) => a.submitted), itemStyle: { color: '#00b4a6', borderRadius: [4, 4, 0, 0] } },
    { name: '未提交', type: 'bar', stack: 'a', barWidth: 24, data: courseAssignments.value.map((a) => a.total - a.submitted), itemStyle: { color: '#e5e7eb' } },
  ],
}))

function back() {
  router.push({ name: 'courses' })
}

const sessionStatusMap: Record<string, { text: string; type: string }> = {
  scheduled: { text: '待上课', type: 'primary' },
  done: { text: '已结课', type: 'success' },
  canceled: { text: '已调停', type: 'danger' },
  makeup: { text: '调课', type: 'warning' },
}
</script>

<template>
  <div v-if="course" class="course-detail">
    <!-- 头部 -->
    <div class="cd-hero" :style="{ background: `linear-gradient(135deg, ${course.cover_color}, ${course.cover_color}cc)` }">
      <el-button link class="back-btn" @click="back">
        <el-icon><ArrowLeft /></el-icon> 返回课程列表
      </el-button>
      <div class="hero-content">
        <div class="hero-meta">
          <el-tag effect="dark" class="meta-tag">{{ course.course_type }}</el-tag>
          <span>{{ course.credit }} 学分</span>
          <span>·</span>
          <span>{{ course.semester }}</span>
        </div>
        <h1 class="hero-title">{{ course.name }}</h1>
        <div class="hero-sub">{{ course.semester_start }} ~ {{ course.semester_end }} · 每周 {{ course.weekly_days.map((d) => '一二三四五六日'[d - 1]).join('、') }}</div>
      </div>
      <div class="hero-progress">
        <div class="hp-val">{{ stats.progress }}<span>%</span></div>
        <div class="hp-label">学期进度</div>
        <div class="hp-sub">{{ stats.done }} / {{ stats.total }} 课次完成</div>
      </div>
    </div>

    <!-- 统计条 -->
    <el-row :gutter="12" class="stat-row">
      <el-col :xs="12" :sm="8" :md="6" :lg="4"><div class="mini-stat"><el-icon color="#4a7dff"><UserFilled /></el-icon><div><div class="ms-v">{{ stats.students }}</div><div class="ms-l">选课学生</div></div></div></el-col>
      <el-col :xs="12" :sm="8" :md="6" :lg="4"><div class="mini-stat"><el-icon color="#10b981"><Calendar /></el-icon><div><div class="ms-v">{{ stats.att_rate }}%</div><div class="ms-l">平均出勤</div></div></div></el-col>
      <el-col :xs="12" :sm="8" :md="6" :lg="4"><div class="mini-stat"><el-icon color="#f59e0b"><EditPen /></el-icon><div><div class="ms-v">{{ stats.open_assn }}/{{ stats.assn_total }}</div><div class="ms-l">进行中/作业</div></div></div></el-col>
      <el-col :xs="12" :sm="8" :md="6" :lg="4"><div class="mini-stat"><el-icon color="#4a7dff"><DataLine /></el-icon><div><div class="ms-v">{{ stats.avg_score }}</div><div class="ms-l">作业均分</div></div></div></el-col>
      <el-col :xs="12" :sm="8" :md="6" :lg="4"><div class="mini-stat"><el-icon color="#9c27b0"><Reading /></el-icon><div><div class="ms-v">{{ coursePlans.length }}</div><div class="ms-l">教案章节</div></div></div></el-col>
      <el-col :xs="12" :sm="8" :md="6" :lg="4"><div class="mini-stat"><el-icon color="#ef4444"><Warning /></el-icon><div><div class="ms-v">{{ stats.alerts }}</div><div class="ms-l">待处理预警</div></div></div></el-col>
    </el-row>

    <!-- Tabs -->
    <div class="panel">
      <el-tabs v-model="activeTab" class="cd-tabs">
        <!-- 概览 -->
        <el-tab-pane label="课程概览" name="overview">
          <el-row :gutter="12">
            <el-col :xs="24" :md="14">
              <div class="sub-panel"><div class="panel-title"><span class="panel-dot" style="background:#4a7dff"></span>出勤率趋势</div>
                <VChart :option="trendOption" height="260px" />
              </div>
            </el-col>
            <el-col :xs="24" :md="10">
              <div class="sub-panel"><div class="panel-title"><span class="panel-dot" style="background:#00b4a6"></span>作业完成情况</div>
                <VChart :option="assnOption" height="260px" />
              </div>
            </el-col>
          </el-row>
        </el-tab-pane>

        <!-- 学生名单 -->
        <el-tab-pane :label="`学生名单 (${courseStudentsList.length})`" name="students">
          <el-table :data="courseStudentsList" stripe :max-height="500" size="small">
            <el-table-column label="#" type="index" width="60" align="center" />
            <el-table-column label="学号" prop="student_no" width="120" />
            <el-table-column label="姓名" width="120">
              <template #default="{ row }">
                <el-avatar :size="24" style="vertical-align:-7px;margin-right:6px;background:linear-gradient(135deg,#4a7dff,#00b4a6);color:#fff;font-size:11px">{{ row.name.slice(0, 1) }}</el-avatar>
                {{ row.name }}
              </template>
            </el-table-column>
            <el-table-column label="班级" prop="class_name" />
          </el-table>
        </el-tab-pane>

        <!-- 课次 -->
        <el-tab-pane :label="`课次 (${courseSessions.length})`" name="sessions">
          <el-table :data="courseSessions" stripe :max-height="500" size="small">
            <el-table-column label="次序" width="80" align="center">
              <template #default="{ row }">#{{ String(row.session_index).padStart(2, '0') }}</template>
            </el-table-column>
            <el-table-column label="日期" prop="date" width="120" />
            <el-table-column label="时段" prop="time_slot" width="200" />
            <el-table-column label="地点" prop="location" width="140" />
            <el-table-column label="主题" prop="topic" />
            <el-table-column label="状态" width="100">
              <template #default="{ row }">
                <el-tag :type="sessionStatusMap[row.status].type as any" size="small">
                  {{ sessionStatusMap[row.status].text }}
                </el-tag>
              </template>
            </el-table-column>
          </el-table>
        </el-tab-pane>

        <!-- 作业 -->
        <el-tab-pane :label="`作业 (${courseAssignments.length})`" name="assignments">
          <el-table :data="courseAssignments" stripe size="small">
            <el-table-column label="标题">
              <template #default="{ row }">
                <div style="font-weight:500">{{ row.title }}</div>
                <div style="font-size:12px;color:#9ca3af;margin-top:2px">{{ row.description.slice(0, 40) }}...</div>
              </template>
            </el-table-column>
            <el-table-column label="类型" width="90">
              <template #default="{ row }">
                <el-tag size="small">{{ row.type }}</el-tag>
              </template>
            </el-table-column>
            <el-table-column label="截止" prop="deadline" width="160" />
            <el-table-column label="提交进度" width="220">
              <template #default="{ row }">
                <el-progress :percentage="Math.round(row.submitted / row.total * 100)" :stroke-width="8" />
              </template>
            </el-table-column>
            <el-table-column label="状态" width="100">
              <template #default="{ row }">
                <el-tag :type="row.status === 'open' ? 'success' : 'info'" size="small">
                  {{ row.status === 'open' ? '进行中' : '已截止' }}
                </el-tag>
              </template>
            </el-table-column>
          </el-table>
        </el-tab-pane>

        <!-- 教案 -->
        <el-tab-pane :label="`教案 (${coursePlans.length})`" name="plans">
          <div v-if="!coursePlans.length" class="empty-tip">
            <el-empty description="暂未上传教案" />
          </div>
          <div v-else class="plan-list">
            <div v-for="p in coursePlans" :key="p.id" class="plan-card">
              <div class="plan-head">
                <el-icon :size="28" color="#4a7dff"><Reading /></el-icon>
                <div style="flex:1">
                  <div class="plan-title">{{ p.title }}</div>
                  <div class="plan-meta">{{ p.chapter_no }} · {{ p.uploaded_at }} · {{ p.file_size_kb }} KB · 共 {{ p.sections.length }} 节</div>
                </div>
                <el-tag v-if="p.status === 'ready'" type="success" size="small">已解析</el-tag>
                <el-tag v-else type="warning" size="small">解析中</el-tag>
              </div>
              <div v-if="p.sections.length" class="plan-sections">
                <div v-for="s in p.sections" :key="s.id" :class="['sec-item', 'level-' + s.level]">
                  <span class="sec-title">{{ s.title }}</span>
                  <span class="sec-time">⏱ {{ s.estimate_minutes }}分钟</span>
                </div>
              </div>
            </div>
          </div>
        </el-tab-pane>

        <!-- 预警 -->
        <el-tab-pane :label="`预警 (${courseAlerts.length})`" name="alerts">
          <el-table :data="courseAlerts" size="small" :max-height="500">
            <el-table-column label="等级" width="90">
              <template #default="{ row }">
                <el-tag :type="row.level === 'danger' ? 'danger' : row.level === 'warning' ? 'warning' : 'info'" effect="dark" size="small">
                  {{ row.level === 'danger' ? '高危' : row.level === 'warning' ? '警告' : '提示' }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column label="学生" width="140">
              <template #default="{ row }">
                <span style="font-weight:500">{{ row.student?.name }}</span>
                <span style="color:#9ca3af;font-size:11px"> ({{ row.student?.student_no }})</span>
              </template>
            </el-table-column>
            <el-table-column label="预警内容">
              <template #default="{ row }">
                <div style="font-weight:500">{{ row.title }}</div>
                <div style="font-size:12px;color:#6b7280;margin-top:2px">{{ row.detail }}</div>
              </template>
            </el-table-column>
            <el-table-column label="时间" prop="created_at" width="150" />
          </el-table>
        </el-tab-pane>
      </el-tabs>
    </div>
  </div>
</template>

<style scoped lang="scss">
.course-detail { display: flex; flex-direction: column; gap: 12px; }

.cd-hero {
  border-radius: 12px;
  padding: 20px 26px;
  color: #fff;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: space-between;
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);

  .back-btn {
    position: absolute;
    top: 14px;
    left: 14px;
    color: rgba(255, 255, 255, 0.9) !important;

    &:hover { color: #fff !important; }
  }

  .hero-content { margin-top: 12px; }
  .hero-meta {
    display: flex; align-items: center; gap: 8px;
    font-size: 12px;
    opacity: 0.9;

    .meta-tag {
      border: 1px solid rgba(255, 255, 255, 0.3) !important;
      background: rgba(255, 255, 255, 0.15) !important;
      color: #fff !important;
    }
  }
  .hero-title {
    font-size: 26px;
    margin: 10px 0 6px;
    font-weight: 700;
  }
  .hero-sub {
    font-size: 13px;
    opacity: 0.9;
  }
}

.hero-progress {
  text-align: right;
  .hp-val {
    font-size: 44px;
    font-weight: 700;
    line-height: 1;
    span { font-size: 20px; margin-left: 2px; }
  }
  .hp-label { font-size: 13px; opacity: 0.9; margin-top: 4px; }
  .hp-sub { font-size: 11px; opacity: 0.8; margin-top: 2px; }
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
  display: flex; align-items: center; gap: 6px;
  margin-bottom: 8px;
}
.panel-dot { width: 8px; height: 8px; border-radius: 50%; }

.stat-row .el-col { margin-bottom: 8px; }

.mini-stat {
  background: #fff;
  border: 1px solid #f1f5f9;
  border-radius: 8px;
  padding: 12px 14px;
  display: flex;
  align-items: center;
  gap: 10px;

  .ms-v { font-size: 18px; font-weight: 700; color: #1f2937; line-height: 1; }
  .ms-l { font-size: 11px; color: #6b7280; margin-top: 3px; }
}

.sub-panel {
  background: #fafbfc;
  border: 1px solid #f1f5f9;
  border-radius: 8px;
  padding: 14px;
  height: 100%;
}

.el-row .el-col { margin-bottom: 8px; }

.empty-tip {
  padding: 30px 0;
}

.plan-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.plan-card {
  background: #fff;
  border: 1px solid #f1f5f9;
  border-radius: 8px;
  padding: 14px;
}

.plan-head {
  display: flex;
  align-items: center;
  gap: 12px;
  padding-bottom: 10px;
  border-bottom: 1px dashed #f1f5f9;

  .plan-title { font-size: 15px; font-weight: 600; color: #1f2937; }
  .plan-meta { font-size: 11.5px; color: #9ca3af; margin-top: 2px; }
}

.plan-sections {
  margin-top: 10px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.sec-item {
  display: flex;
  justify-content: space-between;
  padding: 6px 10px;
  border-radius: 4px;
  font-size: 13px;

  &.level-1 {
    background: #f0f7ff;
    color: #1f2937;
    font-weight: 500;
  }
  &.level-2 {
    margin-left: 20px;
    background: #fafbfc;
    color: #4b5563;
  }

  .sec-time { font-size: 11px; color: #9ca3af; }
}
</style>
