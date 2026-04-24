<script setup lang="ts">
import { computed, ref } from 'vue'
import { Download, Plus, Search, Upload } from '@element-plus/icons-vue'
import VChart from '@/components/common/VChart.vue'
import {
  alerts,
  assignmentScores,
  assignments,
  attendances,
  courseStudentMap,
  students,
} from '@/mock'

const keyword = ref('')
const classFilter = ref('')

const classes = computed(() => Array.from(new Set(students.map((s) => s.class_name))))

const enriched = computed(() => {
  return students.map((s) => {
    const courseIds = Object.entries(courseStudentMap)
      .filter(([, ids]) => ids.includes(s.id))
      .map(([cid]) => +cid)

    const sScores = assignmentScores.filter((x) => x.student_id === s.id && x.score !== null).map((x) => x.score as number)
    const avg = sScores.length ? +(sScores.reduce((a, b) => a + b, 0) / sScores.length).toFixed(1) : 0

    const att = attendances.filter((a) => a.student_id === s.id)
    const present = att.filter((a) => a.status === 'present').length
    const attRate = att.length ? +((present / att.length) * 100).toFixed(1) : 100

    const alertCount = alerts.filter((a) => a.student_id === s.id && a.status !== 'resolved').length

    const missCount = assignments.filter((a) => {
      const rec = assignmentScores.find((x) => x.assignment_id === a.id && x.student_id === s.id)
      return !rec || rec.score === null
    }).length

    return {
      ...s,
      courses_count: courseIds.length,
      avg_score: avg,
      att_rate: attRate,
      alert_count: alertCount,
      miss_count: missCount,
    }
  })
})

const filtered = computed(() =>
  enriched.value
    .filter((s) => !keyword.value || s.name.includes(keyword.value) || s.student_no.includes(keyword.value))
    .filter((s) => !classFilter.value || s.class_name === classFilter.value),
)

// 班级分布
const classOption = computed(() => ({
  tooltip: { trigger: 'item' },
  legend: { bottom: 0, textStyle: { fontSize: 11 } },
  series: [{
    type: 'pie', radius: ['50%', '75%'], center: ['50%', '45%'],
    itemStyle: { borderRadius: 6, borderColor: '#fff', borderWidth: 2 },
    label: { show: false },
    data: classes.value.map((c, i) => ({
      name: c,
      value: students.filter((s) => s.class_name === c).length,
      itemStyle: { color: ['#4a7dff', '#00b4a6', '#f59e0b', '#9c27b0'][i] },
    })),
  }],
}))

const scoreHistogramOption = computed(() => {
  const scores = enriched.value.map((s) => s.avg_score).filter((x) => x > 0)
  const bins = [0, 0, 0, 0, 0]
  scores.forEach((v) => {
    if (v < 60) bins[0]++
    else if (v < 70) bins[1]++
    else if (v < 80) bins[2]++
    else if (v < 90) bins[3]++
    else bins[4]++
  })
  return {
    tooltip: { trigger: 'axis' },
    grid: { left: 32, right: 10, top: 20, bottom: 28 },
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
</script>

<template>
  <div class="students-page">
    <div class="panel">
      <div class="top-bar">
        <div>
          <h2 class="page-title">学生名册</h2>
          <div class="page-sub">共 {{ students.length }} 名学生 · {{ classes.length }} 个行政班</div>
        </div>
        <div class="top-actions">
          <el-input v-model="keyword" placeholder="搜索姓名 / 学号" clearable size="default" :prefix-icon="Search" style="width: 220px" />
          <el-select v-model="classFilter" placeholder="班级筛选" clearable size="default" style="width: 180px">
            <el-option v-for="c in classes" :key="c" :label="c" :value="c" />
          </el-select>
          <el-button>
            <el-icon class="el-icon--left"><Upload /></el-icon>批量导入
          </el-button>
          <el-button>
            <el-icon class="el-icon--left"><Download /></el-icon>导出名册
          </el-button>
          <el-button type="primary">
            <el-icon class="el-icon--left"><Plus /></el-icon>新增学生
          </el-button>
        </div>
      </div>
    </div>

    <el-row :gutter="12">
      <el-col :xs="24" :md="10">
        <div class="panel"><div class="panel-title"><span class="panel-dot" style="background:#4a7dff"></span>班级分布</div>
          <VChart :option="classOption" height="220px" />
        </div>
      </el-col>
      <el-col :xs="24" :md="14">
        <div class="panel"><div class="panel-title"><span class="panel-dot" style="background:#00b4a6"></span>学生平均成绩分布</div>
          <VChart :option="scoreHistogramOption" height="220px" />
        </div>
      </el-col>
    </el-row>

    <div class="panel">
      <el-table :data="filtered" stripe style="width: 100%" :max-height="560" size="default">
        <el-table-column label="#" type="index" width="60" align="center" />
        <el-table-column label="学号" prop="student_no" width="110" sortable />
        <el-table-column label="姓名" width="120">
          <template #default="{ row }">
            <div class="stu-cell">
              <el-avatar :size="28" class="stu-avatar">{{ row.name.slice(0, 1) }}</el-avatar>
              <span class="stu-name">{{ row.name }}</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="班级" width="160">
          <template #default="{ row }">
            <el-tag size="small" effect="plain">{{ row.class_name }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="所选课程" width="110" align="center">
          <template #default="{ row }">
            <el-tag size="small" type="info">{{ row.courses_count }} 门</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="出勤率" width="180">
          <template #default="{ row }">
            <div class="rate-cell">
              <el-progress :percentage="row.att_rate" :stroke-width="8" :show-text="false"
                :color="row.att_rate >= 90 ? '#10b981' : row.att_rate >= 80 ? '#f59e0b' : '#ef4444'" />
              <span>{{ row.att_rate }}%</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="作业均分" width="110" align="center" sortable prop="avg_score">
          <template #default="{ row }">
            <el-tag v-if="row.avg_score" size="small"
              :type="row.avg_score >= 85 ? 'success' : row.avg_score >= 60 ? 'primary' : 'danger'">
              {{ row.avg_score }}
            </el-tag>
            <span v-else class="no-score">—</span>
          </template>
        </el-table-column>
        <el-table-column label="未交作业" width="100" align="center">
          <template #default="{ row }">
            <el-tag v-if="row.miss_count > 0" type="warning" size="small">{{ row.miss_count }}</el-tag>
            <span v-else class="no-score">0</span>
          </template>
        </el-table-column>
        <el-table-column label="预警" width="90" align="center">
          <template #default="{ row }">
            <el-badge v-if="row.alert_count > 0" :value="row.alert_count" class="alert-badge">
              <el-tag type="danger" size="small" effect="dark">待处理</el-tag>
            </el-badge>
            <el-tag v-else type="success" size="small" effect="plain">正常</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="160">
          <template #default>
            <el-button size="small" type="primary" plain>查看详情</el-button>
            <el-button size="small" link>编辑</el-button>
          </template>
        </el-table-column>
      </el-table>
    </div>
  </div>
</template>

<style scoped lang="scss">
.students-page { display: flex; flex-direction: column; gap: 12px; }

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
.panel-dot { width: 8px; height: 8px; border-radius: 50%; }

.top-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
}

.page-title { margin: 0; font-size: 18px; color: #1f2937; }
.page-sub { font-size: 12px; color: #9ca3af; margin-top: 2px; }

.top-actions { display: flex; gap: 8px; flex-wrap: wrap; }

.stu-cell { display: flex; align-items: center; gap: 8px; }
.stu-avatar {
  background: linear-gradient(135deg, #4a7dff, #00b4a6);
  color: #fff;
  font-size: 12px;
}
.stu-name { font-weight: 500; color: #1f2937; }

.rate-cell {
  display: flex;
  align-items: center;
  gap: 8px;

  :deep(.el-progress) { flex: 1; }
  span { font-size: 12px; color: #4b5563; min-width: 40px; text-align: right; }
}

.no-score { color: #d1d5db; }

.alert-badge :deep(.el-badge__content) {
  top: 2px;
  right: 4px;
}

.el-row + .el-row { margin-top: 12px; }
.el-row .el-col { margin-bottom: 8px; }
</style>
