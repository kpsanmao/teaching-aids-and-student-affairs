<script setup lang="ts">
import { computed, ref } from 'vue'
import { Download, Edit, Upload } from '@element-plus/icons-vue'
import VChart from '@/components/common/VChart.vue'
import {
  assignments,
  assignmentScores,
  courses,
  getCourseStudents,
} from '@/mock'

const selectedCourse = ref<number>(1)
const keyword = ref('')

// 成绩公式：平时 30% + 作业 30% + 实验 20% + 期末 20%
const weights = ref({ daily: 30, assignment: 30, lab: 20, final: 20 })

const courseAssignments = computed(() =>
  assignments.filter((a) => a.course_id === selectedCourse.value),
)

const students = computed(() => getCourseStudents(selectedCourse.value))

const scoreTable = computed(() => {
  const r = 0.42
  return students.value
    .filter((s) => !keyword.value || s.name.includes(keyword.value) || s.student_no.includes(keyword.value))
    .map((s, idx) => {
      const scores = courseAssignments.value.map((a) => {
        const rec = assignmentScores.find((x) => x.assignment_id === a.id && x.student_id === s.id)
        return rec?.score ?? null
      })
      // 伪造平时/期中/期末
      const seed = (s.id * 13 + idx * 7) % 100 / 100
      const daily = Math.round(80 + seed * 18)
      const lab = Math.round(70 + ((s.id * 31) % 30))
      const final_ = Math.round(70 + seed * 28)
      const assignAvg = scores.filter((x) => x !== null).length
        ? scores.filter((x) => x !== null).reduce((sum, x) => sum + (x as number), 0) / scores.filter((x) => x !== null).length
        : 0

      const total = +(
        daily * weights.value.daily / 100 +
        assignAvg * weights.value.assignment / 100 +
        lab * weights.value.lab / 100 +
        final_ * weights.value.final / 100
      ).toFixed(1)

      // 预测：期末按当前能力估计
      const predict = +(total * (1 - r) + (final_ + 2) * r).toFixed(1)

      return {
        id: s.id,
        name: s.name,
        no: s.student_no,
        class_name: s.class_name,
        scores,
        daily,
        lab,
        final: final_,
        assignAvg: +assignAvg.toFixed(1),
        total,
        predict,
      }
    })
    .sort((a, b) => b.total - a.total)
})

const courseSummary = computed(() => {
  const rows = scoreTable.value
  if (!rows.length) return { avg: 0, max: 0, min: 0, pass: 0, excellent: 0 }
  const totals = rows.map((r) => r.total)
  const avg = +(totals.reduce((s, v) => s + v, 0) / totals.length).toFixed(1)
  const max = Math.max(...totals)
  const min = Math.min(...totals)
  const pass = rows.filter((r) => r.total >= 60).length
  const excellent = rows.filter((r) => r.total >= 85).length
  return { avg, max, min, pass, excellent, passRate: +((pass / rows.length) * 100).toFixed(1), excellentRate: +((excellent / rows.length) * 100).toFixed(1) }
})

const distOption = computed(() => {
  const totals = scoreTable.value.map((r) => r.total)
  const bins = [0, 0, 0, 0, 0]
  totals.forEach((t) => {
    if (t < 60) bins[0]++
    else if (t < 70) bins[1]++
    else if (t < 80) bins[2]++
    else if (t < 90) bins[3]++
    else bins[4]++
  })
  return {
    tooltip: { trigger: 'axis' },
    grid: { left: 32, right: 16, top: 16, bottom: 28 },
    xAxis: { type: 'category', data: ['<60', '60-70', '70-80', '80-90', '90-100'] },
    yAxis: { type: 'value', splitLine: { lineStyle: { color: '#f1f5f9' } } },
    series: [
      {
        type: 'bar', data: bins, barWidth: 34,
        label: { show: true, position: 'top', color: '#4b5563', fontSize: 12 },
        itemStyle: {
          color: (p: any) => {
            const colors = ['#ef4444', '#f59e0b', '#fbbf24', '#4a7dff', '#10b981']
            return colors[p.dataIndex]
          },
          borderRadius: [6, 6, 0, 0],
        },
      },
    ],
  }
})

const weightOption = computed(() => ({
  tooltip: { trigger: 'item', formatter: '{b}: {c}%' },
  series: [
    {
      type: 'pie', radius: ['50%', '75%'], center: ['50%', '50%'],
      itemStyle: { borderRadius: 6, borderColor: '#fff', borderWidth: 2 },
      label: {
        formatter: '{b|{b}}\n{c|{c}%}',
        rich: {
          b: { fontSize: 12, color: '#6b7280', lineHeight: 18 },
          c: { fontSize: 14, fontWeight: 700, color: '#1f2937' },
        },
      },
      data: [
        { value: weights.value.daily, name: '平时表现', itemStyle: { color: '#4a7dff' } },
        { value: weights.value.assignment, name: '作业均分', itemStyle: { color: '#00b4a6' } },
        { value: weights.value.lab, name: '实验成绩', itemStyle: { color: '#f59e0b' } },
        { value: weights.value.final, name: '期末考试', itemStyle: { color: '#9c27b0' } },
      ],
    },
  ],
}))

const predictOption = computed(() => {
  const top = scoreTable.value.slice(0, 10)
  return {
    tooltip: { trigger: 'axis', axisPointer: { type: 'shadow' } },
    legend: { bottom: 0, textStyle: { fontSize: 11 } },
    grid: { left: 32, right: 16, top: 20, bottom: 40 },
    xAxis: { type: 'category', data: top.map((r) => r.name), axisLabel: { rotate: -15, fontSize: 11 } },
    yAxis: { type: 'value', min: 50, max: 100, splitLine: { lineStyle: { color: '#f1f5f9' } } },
    series: [
      { name: '当前总评', type: 'bar', data: top.map((r) => r.total), barWidth: 14, itemStyle: { color: '#4a7dff', borderRadius: [4, 4, 0, 0] } },
      { name: 'AI 预测', type: 'line', data: top.map((r) => r.predict), smooth: true, symbolSize: 8, lineStyle: { color: '#ef4444', width: 3 }, itemStyle: { color: '#ef4444' } },
    ],
  }
})

function scoreColor(v: number | null) {
  if (v === null) return ''
  if (v >= 85) return 'success'
  if (v >= 60) return 'primary'
  return 'danger'
}
</script>

<template>
  <div class="grades-page">
    <div class="panel">
      <div class="top-bar">
        <div class="top-left">
          <span class="top-label">课程：</span>
          <el-radio-group v-model="selectedCourse">
            <el-radio-button v-for="c in courses" :key="c.id" :value="c.id">
              <span class="course-dot" :style="{ background: c.cover_color }"></span>{{ c.name }}
            </el-radio-button>
          </el-radio-group>
        </div>
        <div class="top-actions">
          <el-input v-model="keyword" placeholder="搜索学生姓名/学号" clearable size="default" style="width: 220px" />
          <el-button>
            <el-icon class="el-icon--left"><Upload /></el-icon>导入成绩
          </el-button>
          <el-button>
            <el-icon class="el-icon--left"><Download /></el-icon>导出总评
          </el-button>
          <el-button type="primary">
            <el-icon class="el-icon--left"><Edit /></el-icon>批量录入
          </el-button>
        </div>
      </div>
    </div>

    <el-row :gutter="12" class="stat-row">
      <el-col :xs="12" :sm="8" :md="4"><div class="mini-stat"><div class="ms-label">平均分</div><div class="ms-value" style="color:#4a7dff">{{ courseSummary.avg }}</div></div></el-col>
      <el-col :xs="12" :sm="8" :md="4"><div class="mini-stat"><div class="ms-label">最高分</div><div class="ms-value" style="color:#10b981">{{ courseSummary.max }}</div></div></el-col>
      <el-col :xs="12" :sm="8" :md="4"><div class="mini-stat"><div class="ms-label">最低分</div><div class="ms-value" style="color:#ef4444">{{ courseSummary.min }}</div></div></el-col>
      <el-col :xs="12" :sm="8" :md="4"><div class="mini-stat"><div class="ms-label">及格率</div><div class="ms-value" style="color:#00b4a6">{{ courseSummary.passRate }}%</div></div></el-col>
      <el-col :xs="12" :sm="8" :md="4"><div class="mini-stat"><div class="ms-label">优秀率</div><div class="ms-value" style="color:#9c27b0">{{ courseSummary.excellentRate }}%</div></div></el-col>
      <el-col :xs="12" :sm="8" :md="4"><div class="mini-stat"><div class="ms-label">应评人数</div><div class="ms-value">{{ students.length }}</div></div></el-col>
    </el-row>

    <el-row :gutter="12" class="chart-row">
      <el-col :xs="24" :md="10">
        <div class="panel">
          <div class="panel-title"><span class="panel-dot" style="background:#4a7dff"></span>成绩分布</div>
          <VChart :option="distOption" height="240px" />
        </div>
      </el-col>
      <el-col :xs="24" :md="7">
        <div class="panel">
          <div class="panel-title"><span class="panel-dot" style="background:#00b4a6"></span>总评公式占比</div>
          <VChart :option="weightOption" height="240px" />
          <div class="weight-hint">平时 {{ weights.daily }}% · 作业 {{ weights.assignment }}% · 实验 {{ weights.lab }}% · 期末 {{ weights.final }}%</div>
        </div>
      </el-col>
      <el-col :xs="24" :md="7">
        <div class="panel">
          <div class="panel-title"><span class="panel-dot" style="background:#ef4444"></span>AI 期末预测（TOP 10）</div>
          <VChart :option="predictOption" height="240px" />
        </div>
      </el-col>
    </el-row>

    <div class="panel">
      <div class="panel-title-bar">
        <div class="panel-title"><span class="panel-dot" style="background:#f59e0b"></span>成绩详表</div>
      </div>

      <el-table :data="scoreTable" stripe style="width:100%" :max-height="520" size="small">
        <el-table-column label="排名" type="index" width="70" align="center" />
        <el-table-column label="学号" prop="no" width="110" />
        <el-table-column label="姓名" prop="name" width="100">
          <template #default="{ row }">
            <el-avatar :size="22" style="vertical-align:-6px; margin-right:4px;background:linear-gradient(135deg,#4a7dff,#00b4a6);color:#fff;font-size:11px">
              {{ row.name.slice(0, 1) }}
            </el-avatar>
            <span style="font-weight:500">{{ row.name }}</span>
          </template>
        </el-table-column>
        <el-table-column label="班级" prop="class_name" width="140" />
        <el-table-column label="平时 30%" width="90" align="center">
          <template #default="{ row }"><el-tag size="small" type="info">{{ row.daily }}</el-tag></template>
        </el-table-column>
        <el-table-column label="作业均分 30%" width="120" align="center">
          <template #default="{ row }">
            <el-tag size="small" :type="scoreColor(row.assignAvg) as any">{{ row.assignAvg || '—' }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="实验 20%" width="90" align="center">
          <template #default="{ row }"><el-tag size="small" :type="scoreColor(row.lab) as any">{{ row.lab }}</el-tag></template>
        </el-table-column>
        <el-table-column label="期末 20%" width="90" align="center">
          <template #default="{ row }"><el-tag size="small" :type="scoreColor(row.final) as any">{{ row.final }}</el-tag></template>
        </el-table-column>
        <el-table-column label="总评" width="120" align="center">
          <template #default="{ row }">
            <div class="total-cell">
              <el-progress type="circle" :percentage="row.total" :width="38" :stroke-width="4"
                :color="row.total >= 85 ? '#10b981' : row.total >= 60 ? '#4a7dff' : '#ef4444'" />
            </div>
          </template>
        </el-table-column>
        <el-table-column label="AI 预测" width="110" align="center">
          <template #default="{ row }">
            <span :class="['predict', row.predict > row.total ? 'up' : row.predict < row.total ? 'down' : '']">
              {{ row.predict }}
              <small v-if="row.predict > row.total">↑{{ (row.predict - row.total).toFixed(1) }}</small>
              <small v-else-if="row.predict < row.total">↓{{ (row.total - row.predict).toFixed(1) }}</small>
            </span>
          </template>
        </el-table-column>
      </el-table>
    </div>
  </div>
</template>

<style scoped lang="scss">
.grades-page { display: flex; flex-direction: column; gap: 12px; }

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
  display: flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 8px;
}
.panel-dot { width: 8px; height: 8px; border-radius: 50%; }
.panel-title-bar {
  display: flex;
  justify-content: space-between;
  margin-bottom: 12px;
}

.top-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;

  .top-label { font-size: 13px; color: #6b7280; margin-right: 6px; }
  .top-actions { display: flex; gap: 8px; flex-wrap: wrap; }
}

.course-dot {
  display: inline-block;
  width: 8px; height: 12px;
  border-radius: 2px;
  margin-right: 5px;
  vertical-align: middle;
}

.stat-row .el-col { margin-bottom: 8px; }
.mini-stat {
  background: linear-gradient(135deg, #f8fafc, #fff);
  border: 1px solid #f1f5f9;
  border-radius: 8px;
  padding: 14px 10px;
  text-align: center;

  .ms-label { font-size: 12px; color: #6b7280; }
  .ms-value { font-size: 22px; font-weight: 700; color: #1f2937; margin-top: 4px; }
}

.chart-row .el-col { margin-bottom: 8px; }
.weight-hint {
  text-align: center;
  font-size: 11px;
  color: #9ca3af;
  margin-top: -10px;
}

.total-cell {
  display: flex;
  justify-content: center;

  :deep(.el-progress__text) {
    font-size: 11px !important;
    font-weight: 700;
  }
}

.predict {
  font-weight: 600;
  color: #4a7dff;

  small { font-size: 10px; margin-left: 2px; }
  &.up { color: #10b981; }
  &.down { color: #ef4444; }
}
</style>
