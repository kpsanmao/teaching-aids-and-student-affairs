<script setup lang="ts">
import { computed, ref } from 'vue'
import { ElMessage } from 'element-plus'
import { Clock, MagicStick, Plus } from '@element-plus/icons-vue'
import VChart from '@/components/common/VChart.vue'
import {
  assignments as mockAssignments,
  assignmentScores,
  assignmentSuggestions,
  courses,
  getCourseById,
  getStudentById,
} from '@/mock'
import type { MockAssignment, MockAssignmentSuggestion } from '@/mock'

const keyword = ref('')
const courseFilter = ref<number | ''>('')
const statusFilter = ref<'' | 'open' | 'closed'>('')

const dialogVisible = ref(false)
const currentAssignment = ref<MockAssignment | null>(null)
const suggestions = ref<MockAssignmentSuggestion[]>([])
const streaming = ref(false)
const streamingIdx = ref(-1)

const typeTagMap: Record<string, string> = {
  作业: 'primary',
  实验: 'success',
  项目: 'warning',
  测验: 'danger',
}
const difficultyColor: Record<string, string> = {
  基础: '#10b981',
  进阶: '#f59e0b',
  挑战: '#ef4444',
}

const filtered = computed(() => {
  return mockAssignments
    .filter((a) => !keyword.value || a.title.includes(keyword.value))
    .filter((a) => !courseFilter.value || a.course_id === courseFilter.value)
    .filter((a) => !statusFilter.value || a.status === statusFilter.value)
    .map((a) => {
      const scores = assignmentScores.filter((s) => s.assignment_id === a.id && s.score !== null)
      const avg = scores.length
        ? +(scores.reduce((sum, s) => sum + (s.score || 0), 0) / scores.length).toFixed(1)
        : 0
      return {
        ...a,
        course: getCourseById(a.course_id),
        avg,
        rate: a.total ? +((a.submitted / a.total) * 100).toFixed(0) : 0,
      }
    })
})

const stats = computed(() => {
  const all = mockAssignments
  const open = all.filter((a) => a.status === 'open').length
  const totalSub = all.reduce((s, a) => s + a.submitted, 0)
  const totalAll = all.reduce((s, a) => s + a.total, 0)
  const rate = totalAll ? ((totalSub / totalAll) * 100).toFixed(1) : '0'
  const withScores = assignmentScores.filter((s) => s.score !== null)
  const avg = withScores.length
    ? (withScores.reduce((sum, s) => sum + (s.score || 0), 0) / withScores.length).toFixed(1)
    : '0'
  return { total: all.length, open, rate, avg }
})

function statusText(s: string) {
  return s === 'open' ? '进行中' : '已截止'
}

function openDetail(a: MockAssignment) {
  currentAssignment.value = a
  suggestions.value = []
  streamingIdx.value = -1
  streaming.value = false
  dialogVisible.value = true
  // 自动加载已存在的建议
  const existing = assignmentSuggestions.filter((s) => s.assignment_id === a.id)
  suggestions.value = existing.map((s) => ({ ...s }))
}

async function generateSuggestions() {
  if (!currentAssignment.value || streaming.value) return
  streaming.value = true
  suggestions.value = []
  const template = assignmentSuggestions.filter((s) => s.assignment_id === currentAssignment.value!.id)
  const seed: MockAssignmentSuggestion[] = template.length
    ? template
    : assignmentSuggestions.slice(0, 3).map((s) => ({
      ...s,
      assignment_id: currentAssignment.value!.id,
      title: '建议：' + currentAssignment.value!.title,
    }))

  for (let i = 0; i < seed.length; i++) {
    streamingIdx.value = i
    const draft: MockAssignmentSuggestion = { ...seed[i], content: '', objectives: [], reference: [] }
    suggestions.value.push(draft)
    // 逐字追加 content 营造流式效果
    const fullContent = seed[i].content
    for (let k = 0; k < fullContent.length; k += 3) {
      draft.content = fullContent.slice(0, k + 3)
      suggestions.value = [...suggestions.value]
      await new Promise((r) => setTimeout(r, 20))
    }
    draft.objectives = seed[i].objectives
    draft.reference = seed[i].reference
    suggestions.value = [...suggestions.value]
    await new Promise((r) => setTimeout(r, 150))
  }
  streaming.value = false
  streamingIdx.value = -1
  ElMessage.success(`AI 已生成 ${seed.length} 条作业建议`)
}

function acceptSuggestion(s: MockAssignmentSuggestion) {
  s.status = 'accepted'
  ElMessage.success('已采纳该建议，将并入作业描述')
}
function rejectSuggestion(s: MockAssignmentSuggestion) {
  s.status = 'rejected'
  ElMessage.info('已忽略该建议')
}

const scoreDistOption = computed(() => {
  if (!currentAssignment.value) return {}
  const scores = assignmentScores
    .filter((s) => s.assignment_id === currentAssignment.value!.id && s.score !== null)
    .map((s) => s.score as number)
  const bins = [0, 0, 0, 0, 0] // <60, 60-70, 70-80, 80-90, 90-100
  scores.forEach((sc) => {
    if (sc < 60) bins[0]++
    else if (sc < 70) bins[1]++
    else if (sc < 80) bins[2]++
    else if (sc < 90) bins[3]++
    else bins[4]++
  })
  return {
    tooltip: { trigger: 'axis' },
    grid: { left: 30, right: 10, top: 20, bottom: 28 },
    xAxis: { type: 'category', data: ['<60', '60-70', '70-80', '80-90', '90-100'] },
    yAxis: { type: 'value', splitLine: { lineStyle: { color: '#f1f5f9' } } },
    series: [
      {
        type: 'bar',
        data: bins,
        barWidth: 30,
        itemStyle: {
          color: {
            type: 'linear', x: 0, y: 0, x2: 0, y2: 1,
            colorStops: [
              { offset: 0, color: '#4a7dff' },
              { offset: 1, color: '#93c5fd' },
            ],
          },
          borderRadius: [6, 6, 0, 0],
        },
      },
    ],
  }
})

const submittedList = computed(() => {
  if (!currentAssignment.value) return []
  return assignmentScores
    .filter((s) => s.assignment_id === currentAssignment.value!.id)
    .map((s) => ({
      ...s,
      student: getStudentById(s.student_id),
    }))
    .slice(0, 12)
})
</script>

<template>
  <div class="assignments-page">
    <el-row :gutter="12" class="stat-row">
      <el-col :xs="12" :md="6">
        <div class="stat-card">
          <div class="stat-label">作业总数</div>
          <div class="stat-value">{{ stats.total }}<span>项</span></div>
        </div>
      </el-col>
      <el-col :xs="12" :md="6">
        <div class="stat-card">
          <div class="stat-label">进行中</div>
          <div class="stat-value" style="color:#f57c00">{{ stats.open }}<span>项</span></div>
        </div>
      </el-col>
      <el-col :xs="12" :md="6">
        <div class="stat-card">
          <div class="stat-label">整体提交率</div>
          <div class="stat-value" style="color:#00b4a6">{{ stats.rate }}<span>%</span></div>
        </div>
      </el-col>
      <el-col :xs="12" :md="6">
        <div class="stat-card">
          <div class="stat-label">平均分</div>
          <div class="stat-value" style="color:#4a7dff">{{ stats.avg }}<span>分</span></div>
        </div>
      </el-col>
    </el-row>

    <div class="panel">
      <div class="panel-title-bar">
        <div class="panel-title">
          <span class="panel-dot" style="background:#4a7dff"></span>作业列表
        </div>
        <div class="filter-bar">
          <el-input v-model="keyword" placeholder="搜索作业标题" clearable size="default" style="width: 200px" />
          <el-select v-model="courseFilter" placeholder="课程" clearable size="default" style="width: 180px">
            <el-option v-for="c in courses" :key="c.id" :label="c.name" :value="c.id" />
          </el-select>
          <el-select v-model="statusFilter" placeholder="状态" clearable size="default" style="width: 110px">
            <el-option label="进行中" value="open" />
            <el-option label="已截止" value="closed" />
          </el-select>
          <el-button type="primary">
            <el-icon class="el-icon--left"><Plus /></el-icon>
            布置作业
          </el-button>
        </div>
      </div>

      <el-row :gutter="14">
        <el-col v-for="a in filtered" :key="a.id" :xs="24" :sm="12" :lg="8">
          <div class="assignment-card" @click="openDetail(a)">
            <div class="card-top" :style="{ background: a.course?.cover_color }">
              <el-tag :type="typeTagMap[a.type] as any" effect="dark" class="type-tag">{{ a.type }}</el-tag>
              <el-tag :type="a.status === 'open' ? 'success' : 'info'" effect="dark" size="small" class="status-tag">
                {{ statusText(a.status) }}
              </el-tag>
            </div>
            <div class="card-body">
              <div class="card-course">{{ a.course?.name }}</div>
              <div class="card-title">{{ a.title }}</div>
              <div class="card-desc">{{ a.description }}</div>

              <div class="card-progress">
                <div class="card-progress-label">
                  <span>提交进度</span>
                  <span class="card-progress-value">{{ a.submitted }}/{{ a.total }}（{{ a.rate }}%）</span>
                </div>
                <el-progress :percentage="a.rate" :stroke-width="8"
                  :color="a.rate >= 90 ? '#10b981' : a.rate >= 60 ? '#4a7dff' : '#f57c00'" :show-text="false" />
              </div>

              <div class="card-footer">
                <div class="card-deadline">
                  <el-icon><Clock /></el-icon>
                  截止：{{ a.deadline }}
                </div>
                <div class="card-avg" v-if="a.avg">
                  平均分 <b>{{ a.avg }}</b>
                </div>
              </div>
            </div>
          </div>
        </el-col>
      </el-row>
    </div>

    <!-- 详情对话框 -->
    <el-dialog v-model="dialogVisible" :title="currentAssignment?.title" width="920px" destroy-on-close top="5vh">
      <template v-if="currentAssignment">
        <div class="dlg-head">
          <el-tag :type="typeTagMap[currentAssignment.type] as any" effect="dark">{{ currentAssignment.type }}</el-tag>
          <el-tag :type="currentAssignment.status === 'open' ? 'success' : 'info'" effect="light">
            {{ statusText(currentAssignment.status) }}
          </el-tag>
          <span class="dlg-course">{{ getCourseById(currentAssignment.course_id)?.name }}</span>
          <span class="dlg-deadline">截止：{{ currentAssignment.deadline }}</span>
        </div>

        <el-tabs class="dlg-tabs">
          <el-tab-pane label="作业描述">
            <div class="dlg-desc">{{ currentAssignment.description }}</div>
            <el-row :gutter="12" class="dlg-row">
              <el-col :span="8">
                <div class="small-stat"><div class="ss-label">提交人数</div><div class="ss-value">{{ currentAssignment.submitted }}/{{ currentAssignment.total }}</div></div>
              </el-col>
              <el-col :span="8">
                <div class="small-stat"><div class="ss-label">满分</div><div class="ss-value">{{ currentAssignment.total_score }}</div></div>
              </el-col>
              <el-col :span="8">
                <div class="small-stat"><div class="ss-label">发布时间</div><div class="ss-value">{{ currentAssignment.published_at }}</div></div>
              </el-col>
            </el-row>

            <div class="dlg-sub-title">成绩分布</div>
            <VChart :option="scoreDistOption" height="220px" />
          </el-tab-pane>

          <el-tab-pane>
            <template #label>
              <span>
                <el-icon style="vertical-align:-2px"><MagicStick /></el-icon>
                AI 建议
                <el-badge v-if="suggestions.length" :value="suggestions.length" class="tab-badge" />
              </span>
            </template>

            <div class="ai-toolbar">
              <el-alert type="info" :closable="false">
                <template #title>
                  基于课程教案向量索引与该作业要求，AI 将生成 <b>3 条</b>难度递进的补充建议（基础 / 进阶 / 挑战）。
                </template>
              </el-alert>
              <el-button type="primary" :loading="streaming" @click="generateSuggestions" class="ai-btn">
                <el-icon class="el-icon--left"><MagicStick /></el-icon>
                {{ suggestions.length ? '重新生成' : '生成 AI 建议' }}
              </el-button>
            </div>

            <div class="suggestion-list">
              <div v-for="(s, i) in suggestions" :key="s.id" class="sugg-card"
                :class="{ 'streaming': streaming && streamingIdx === i, 'accepted': s.status === 'accepted', 'rejected': s.status === 'rejected' }">
                <div class="sugg-header">
                  <el-tag :color="difficultyColor[s.difficulty]" class="diff-tag" effect="dark">{{ s.difficulty }}</el-tag>
                  <span class="sugg-title">{{ s.title }}</span>
                  <span class="sugg-est">⏱ {{ s.estimate_minutes }} 分钟</span>
                </div>
                <div class="sugg-obj" v-if="s.objectives && s.objectives.length">
                  <span class="sugg-label">学习目标：</span>
                  <el-tag v-for="o in s.objectives" :key="o" size="small" class="obj-tag" effect="plain">{{ o }}</el-tag>
                </div>
                <div class="sugg-content">
                  {{ s.content }}<span v-if="streaming && streamingIdx === i" class="cursor-blink">▊</span>
                </div>
                <div class="sugg-ref" v-if="s.reference && s.reference.length">
                  <span class="sugg-label">参考：</span>
                  <span v-for="r in s.reference" :key="r" class="ref-item">{{ r }}</span>
                </div>
                <div class="sugg-actions">
                  <el-button v-if="s.status === 'pending'" type="primary" plain size="small" @click="acceptSuggestion(s)">采纳</el-button>
                  <el-button v-if="s.status === 'pending'" size="small" @click="rejectSuggestion(s)">忽略</el-button>
                  <el-tag v-else-if="s.status === 'accepted'" type="success" size="small">已采纳</el-tag>
                  <el-tag v-else type="info" size="small">已忽略</el-tag>
                </div>
              </div>
              <el-empty v-if="!suggestions.length && !streaming" description="尚未生成 AI 建议" />
            </div>
          </el-tab-pane>

          <el-tab-pane label="提交详情">
            <el-table :data="submittedList" size="small" stripe>
              <el-table-column label="学号" prop="student.student_no" width="110" />
              <el-table-column label="姓名" prop="student.name" width="100" />
              <el-table-column label="班级" prop="student.class_name" width="140" />
              <el-table-column label="提交时间" prop="submitted_at" width="120">
                <template #default="{ row }">
                  <span v-if="row.submitted_at">{{ row.submitted_at }}</span>
                  <el-tag v-else type="danger" size="small">未提交</el-tag>
                </template>
              </el-table-column>
              <el-table-column label="得分" width="100">
                <template #default="{ row }">
                  <el-tag v-if="row.score !== null" :type="row.score >= 85 ? 'success' : row.score >= 60 ? 'warning' : 'danger'">
                    {{ row.score }}
                  </el-tag>
                  <span v-else>—</span>
                </template>
              </el-table-column>
              <el-table-column label="评语" prop="feedback">
                <template #default="{ row }">
                  <span v-if="row.feedback" class="fb">{{ row.feedback }}</span>
                  <span v-else class="fb-empty">—</span>
                </template>
              </el-table-column>
            </el-table>
          </el-tab-pane>
        </el-tabs>
      </template>
    </el-dialog>
  </div>
</template>

<style scoped lang="scss">
.assignments-page {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.stat-row .el-col { margin-bottom: 8px; }

.stat-card {
  background: #fff;
  border-radius: 10px;
  padding: 16px 20px;
  border: 1px solid #f1f5f9;

  .stat-label { font-size: 12px; color: #6b7280; margin-bottom: 6px; }
  .stat-value {
    font-size: 26px; font-weight: 700; line-height: 1;
    span { font-size: 13px; font-weight: 400; color: #9ca3af; margin-left: 4px; }
  }
}

.panel {
  background: #fff;
  border-radius: 10px;
  padding: 14px 18px;
  border: 1px solid #f1f5f9;
}

.panel-title {
  font-size: 14px; font-weight: 600; color: #1f2937;
  display: flex; align-items: center; gap: 6px;
}
.panel-dot { width: 8px; height: 8px; border-radius: 50%; }
.panel-title-bar {
  display: flex; justify-content: space-between; align-items: center;
  margin-bottom: 14px; flex-wrap: wrap; gap: 12px;
}
.filter-bar { display: flex; gap: 8px; flex-wrap: wrap; }

.assignment-card {
  background: #fff;
  border-radius: 10px;
  overflow: hidden;
  border: 1px solid #f1f5f9;
  margin-bottom: 14px;
  cursor: pointer;
  transition: transform 0.15s, box-shadow 0.15s;

  &:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
  }

  .card-top {
    height: 60px;
    position: relative;
    padding: 10px 14px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    background: linear-gradient(135deg, #4a7dff, #6a5cff);

    .type-tag, .status-tag { border: none; }
  }

  .card-body {
    padding: 14px 18px 16px;
  }

  .card-course {
    font-size: 11px;
    color: #9ca3af;
    letter-spacing: 1px;
  }
  .card-title {
    font-size: 15px;
    font-weight: 600;
    color: #1f2937;
    margin: 4px 0 8px;
  }
  .card-desc {
    font-size: 12.5px;
    color: #6b7280;
    line-height: 1.6;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 40px;
  }
  .card-progress {
    margin-top: 12px;
    .card-progress-label {
      display: flex;
      justify-content: space-between;
      font-size: 11.5px;
      color: #6b7280;
      margin-bottom: 4px;

      .card-progress-value { color: #1f2937; font-weight: 500; }
    }
  }
  .card-footer {
    margin-top: 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 12px;
    color: #6b7280;

    .card-deadline {
      display: flex; align-items: center; gap: 4px;
    }
    .card-avg b { color: #4a7dff; font-weight: 700; margin-left: 2px; }
  }
}

.dlg-head {
  display: flex;
  align-items: center;
  gap: 10px;
  padding-bottom: 12px;
  border-bottom: 1px solid #f1f5f9;

  .dlg-course { color: #6b7280; font-size: 13px; }
  .dlg-deadline { margin-left: auto; font-size: 12px; color: #ef4444; }
}

.dlg-tabs { margin-top: 4px; }
.dlg-desc {
  padding: 12px 14px;
  background: #f8fafc;
  border-radius: 6px;
  font-size: 13px;
  line-height: 1.7;
  color: #4b5563;
}
.dlg-row { margin-top: 12px; }
.small-stat {
  background: #f8fafc;
  border-radius: 6px;
  padding: 10px 12px;
  text-align: center;

  .ss-label { font-size: 11px; color: #9ca3af; }
  .ss-value { font-size: 16px; font-weight: 600; color: #1f2937; margin-top: 4px; }
}
.dlg-sub-title {
  margin: 16px 0 6px;
  font-size: 13px;
  font-weight: 600;
  color: #1f2937;
}

.ai-toolbar {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;

  .el-alert { flex: 1; }
  .ai-btn {
    background: linear-gradient(135deg, #6a5cff, #4a7dff);
    border: none;
  }
}

.tab-badge :deep(.el-badge__content) {
  transform: scale(0.9);
  margin-left: 4px;
}

.suggestion-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.sugg-card {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-left: 3px solid #4a7dff;
  border-radius: 8px;
  padding: 12px 14px;
  transition: background 0.2s;

  &.streaming {
    background: linear-gradient(90deg, #eff6ff, #fff);
    border-left-color: #6a5cff;
    animation: pulse 1.6s infinite;
  }
  &.accepted {
    background: #f0fdf4;
    border-left-color: #10b981;
  }
  &.rejected {
    opacity: 0.6;
    border-left-color: #9ca3af;
  }

  .sugg-header {
    display: flex; align-items: center; gap: 10px;
    .sugg-title {
      font-size: 14px; font-weight: 600; color: #1f2937;
      flex: 1;
    }
    .sugg-est { font-size: 11px; color: #9ca3af; }
    .diff-tag { border: none; color: #fff !important; }
  }

  .sugg-obj {
    margin-top: 8px;
    .obj-tag { margin-right: 6px; }
  }
  .sugg-label { font-size: 12px; color: #6b7280; margin-right: 4px; }

  .sugg-content {
    margin-top: 8px;
    font-size: 13px;
    color: #4b5563;
    line-height: 1.75;
    background: #f9fafb;
    padding: 10px 12px;
    border-radius: 6px;
    white-space: pre-wrap;
  }
  .sugg-ref {
    margin-top: 6px;
    font-size: 12px;
    color: #6b7280;
    .ref-item {
      margin-right: 10px;
      color: #4a7dff;
    }
  }
  .sugg-actions {
    margin-top: 10px;
    display: flex; gap: 8px;
  }
}

.cursor-blink {
  color: #6a5cff;
  animation: blink 1s infinite;
}

@keyframes blink { 50% { opacity: 0; } }
@keyframes pulse {
  0%, 100% { box-shadow: 0 0 0 0 rgba(106, 92, 255, 0.15); }
  50% { box-shadow: 0 0 0 6px rgba(106, 92, 255, 0); }
}

.fb { color: #f57c00; font-size: 12.5px; }
.fb-empty { color: #d1d5db; }
</style>
