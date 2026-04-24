<script setup lang="ts">
import { computed, ref } from 'vue'
import { ElMessage } from 'element-plus'
import { Check, CircleClose, Warning } from '@element-plus/icons-vue'
import VChart from '@/components/common/VChart.vue'
import { alerts as mockAlerts, getCourseById, getStudentById } from '@/mock'
import type { MockAlert } from '@/mock'

const alerts = ref<MockAlert[]>(mockAlerts.map((a) => ({ ...a })))

const filterLevel = ref<string>('')
const filterStatus = ref<string>('')
const filterType = ref<string>('')
const keyword = ref('')
const drawer = ref(false)
const current = ref<MockAlert | null>(null)
const resolveNote = ref('')

const typeLabel: Record<string, string> = {
  absence: '出勤异常',
  grade_low: '成绩偏低',
  grade_decline: '成绩下滑',
  assignment_miss: '作业逾期',
}
const levelTag: Record<string, { text: string; type: string }> = {
  danger: { text: '高危', type: 'danger' },
  warning: { text: '警告', type: 'warning' },
  info: { text: '提示', type: 'info' },
}
const statusTag: Record<string, { text: string; type: string }> = {
  unread: { text: '未读', type: 'danger' },
  read: { text: '已读', type: 'warning' },
  resolved: { text: '已处置', type: 'success' },
}

const stats = computed(() => {
  const total = alerts.value.length
  const danger = alerts.value.filter((a) => a.level === 'danger').length
  const unread = alerts.value.filter((a) => a.status === 'unread').length
  const resolved = alerts.value.filter((a) => a.status === 'resolved').length
  return { total, danger, unread, resolved, rate: total ? ((resolved / total) * 100).toFixed(1) : '0' }
})

const filtered = computed(() => {
  return alerts.value
    .filter((a) => !filterLevel.value || a.level === filterLevel.value)
    .filter((a) => !filterStatus.value || a.status === filterStatus.value)
    .filter((a) => !filterType.value || a.type === filterType.value)
    .filter((a) => {
      if (!keyword.value) return true
      const stu = getStudentById(a.student_id)?.name || ''
      return a.title.includes(keyword.value) || stu.includes(keyword.value)
    })
    .map((a) => ({
      ...a,
      student: getStudentById(a.student_id),
      course: getCourseById(a.course_id),
    }))
})

const pieOption = computed(() => ({
  tooltip: { trigger: 'item' },
  legend: { bottom: 0, textStyle: { fontSize: 11 } },
  series: [
    {
      type: 'pie',
      radius: ['55%', '78%'],
      center: ['50%', '42%'],
      avoidLabelOverlap: true,
      itemStyle: { borderRadius: 6, borderColor: '#fff', borderWidth: 2 },
      label: { show: false },
      data: [
        { value: alerts.value.filter((a) => a.type === 'absence').length, name: '出勤异常', itemStyle: { color: '#ef4444' } },
        { value: alerts.value.filter((a) => a.type === 'grade_low').length, name: '成绩偏低', itemStyle: { color: '#f57c00' } },
        { value: alerts.value.filter((a) => a.type === 'grade_decline').length, name: '成绩下滑', itemStyle: { color: '#9c27b0' } },
        { value: alerts.value.filter((a) => a.type === 'assignment_miss').length, name: '作业逾期', itemStyle: { color: '#4a7dff' } },
      ],
    },
  ],
}))

const barOption = computed(() => {
  // 近 10 天预警数量
  const byDate = new Map<string, number>()
  alerts.value.forEach((a) => {
    const d = a.created_at.slice(5, 10)
    byDate.set(d, (byDate.get(d) || 0) + 1)
  })
  const entries = Array.from(byDate.entries()).sort()
  return {
    tooltip: { trigger: 'axis', axisPointer: { type: 'shadow' } },
    grid: { left: 32, right: 10, top: 20, bottom: 28 },
    xAxis: {
      type: 'category',
      data: entries.map(([d]) => d),
      axisLabel: { color: '#6b7280', fontSize: 11 },
    },
    yAxis: { type: 'value', splitLine: { lineStyle: { color: '#f1f5f9' } }, axisLabel: { fontSize: 11 } },
    series: [
      {
        type: 'bar',
        data: entries.map(([, v]) => v),
        barWidth: 18,
        itemStyle: {
          color: {
            type: 'linear',
            x: 0, y: 0, x2: 0, y2: 1,
            colorStops: [
              { offset: 0, color: '#ef4444' },
              { offset: 1, color: '#fca5a5' },
            ],
          },
          borderRadius: [4, 4, 0, 0],
        },
      },
    ],
  }
})

function openDetail(a: MockAlert) {
  current.value = a
  drawer.value = true
  resolveNote.value = ''
  // 打开即标记为已读
  if (a.status === 'unread') {
    const target = alerts.value.find((x) => x.id === a.id)
    if (target) target.status = 'read'
  }
}

function markAllRead() {
  alerts.value.forEach((a) => {
    if (a.status === 'unread') a.status = 'read'
  })
  ElMessage.success('已全部标记为已读')
}

function resolveAlert() {
  if (!current.value) return
  const target = alerts.value.find((x) => x.id === current.value!.id)
  if (target) target.status = 'resolved'
  ElMessage.success('预警已处置')
  drawer.value = false
}
</script>

<template>
  <div class="alert-center">
    <!-- 顶部统计 -->
    <el-row :gutter="12" class="stat-row">
      <el-col :xs="12" :md="6">
        <div class="stat-card stat-total">
          <div class="stat-label">累计预警</div>
          <div class="stat-value">{{ stats.total }}<span>条</span></div>
        </div>
      </el-col>
      <el-col :xs="12" :md="6">
        <div class="stat-card stat-danger">
          <div class="stat-label">高危预警</div>
          <div class="stat-value">{{ stats.danger }}<span>条</span></div>
        </div>
      </el-col>
      <el-col :xs="12" :md="6">
        <div class="stat-card stat-warning">
          <div class="stat-label">未读预警</div>
          <div class="stat-value">{{ stats.unread }}<span>条</span></div>
        </div>
      </el-col>
      <el-col :xs="12" :md="6">
        <div class="stat-card stat-success">
          <div class="stat-label">处置率</div>
          <div class="stat-value">{{ stats.rate }}<span>%</span></div>
        </div>
      </el-col>
    </el-row>

    <!-- 图表 -->
    <el-row :gutter="12" class="chart-row">
      <el-col :xs="24" :md="10">
        <div class="panel">
          <div class="panel-title">
            <span class="panel-dot" style="background: #ef4444"></span>
            预警类型占比
          </div>
          <VChart :option="pieOption" height="260px" />
        </div>
      </el-col>
      <el-col :xs="24" :md="14">
        <div class="panel">
          <div class="panel-title">
            <span class="panel-dot" style="background: #f57c00"></span>
            近 10 日预警数量
          </div>
          <VChart :option="barOption" height="260px" />
        </div>
      </el-col>
    </el-row>

    <!-- 列表 -->
    <div class="panel list-panel">
      <div class="panel-title-bar">
        <div class="panel-title">
          <span class="panel-dot" style="background: #4a7dff"></span>
          预警列表
        </div>
        <div class="filter-bar">
          <el-input v-model="keyword" placeholder="搜索学生或标题" clearable size="default" style="width: 200px" />
          <el-select v-model="filterType" placeholder="类型" clearable size="default" style="width: 130px">
            <el-option label="出勤异常" value="absence" />
            <el-option label="成绩偏低" value="grade_low" />
            <el-option label="成绩下滑" value="grade_decline" />
            <el-option label="作业逾期" value="assignment_miss" />
          </el-select>
          <el-select v-model="filterLevel" placeholder="等级" clearable size="default" style="width: 110px">
            <el-option label="高危" value="danger" />
            <el-option label="警告" value="warning" />
            <el-option label="提示" value="info" />
          </el-select>
          <el-select v-model="filterStatus" placeholder="状态" clearable size="default" style="width: 110px">
            <el-option label="未读" value="unread" />
            <el-option label="已读" value="read" />
            <el-option label="已处置" value="resolved" />
          </el-select>
          <el-button type="primary" plain @click="markAllRead">全部已读</el-button>
        </div>
      </div>

      <el-table :data="filtered" stripe size="default" @row-click="openDetail" row-key="id"
        :row-style="{ cursor: 'pointer' }">
        <el-table-column label="等级" width="90" align="center">
          <template #default="{ row }">
            <el-tag :type="levelTag[row.level].type as any" effect="dark" size="small">
              {{ levelTag[row.level].text }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="类型" width="110">
          <template #default="{ row }">
            <el-tag size="small" effect="plain">{{ typeLabel[row.type] }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="学生" width="140">
          <template #default="{ row }">
            <div class="stu-cell">
              <el-avatar :size="28" class="stu-avatar">{{ row.student?.name?.slice(0, 1) }}</el-avatar>
              <div>
                <div class="stu-name">{{ row.student?.name }}</div>
                <div class="stu-no">{{ row.student?.student_no }}</div>
              </div>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="课程" width="160">
          <template #default="{ row }">
            <span class="course-dot" :style="{ background: row.course?.cover_color }"></span>
            <span>{{ row.course?.name }}</span>
          </template>
        </el-table-column>
        <el-table-column label="预警内容" min-width="280">
          <template #default="{ row }">
            <div class="alert-title">{{ row.title }}</div>
            <div class="alert-detail">{{ row.detail }}</div>
          </template>
        </el-table-column>
        <el-table-column label="时间" width="140">
          <template #default="{ row }">{{ row.created_at }}</template>
        </el-table-column>
        <el-table-column label="状态" width="100" align="center">
          <template #default="{ row }">
            <el-tag :type="statusTag[row.status].type as any" size="small">
              {{ statusTag[row.status].text }}
            </el-tag>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <!-- 详情 Drawer -->
    <el-drawer v-model="drawer" title="预警详情" size="500px" :with-header="true">
      <template v-if="current">
        <div class="drawer-head">
          <el-tag :type="levelTag[current.level].type as any" effect="dark" size="large">
            {{ levelTag[current.level].text }}
          </el-tag>
          <span class="drawer-type">{{ typeLabel[current.type] }}</span>
          <span class="drawer-time">{{ current.created_at }}</span>
        </div>
        <h3 class="drawer-title">{{ current.title }}</h3>
        <el-descriptions :column="1" size="small" border class="drawer-desc">
          <el-descriptions-item label="涉及学生">
            {{ getStudentById(current.student_id)?.name }}
            （{{ getStudentById(current.student_id)?.student_no }}）
          </el-descriptions-item>
          <el-descriptions-item label="所在班级">
            {{ getStudentById(current.student_id)?.class_name }}
          </el-descriptions-item>
          <el-descriptions-item label="课程">
            {{ getCourseById(current.course_id)?.name }}
          </el-descriptions-item>
          <el-descriptions-item label="当前状态">
            <el-tag :type="statusTag[current.status].type as any" size="small">
              {{ statusTag[current.status].text }}
            </el-tag>
          </el-descriptions-item>
        </el-descriptions>

        <div class="drawer-section">
          <div class="drawer-section-title">预警详情</div>
          <div class="drawer-section-body">{{ current.detail }}</div>
        </div>

        <div class="drawer-section">
          <div class="drawer-section-title">AI 建议处置</div>
          <el-alert type="info" :closable="false" class="ai-tip">
            <template #title>
              <el-icon><Warning /></el-icon>
              建议：本周安排 15 分钟一对一沟通，了解缺勤原因；同时布置 1 份回顾性小测巩固知识点。
            </template>
          </el-alert>
        </div>

        <div class="drawer-section">
          <div class="drawer-section-title">处置记录</div>
          <el-input v-model="resolveNote" type="textarea" :rows="3" placeholder="记录您与学生沟通 / 调整的要点" />
        </div>

        <div class="drawer-actions">
          <el-button @click="drawer = false">取消</el-button>
          <el-button type="warning" plain>
            <el-icon><CircleClose /></el-icon>
            仅标记已读
          </el-button>
          <el-button type="primary" @click="resolveAlert">
            <el-icon><Check /></el-icon>
            确认处置
          </el-button>
        </div>
      </template>
    </el-drawer>
  </div>
</template>

<style scoped lang="scss">
.alert-center {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.stat-row .el-col {
  margin-bottom: 8px;
}

.stat-card {
  background: #fff;
  border-radius: 10px;
  padding: 16px 20px;
  border: 1px solid #f1f5f9;

  .stat-label {
    font-size: 12px;
    color: #6b7280;
    margin-bottom: 6px;
  }

  .stat-value {
    font-size: 26px;
    font-weight: 700;
    line-height: 1;

    span {
      font-size: 13px;
      font-weight: 400;
      color: #9ca3af;
      margin-left: 4px;
    }
  }
}

.stat-total .stat-value { color: #1f2937; }
.stat-danger { border-left: 3px solid #ef4444; .stat-value { color: #ef4444; } }
.stat-warning { border-left: 3px solid #f57c00; .stat-value { color: #f57c00; } }
.stat-success { border-left: 3px solid #10b981; .stat-value { color: #10b981; } }

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
  margin-bottom: 8px;
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
  margin-bottom: 12px;
  flex-wrap: wrap;
  gap: 12px;
}

.filter-bar {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.chart-row .el-col { margin-bottom: 8px; }

.stu-cell {
  display: flex;
  align-items: center;
  gap: 8px;

  .stu-avatar {
    background: linear-gradient(135deg, #4a7dff, #6a5cff);
    color: #fff;
    font-size: 12px;
  }

  .stu-name {
    font-size: 13px;
    font-weight: 500;
    line-height: 1.2;
  }

  .stu-no {
    font-size: 11px;
    color: #9ca3af;
    line-height: 1.2;
  }
}

.course-dot {
  display: inline-block;
  width: 6px;
  height: 14px;
  border-radius: 2px;
  vertical-align: middle;
  margin-right: 6px;
}

.alert-title {
  font-size: 13.5px;
  font-weight: 500;
  color: #1f2937;
}

.alert-detail {
  font-size: 12px;
  color: #6b7280;
  margin-top: 2px;
  max-width: 420px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.drawer-head {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 0 20px;
}

.drawer-type {
  font-size: 13px;
  color: #6b7280;
}

.drawer-time {
  margin-left: auto;
  font-size: 12px;
  color: #9ca3af;
}

.drawer-title {
  padding: 10px 20px 0;
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  color: #1f2937;
}

.drawer-desc {
  margin: 16px 20px;
}

.drawer-section {
  padding: 0 20px;
  margin-top: 16px;

  .drawer-section-title {
    font-size: 13px;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 8px;
  }

  .drawer-section-body {
    font-size: 13px;
    color: #4b5563;
    line-height: 1.7;
    padding: 10px 12px;
    background: #f8fafc;
    border-radius: 6px;
  }
}

.ai-tip {
  background: linear-gradient(90deg, #eff6ff, #eef2ff);
  border: 1px solid #dbeafe;
}

.drawer-actions {
  padding: 20px;
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  border-top: 1px solid #f1f5f9;
  margin-top: 20px;
}
</style>
