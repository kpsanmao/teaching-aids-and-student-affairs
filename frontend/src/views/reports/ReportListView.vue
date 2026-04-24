<script setup lang="ts">
import { computed, ref } from 'vue'
import { ElMessage } from 'element-plus'
import { Document, Download, MagicStick, Plus, Printer, View } from '@element-plus/icons-vue'
import VChart from '@/components/common/VChart.vue'
import {
  assignmentScores,
  assignments,
  attendances,
  getCourseById,
  getCourseStudents,
  reports as mockReports,
  sessions,
} from '@/mock'

const reports = ref(mockReports.map((r) => ({ ...r })))
const previewVisible = ref(false)
const currentReport = ref<typeof reports.value[number] | null>(null)

function openPreview(r: typeof reports.value[number]) {
  currentReport.value = r
  previewVisible.value = true
}

function generateNew() {
  ElMessage.success('已进入生成队列，预计 2 分钟内完成')
}

function doPrint() {
  window.print()
}

// 报告内的分析数据
const reportStats = computed(() => {
  if (!currentReport.value) return null
  const cid = currentReport.value.course_id
  const c = getCourseById(cid)
  const cs = sessions.filter((s) => s.course_id === cid && s.status === 'done')
  const att = attendances.filter((a) => cs.some((s) => s.id === a.session_id))
  const present = att.filter((a) => a.status === 'present').length
  const absent = att.filter((a) => a.status === 'absent').length
  const rate = att.length ? +((present / att.length) * 100).toFixed(1) : 0

  const assn = assignments.filter((a) => a.course_id === cid)
  const scores = assignmentScores
    .filter((s) => assn.some((a) => a.id === s.assignment_id) && s.score !== null)
    .map((s) => s.score as number)
  const avg = scores.length ? +(scores.reduce((a, b) => a + b, 0) / scores.length).toFixed(1) : 0
  const max = scores.length ? Math.max(...scores) : 0
  const min = scores.length ? Math.min(...scores) : 0

  const students = getCourseStudents(cid)

  return {
    course: c,
    attendance_rate: rate,
    absent_count: absent,
    average_score: avg,
    max_score: max,
    min_score: min,
    students_count: students.length,
    sessions_done: cs.length,
  }
})

const reportAttOption = computed(() => {
  if (!reportStats.value) return {}
  return {
    tooltip: { trigger: 'item' },
    legend: { bottom: 0, textStyle: { fontSize: 11 } },
    series: [{
      type: 'pie', radius: ['55%', '78%'], center: ['50%', '45%'],
      itemStyle: { borderRadius: 6, borderColor: '#fff', borderWidth: 2 },
      label: {
        formatter: '{b}: {d}%',
        fontSize: 11,
      },
      data: [
        { value: Math.round(reportStats.value.attendance_rate), name: '出勤', itemStyle: { color: '#10b981' } },
        { value: 5, name: '迟到', itemStyle: { color: '#f59e0b' } },
        { value: 2, name: '请假', itemStyle: { color: '#6b7280' } },
        { value: Math.round(100 - reportStats.value.attendance_rate - 7), name: '缺勤', itemStyle: { color: '#ef4444' } },
      ],
    }],
  }
})

const reportScoreOption = computed(() => ({
  tooltip: { trigger: 'axis' },
  grid: { left: 32, right: 10, top: 20, bottom: 28 },
  xAxis: { type: 'category', data: ['<60', '60-70', '70-80', '80-90', '90-100'] },
  yAxis: { type: 'value' },
  series: [{
    type: 'bar', barWidth: 30,
    data: [2, 4, 12, 28, 14],
    label: { show: true, position: 'top', fontSize: 11 },
    itemStyle: {
      color: (p: any) => ['#ef4444', '#f59e0b', '#fbbf24', '#4a7dff', '#10b981'][p.dataIndex],
      borderRadius: [6, 6, 0, 0],
    },
  }],
}))

const reportTrendOption = computed(() => ({
  tooltip: { trigger: 'axis' },
  legend: { data: ['班级均分', '本人均分'], bottom: 0, textStyle: { fontSize: 11 } },
  grid: { left: 36, right: 10, top: 26, bottom: 40 },
  xAxis: { type: 'category', data: ['第1次', '第2次', '第3次', '第4次', '第5次'], axisLabel: { fontSize: 11 } },
  yAxis: { type: 'value', min: 60, max: 100 },
  series: [
    { name: '班级均分', type: 'line', data: [78, 82, 81, 85, 84], smooth: true, itemStyle: { color: '#9ca3af' }, lineStyle: { color: '#9ca3af' } },
    { name: '本人均分', type: 'line', data: [82, 85, 88, 87, 90], smooth: true, itemStyle: { color: '#4a7dff' }, lineStyle: { color: '#4a7dff', width: 3 } },
  ],
}))
</script>

<template>
  <div class="reports-page">
    <div class="panel">
      <div class="top-bar">
        <div>
          <h2 class="page-title">学情报告</h2>
          <div class="page-sub">基于出勤 / 作业 / 成绩 / 预警数据由 AI 自动生成的结构化报告</div>
        </div>
        <el-button type="primary" @click="generateNew">
          <el-icon class="el-icon--left"><Plus /></el-icon>
          新建报告
        </el-button>
      </div>
    </div>

    <el-row :gutter="14">
      <el-col v-for="r in reports" :key="r.id" :xs="24" :sm="12" :lg="8">
        <div class="report-card" @click="r.status === 'ready' ? openPreview(r) : null">
          <div class="card-banner" :style="{ background: getCourseById(r.course_id)?.cover_color }">
            <el-icon :size="32"><Document /></el-icon>
            <el-tag v-if="r.status === 'ready'" effect="dark" type="success" class="status-tag">已生成</el-tag>
            <el-tag v-else effect="dark" type="warning" class="status-tag">生成中...</el-tag>
          </div>
          <div class="card-body">
            <div class="card-course">{{ getCourseById(r.course_id)?.name }}</div>
            <div class="card-title">{{ r.title }}</div>
            <div class="card-period">
              <el-icon><Document /></el-icon>
              报告周期：{{ r.period }}
            </div>
            <div class="card-summary">{{ r.summary || '报告正在生成中，请稍候...' }}</div>
            <div class="card-footer">
              <div class="card-time">
                <template v-if="r.status === 'ready'">
                  共 {{ r.pages }} 页 · {{ r.generated_at }}
                </template>
                <template v-else>
                  <el-icon class="is-loading"><MagicStick /></el-icon>
                  AI 正在分析 {{ Math.round(60) }}%
                </template>
              </div>
              <el-button v-if="r.status === 'ready'" size="small" type="primary" plain>
                <el-icon class="el-icon--left"><View /></el-icon>预览
              </el-button>
            </div>
          </div>
        </div>
      </el-col>
    </el-row>

    <!-- 报告预览 -->
    <el-dialog v-model="previewVisible" width="880px" top="3vh" :show-close="true" class="report-dialog" destroy-on-close>
      <template #header>
        <div class="dialog-head">
          <span>{{ currentReport?.title }}</span>
          <div class="dialog-actions">
            <el-button size="small" @click="doPrint">
              <el-icon class="el-icon--left"><Printer /></el-icon>打印 / PDF
            </el-button>
            <el-button size="small" type="primary">
              <el-icon class="el-icon--left"><Download /></el-icon>下载
            </el-button>
          </div>
        </div>
      </template>

      <div v-if="currentReport && reportStats" class="report-paper">
        <!-- 封面 -->
        <div class="rp-cover" :style="{ background: `linear-gradient(135deg, ${reportStats.course?.cover_color || '#4a7dff'}, #6a5cff)` }">
          <div class="cover-badge">学情管理系统 · AI 报告</div>
          <h1 class="cover-title">{{ currentReport.title }}</h1>
          <div class="cover-course">{{ reportStats.course?.name }}</div>
          <div class="cover-period">报告周期：{{ currentReport.period }}</div>
          <div class="cover-footer">
            <div>任课教师：示例教师</div>
            <div>生成时间：{{ currentReport.generated_at }}</div>
          </div>
        </div>

        <!-- 摘要 -->
        <div class="rp-section">
          <h2 class="rp-h2">一、总体摘要</h2>
          <p class="rp-p">{{ currentReport.summary }}</p>

          <el-row :gutter="10" class="rp-stat-row">
            <el-col :span="6"><div class="rp-stat"><div class="rs-val" style="color:#4a7dff">{{ reportStats.students_count }}</div><div class="rs-lbl">选课学生</div></div></el-col>
            <el-col :span="6"><div class="rp-stat"><div class="rs-val" style="color:#10b981">{{ reportStats.attendance_rate }}%</div><div class="rs-lbl">平均出勤</div></div></el-col>
            <el-col :span="6"><div class="rp-stat"><div class="rs-val" style="color:#f59e0b">{{ reportStats.average_score }}</div><div class="rs-lbl">作业均分</div></div></el-col>
            <el-col :span="6"><div class="rp-stat"><div class="rs-val" style="color:#ef4444">{{ reportStats.absent_count }}</div><div class="rs-lbl">缺勤人次</div></div></el-col>
          </el-row>
        </div>

        <!-- 出勤分析 -->
        <div class="rp-section">
          <h2 class="rp-h2">二、出勤情况分析</h2>
          <p class="rp-p">统计周期内共组织 <b>{{ reportStats.sessions_done }}</b> 次课，平均出勤率 <b>{{ reportStats.attendance_rate }}%</b>，整体稳定。</p>
          <el-row :gutter="10">
            <el-col :span="12">
              <VChart :option="reportAttOption" height="240px" />
              <div class="chart-caption">图 1 出勤结构分布</div>
            </el-col>
            <el-col :span="12">
              <VChart :option="reportTrendOption" height="240px" />
              <div class="chart-caption">图 2 近 5 次课次出勤趋势（班级 vs 本人）</div>
            </el-col>
          </el-row>
        </div>

        <!-- 成绩分析 -->
        <div class="rp-section">
          <h2 class="rp-h2">三、作业与成绩分析</h2>
            <p class="rp-p">
            本阶段共布置 {{ assignments.filter((a) => a.course_id === currentReport!.course_id).length }} 项作业，学生完成情况良好，平均分 <b>{{ reportStats.average_score }}</b>，
            最高 {{ reportStats.max_score }}，最低 {{ reportStats.min_score }}。成绩分布整体集中在 80-90 分区间，知识掌握基础扎实。
          </p>
          <VChart :option="reportScoreOption" height="260px" />
          <div class="chart-caption">图 3 学生成绩分布</div>
        </div>

        <!-- AI 建议 -->
        <div class="rp-section">
          <h2 class="rp-h2">四、AI 教学建议</h2>
          <div class="ai-suggestion">
            <div class="ai-item">
              <div class="ai-tag" style="background:#eff6ff;color:#4a7dff">教学建议</div>
              <div class="ai-body">针对成绩分布右偏的特点，建议在下一阶段重点关注 <b>&lt; 70 分段</b> 的 6 名学生，可布置 2 份针对性巩固作业，难度以"基础 + 进阶"搭配为宜。</div>
            </div>
            <div class="ai-item">
              <div class="ai-tag" style="background:#f0fdf4;color:#10b981">学习建议</div>
              <div class="ai-body">实验类作业平均分比理论作业低 4.2 分，建议在课堂上补充 1-2 个典型实验案例解析，并安排助教 1 对 1 答疑时间。</div>
            </div>
            <div class="ai-item">
              <div class="ai-tag" style="background:#fef2f2;color:#ef4444">关注名单</div>
              <div class="ai-body">以下 3 名学生近期成绩连续下降，建议本周主动沟通：<b>张梓轩、李若曦、王俊杰</b>。</div>
            </div>
          </div>
        </div>

        <!-- 签章 -->
        <div class="rp-signature">
          <div>任课教师：<span class="sig-line">示例教师</span></div>
          <div>日期：<span class="sig-line">{{ currentReport.generated_at.slice(0, 10) }}</span></div>
        </div>
      </div>
    </el-dialog>
  </div>
</template>

<style scoped lang="scss">
.reports-page { display: flex; flex-direction: column; gap: 12px; }

.panel {
  background: #fff;
  border-radius: 10px;
  padding: 14px 18px;
  border: 1px solid #f1f5f9;
}

.top-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
}
.page-title { margin: 0; font-size: 18px; color: #1f2937; }
.page-sub { font-size: 12px; color: #9ca3af; margin-top: 2px; }

.report-card {
  background: #fff;
  border-radius: 12px;
  overflow: hidden;
  border: 1px solid #f1f5f9;
  margin-bottom: 14px;
  cursor: pointer;
  transition: all 0.2s;

  &:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
  }
}

.card-banner {
  height: 72px;
  padding: 14px 16px;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: linear-gradient(135deg, #4a7dff, #6a5cff);

  .status-tag { border: none; }
}

.card-body {
  padding: 14px 18px;
}

.card-course {
  font-size: 11px;
  color: #9ca3af;
  letter-spacing: 1px;
}
.card-title {
  font-size: 16px;
  font-weight: 600;
  color: #1f2937;
  margin: 4px 0 8px;
}
.card-period {
  font-size: 12px;
  color: #6b7280;
  display: flex;
  align-items: center;
  gap: 4px;
  margin-bottom: 8px;
}
.card-summary {
  font-size: 12.5px;
  color: #4b5563;
  line-height: 1.6;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
  min-height: 58px;
}
.card-footer {
  margin-top: 10px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 10px;
  border-top: 1px dashed #f1f5f9;

  .card-time {
    font-size: 11px;
    color: #9ca3af;
    display: flex;
    align-items: center;
    gap: 4px;
  }
}

.dialog-head {
  display: flex;
  justify-content: space-between;
  align-items: center;

  .dialog-actions { display: flex; gap: 6px; }
}

// 报告纸样式
.report-paper {
  background: #fff;
  max-height: 80vh;
  overflow-y: auto;
  font-family: 'PingFang SC', 'Microsoft YaHei', serif;
  padding-bottom: 20px;
}

.rp-cover {
  color: #fff;
  padding: 40px 50px;
  border-radius: 8px;
  margin-bottom: 24px;
  position: relative;

  .cover-badge {
    display: inline-block;
    background: rgba(255, 255, 255, 0.2);
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    letter-spacing: 2px;
  }
  .cover-title {
    font-size: 28px;
    margin: 20px 0 16px;
    font-weight: 700;
    line-height: 1.3;
  }
  .cover-course {
    font-size: 16px;
    opacity: 0.9;
  }
  .cover-period {
    font-size: 13px;
    opacity: 0.85;
    margin-top: 6px;
  }
  .cover-footer {
    margin-top: 36px;
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    opacity: 0.85;
  }
}

.rp-section {
  padding: 0 30px;
  margin-bottom: 28px;
}

.rp-h2 {
  font-size: 18px;
  color: #1f2937;
  margin: 0 0 14px;
  padding-left: 10px;
  border-left: 4px solid #4a7dff;
}

.rp-p {
  font-size: 13.5px;
  line-height: 1.85;
  color: #374151;
  text-indent: 2em;

  b { color: #4a7dff; }
}

.rp-stat-row { margin-top: 14px; margin-bottom: 6px; }
.rp-stat {
  background: #f8fafc;
  padding: 14px 10px;
  border-radius: 6px;
  text-align: center;

  .rs-val { font-size: 22px; font-weight: 700; line-height: 1; }
  .rs-lbl { font-size: 12px; color: #6b7280; margin-top: 4px; }
}

.chart-caption {
  text-align: center;
  font-size: 11.5px;
  color: #6b7280;
  margin-top: -4px;
  margin-bottom: 10px;
}

.ai-suggestion {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.ai-item {
  display: flex;
  gap: 12px;
  padding: 12px 14px;
  border-radius: 6px;
  background: #fafbfc;
  border-left: 3px solid #4a7dff;

  .ai-tag {
    padding: 3px 10px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
    flex-shrink: 0;
    align-self: flex-start;
  }
  .ai-body {
    font-size: 13px;
    line-height: 1.7;
    color: #374151;

    b { color: #4a7dff; font-weight: 600; }
  }
}

.rp-signature {
  padding: 0 30px;
  display: flex;
  justify-content: space-between;
  font-size: 13px;
  color: #374151;
  margin-top: 40px;
  padding-top: 20px;
  border-top: 1px dashed #e5e7eb;

  .sig-line {
    display: inline-block;
    border-bottom: 1px solid #6b7280;
    padding: 0 20px;
    margin-left: 6px;
  }
}

@media print {
  body * { visibility: hidden; }
  .report-paper, .report-paper * { visibility: visible; }
  .report-paper { position: absolute; left: 0; top: 0; width: 100%; }
}
</style>
