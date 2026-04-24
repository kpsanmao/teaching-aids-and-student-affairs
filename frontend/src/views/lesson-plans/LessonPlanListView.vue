<script setup lang="ts">
import { computed, ref } from 'vue'
import { ElMessage } from 'element-plus'
import { Document, MagicStick, Reading, Search, Upload } from '@element-plus/icons-vue'
import { courses, getCourseById, lessonPlans as mockPlans } from '@/mock'
import type { MockLessonPlan, MockLessonPlanSection } from '@/mock'

const plans = ref<MockLessonPlan[]>(mockPlans.map((p) => ({
  ...p,
  sections: p.sections.map((s) => ({ ...s })),
})))
const keyword = ref('')
const courseFilter = ref<number | ''>('')
const currentPlan = ref<MockLessonPlan | null>(plans.value.find((p) => p.status === 'ready') || null)
const uploadVisible = ref(false)
const aiProgress = ref(0)
const aiRunning = ref(false)

const filteredPlans = computed(() =>
  plans.value
    .filter((p) => !keyword.value || p.title.includes(keyword.value))
    .filter((p) => !courseFilter.value || p.course_id === courseFilter.value),
)

// 构建树形数据
function buildTree(sections: MockLessonPlanSection[]) {
  const map = new Map<number, any>()
  const result: any[] = []
  sections.forEach((s) => map.set(s.id, { ...s, children: [] }))
  sections.forEach((s) => {
    const node = map.get(s.id)
    if (s.parent_id && map.has(s.parent_id)) {
      map.get(s.parent_id).children.push(node)
    } else {
      result.push(node)
    }
  })
  return result
}

const sectionTree = computed(() => currentPlan.value ? buildTree(currentPlan.value.sections) : [])

function selectPlan(p: MockLessonPlan) {
  currentPlan.value = p
}

async function startAiAnalyze() {
  if (!currentPlan.value || currentPlan.value.status === 'ready') {
    ElMessage.info('该教案已完成 AI 解析')
    return
  }
  aiRunning.value = true
  aiProgress.value = 0
  for (let i = 0; i <= 100; i += 5) {
    aiProgress.value = i
    await new Promise((r) => setTimeout(r, 60))
  }
  // 模拟解析后填充章节
  const plan = currentPlan.value
  plan.status = 'ready'
  plan.sections = [
    { id: 100, plan_id: plan.id, parent_id: null, title: '6.1 为什么需要规范化', summary: '通过存在冗余与异常的例子引出规范化问题。', objectives: ['识别数据冗余', '说出更新异常'], keywords: ['冗余', '异常', '规范化'], estimate_minutes: 20, order: 1, level: 1 },
    { id: 101, plan_id: plan.id, parent_id: null, title: '6.2 函数依赖', summary: '讲解完全函数依赖、传递依赖。', objectives: ['理解函数依赖', '判断传递依赖'], keywords: ['FD', '传递依赖'], estimate_minutes: 40, order: 2, level: 1 },
    { id: 102, plan_id: plan.id, parent_id: 101, title: '6.2.1 完全/部分函数依赖', summary: '通过示例区分完全和部分依赖。', objectives: ['区分完全/部分依赖'], keywords: ['完全依赖', '部分依赖'], estimate_minutes: 15, order: 3, level: 2 },
    { id: 103, plan_id: plan.id, parent_id: null, title: '6.3 1NF / 2NF / 3NF', summary: '三大范式的定义与转换过程。', objectives: ['掌握三范式', '能手工分解关系模式'], keywords: ['1NF', '2NF', '3NF'], estimate_minutes: 50, order: 4, level: 1 },
    { id: 104, plan_id: plan.id, parent_id: null, title: '6.4 BCNF 与多值依赖', summary: 'BCNF 的定义、与 3NF 的关系。', objectives: ['说出 BCNF 定义', '识别多值依赖'], keywords: ['BCNF', '多值依赖'], estimate_minutes: 30, order: 5, level: 1 },
  ]
  ElMessage.success('AI 解析完成，已生成 5 个章节结构')
  aiRunning.value = false
}

function statusTag(s: string) {
  if (s === 'ready') return { text: '已解析', type: 'success' }
  if (s === 'parsing') return { text: '解析中', type: 'warning' }
  return { text: '失败', type: 'danger' }
}
</script>

<template>
  <div class="lesson-plans-page">
    <!-- 顶部 -->
    <div class="panel">
      <div class="top-bar">
        <div>
          <h2 class="page-title">教案管理</h2>
          <div class="page-sub">上传 .docx 教案，AI 自动识别章节结构与知识点</div>
        </div>
        <div class="top-actions">
          <el-input v-model="keyword" placeholder="搜索教案名称" :prefix-icon="Search" clearable size="default" style="width: 200px" />
          <el-select v-model="courseFilter" placeholder="课程筛选" clearable size="default" style="width: 180px">
            <el-option v-for="c in courses" :key="c.id" :label="c.name" :value="c.id" />
          </el-select>
          <el-button type="primary" @click="uploadVisible = true">
            <el-icon class="el-icon--left"><Upload /></el-icon>上传教案
          </el-button>
        </div>
      </div>
    </div>

    <el-row :gutter="12">
      <!-- 左侧列表 -->
      <el-col :xs="24" :md="8">
        <div class="panel">
          <div class="panel-title"><span class="panel-dot" style="background:#4a7dff"></span>教案列表</div>
          <div class="plan-list">
            <div v-for="p in filteredPlans" :key="p.id" class="plan-item"
              :class="{ active: currentPlan?.id === p.id }" @click="selectPlan(p)">
              <div class="plan-ic" :style="{ background: getCourseById(p.course_id)?.cover_color }">
                <el-icon :size="22" color="#fff"><Document /></el-icon>
              </div>
              <div class="plan-info">
                <div class="plan-title">{{ p.title }}</div>
                <div class="plan-meta">
                  <el-tag size="small" effect="plain">{{ getCourseById(p.course_id)?.name }}</el-tag>
                  <el-tag :type="statusTag(p.status).type as any" size="small">{{ statusTag(p.status).text }}</el-tag>
                </div>
                <div class="plan-sub">{{ p.uploaded_at }} · {{ p.file_size_kb }} KB · {{ p.sections.length }} 节</div>
              </div>
            </div>
          </div>
        </div>
      </el-col>

      <!-- 右侧详情 -->
      <el-col :xs="24" :md="16">
        <div v-if="!currentPlan" class="panel">
          <el-empty description="请从左侧选择教案查看详情" />
        </div>

        <div v-else class="panel detail-panel">
          <div class="detail-head">
            <div>
              <div class="detail-chapter">{{ currentPlan.chapter_no }}</div>
              <div class="detail-title">{{ currentPlan.title }}</div>
              <div class="detail-meta">
                <span>所属课程：{{ getCourseById(currentPlan.course_id)?.name }}</span>
                <el-divider direction="vertical" />
                <span>上传人：{{ currentPlan.uploader }}</span>
                <el-divider direction="vertical" />
                <span>{{ currentPlan.uploaded_at }}</span>
                <el-divider direction="vertical" />
                <span>{{ currentPlan.file_size_kb }} KB</span>
              </div>
            </div>
            <div class="detail-actions">
              <el-tag :type="statusTag(currentPlan.status).type as any" size="large">
                {{ statusTag(currentPlan.status).text }}
              </el-tag>
              <el-button type="primary" plain :loading="aiRunning" @click="startAiAnalyze"
                :disabled="currentPlan.status === 'ready'">
                <el-icon class="el-icon--left"><MagicStick /></el-icon>
                {{ currentPlan.status === 'ready' ? '重新分析' : 'AI 分析' }}
              </el-button>
            </div>
          </div>

          <!-- AI 分析进度 -->
          <div v-if="aiRunning || currentPlan.status === 'parsing'" class="ai-progress">
            <div class="ai-progress-head">
              <el-icon color="#6a5cff" class="is-loading"><MagicStick /></el-icon>
              <span>AI 正在解析教案结构 · {{ aiProgress }}%</span>
            </div>
            <el-progress :percentage="aiProgress" :show-text="false" :stroke-width="6"
              :color="[{ color: '#4a7dff', percentage: 50 }, { color: '#6a5cff', percentage: 100 }]" />
            <div class="ai-steps">
              <div :class="['step', aiProgress >= 20 ? 'done' : '']">📄 提取文本</div>
              <div :class="['step', aiProgress >= 50 ? 'done' : '']">🔍 识别章节</div>
              <div :class="['step', aiProgress >= 80 ? 'done' : '']">🎯 提炼目标</div>
              <div :class="['step', aiProgress >= 100 ? 'done' : '']">✅ 完成</div>
            </div>
          </div>

          <!-- 章节树 -->
          <div v-if="currentPlan.status === 'ready'" class="sections-wrap">
            <div class="section-summary">
              <div class="ss-item"><div class="ss-v">{{ currentPlan.sections.length }}</div><div class="ss-l">章节总数</div></div>
              <div class="ss-item"><div class="ss-v">{{ currentPlan.sections.reduce((s, c) => s + c.estimate_minutes, 0) }}</div><div class="ss-l">计划课时(分)</div></div>
              <div class="ss-item"><div class="ss-v">{{ [...new Set(currentPlan.sections.flatMap((s) => s.keywords))].length }}</div><div class="ss-l">关键词</div></div>
              <div class="ss-item"><div class="ss-v">{{ currentPlan.sections.reduce((s, c) => s + c.objectives.length, 0) }}</div><div class="ss-l">学习目标</div></div>
            </div>

            <h3 class="chapters-title"><el-icon><Reading /></el-icon> 章节结构</h3>
            <el-tree :data="sectionTree" :expand-on-click-node="false" default-expand-all node-key="id" class="section-tree">
              <template #default="{ data }">
                <div class="sec-row">
                  <div class="sec-main">
                    <span class="sec-title">{{ data.title }}</span>
                    <div class="sec-summary">{{ data.summary }}</div>
                    <div class="sec-obj">
                      <el-tag v-for="o in data.objectives" :key="o" size="small" class="obj-tag" effect="plain">{{ o }}</el-tag>
                    </div>
                    <div class="sec-kw">
                      <span class="kw-label">关键词：</span>
                      <el-tag v-for="k in data.keywords" :key="k" size="small" type="info" class="kw-tag" effect="light">#{{ k }}</el-tag>
                    </div>
                  </div>
                  <div class="sec-time">⏱ {{ data.estimate_minutes }}分钟</div>
                </div>
              </template>
            </el-tree>
          </div>

          <div v-else-if="!aiRunning" class="empty-hint">
            <el-empty description="尚未 AI 分析，点击右上角按钮开始识别章节结构" />
          </div>
        </div>
      </el-col>
    </el-row>

    <!-- 上传对话框 -->
    <el-dialog v-model="uploadVisible" title="上传教案" width="540px">
      <el-form label-width="100px">
        <el-form-item label="关联课程">
          <el-select placeholder="请选择课程" style="width: 100%">
            <el-option v-for="c in courses" :key="c.id" :label="c.name" :value="c.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="章节号">
          <el-input placeholder="例如：第 6 章" />
        </el-form-item>
        <el-form-item label="教案标题">
          <el-input placeholder="例如：关系数据库规范化" />
        </el-form-item>
        <el-form-item label="文件">
          <el-upload drag action="#" :auto-upload="false">
            <el-icon class="el-icon--upload" :size="48"><Upload /></el-icon>
            <div class="el-upload__text">拖拽 .docx 教案到此处，或<em>点击选择</em></div>
            <template #tip>
              <div class="el-upload__tip">
                仅支持 Microsoft Word .docx 文件，大小不超过 20MB<br>
                上传后 AI 将自动解析章节、提取学习目标与关键词
              </div>
            </template>
          </el-upload>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="uploadVisible = false">取消</el-button>
        <el-button type="primary" @click="() => { uploadVisible = false; ElMessage.success('教案已上传，正在排队解析') }">
          上传并解析
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<style scoped lang="scss">
.lesson-plans-page { display: flex; flex-direction: column; gap: 12px; }

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
  margin-bottom: 10px;
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

.plan-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
  max-height: 600px;
  overflow-y: auto;
}

.plan-item {
  display: flex;
  gap: 10px;
  padding: 10px;
  border-radius: 8px;
  border: 1px solid #f1f5f9;
  background: #fafbfc;
  cursor: pointer;
  transition: all 0.15s;

  &:hover {
    background: #eff6ff;
  }
  &.active {
    background: #eff6ff;
    border-color: #4a7dff;
    box-shadow: 0 0 0 2px rgba(74, 125, 255, 0.1);
  }

  .plan-ic {
    width: 42px;
    height: 42px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }
  .plan-info {
    flex: 1;
    min-width: 0;
  }
  .plan-title {
    font-size: 13.5px;
    font-weight: 500;
    color: #1f2937;
    line-height: 1.3;
  }
  .plan-meta {
    margin-top: 4px;
    display: flex;
    gap: 4px;
  }
  .plan-sub {
    font-size: 11px;
    color: #9ca3af;
    margin-top: 4px;
  }
}

.detail-panel {
  min-height: 500px;
}

.detail-head {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding-bottom: 16px;
  border-bottom: 1px solid #f1f5f9;
  margin-bottom: 16px;

  .detail-chapter {
    font-size: 12px;
    color: #4a7dff;
    font-weight: 600;
    letter-spacing: 2px;
  }
  .detail-title {
    font-size: 20px;
    font-weight: 700;
    color: #1f2937;
    margin: 4px 0 8px;
  }
  .detail-meta {
    font-size: 12px;
    color: #6b7280;
  }
  .detail-actions {
    display: flex;
    gap: 10px;
    align-items: center;
  }
}

.ai-progress {
  background: linear-gradient(90deg, #eff6ff, #eef2ff);
  border: 1px solid #dbeafe;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 16px;

  .ai-progress-head {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #4338ca;
    font-weight: 500;
    margin-bottom: 10px;
  }

  .ai-steps {
    display: flex;
    justify-content: space-between;
    margin-top: 10px;
    font-size: 11.5px;
    color: #9ca3af;

    .step {
      flex: 1;
      text-align: center;
      padding: 4px 8px;

      &.done {
        color: #10b981;
        font-weight: 500;
      }
    }
  }
}

.section-summary {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 10px;
  margin-bottom: 20px;

  .ss-item {
    background: #fafbfc;
    padding: 12px 10px;
    border-radius: 6px;
    text-align: center;
    border-left: 3px solid #4a7dff;

    &:nth-child(2) { border-left-color: #00b4a6; }
    &:nth-child(3) { border-left-color: #f59e0b; }
    &:nth-child(4) { border-left-color: #9c27b0; }

    .ss-v { font-size: 20px; font-weight: 700; color: #1f2937; line-height: 1; }
    .ss-l { font-size: 11px; color: #6b7280; margin-top: 4px; }
  }
}

.chapters-title {
  font-size: 14px;
  font-weight: 600;
  color: #1f2937;
  display: flex;
  align-items: center;
  gap: 6px;
  margin: 10px 0 14px;
}

.section-tree {
  :deep(.el-tree-node__content) {
    height: auto;
    padding: 8px 0;
    border-bottom: 1px dashed #f1f5f9;
  }
}

.sec-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 14px;
  flex: 1;
  padding: 4px 0;
}

.sec-main {
  flex: 1;

  .sec-title {
    font-size: 14px;
    font-weight: 600;
    color: #1f2937;
  }
  .sec-summary {
    font-size: 12.5px;
    color: #4b5563;
    margin-top: 4px;
    line-height: 1.5;
  }
  .sec-obj {
    margin-top: 6px;
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    .obj-tag { font-size: 11px; }
  }
  .sec-kw {
    margin-top: 4px;

    .kw-label { font-size: 11px; color: #9ca3af; }
    .kw-tag { margin-right: 4px; font-size: 11px; }
  }
}

.sec-time {
  font-size: 11px;
  color: #9ca3af;
  flex-shrink: 0;
}

.empty-hint {
  padding: 40px 0;
}
</style>
