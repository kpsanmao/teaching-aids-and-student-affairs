<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { Calendar, Collection, EditPen, Plus, UserFilled } from '@element-plus/icons-vue'
import {
  alerts,
  assignments,
  attendances,
  courses,
  sessions,
} from '@/mock'

const router = useRouter()
const keyword = ref('')
const typeFilter = ref('')

const enriched = computed(() => {
  return courses.map((c) => {
    const cs = sessions.filter((s) => s.course_id === c.id)
    const done = cs.filter((s) => s.status === 'done').length
    const progress = cs.length ? Math.round((done / cs.length) * 100) : 0

    const records = attendances.filter((a) => cs.some((s) => s.id === a.session_id))
    const present = records.filter((a) => a.status === 'present').length
    const attRate = records.length ? +((present / records.length) * 100).toFixed(1) : 0

    const assn = assignments.filter((a) => a.course_id === c.id)
    const openAssn = assn.filter((a) => a.status === 'open').length
    const alertCount = alerts.filter((a) => a.course_id === c.id && a.status !== 'resolved').length

    return { ...c, done, progress, att_rate: attRate, open_assignments: openAssn, alert_count: alertCount }
  })
})

const filtered = computed(() =>
  enriched.value
    .filter((c) => !keyword.value || c.name.includes(keyword.value))
    .filter((c) => !typeFilter.value || c.course_type === typeFilter.value),
)

function openDetail(id: number) {
  router.push({ name: 'course-detail', params: { id } })
}
</script>

<template>
  <div class="courses-page">
    <div class="panel">
      <div class="top-bar">
        <div>
          <h2 class="page-title">我的课程</h2>
          <div class="page-sub">共 {{ courses.length }} 门 · 本学期进行中</div>
        </div>
        <div class="top-actions">
          <el-input v-model="keyword" placeholder="搜索课程名称" clearable size="default" style="width: 200px" />
          <el-select v-model="typeFilter" placeholder="课程类型" clearable size="default" style="width: 130px">
            <el-option label="必修课" value="必修课" />
            <el-option label="选修课" value="选修课" />
            <el-option label="实验课" value="实验课" />
          </el-select>
          <el-button type="primary">
            <el-icon class="el-icon--left"><Plus /></el-icon>新建课程
          </el-button>
        </div>
      </div>
    </div>

    <el-row :gutter="14">
      <el-col v-for="c in filtered" :key="c.id" :xs="24" :sm="12" :md="12" :lg="8" :xl="6">
        <div class="course-card" @click="openDetail(c.id)">
          <div class="course-banner" :style="{ background: `linear-gradient(135deg, ${c.cover_color}, ${c.cover_color}dd)` }">
            <div class="banner-decoration">
              <el-icon :size="80"><Collection /></el-icon>
            </div>
            <el-tag class="type-tag" effect="dark" :color="'rgba(255,255,255,0.2)'">{{ c.course_type }}</el-tag>
            <div class="banner-credit">{{ c.credit }} 学分</div>
            <div class="banner-title">{{ c.name }}</div>
            <div class="banner-semester">{{ c.semester }}</div>
          </div>

          <div class="course-body">
            <div class="row-label">
              <span>学期进度</span>
              <span class="row-value">{{ c.done }}/{{ c.sessions_count }} 课次</span>
            </div>
            <el-progress :percentage="c.progress" :stroke-width="6" :show-text="false"
              :color="c.progress >= 70 ? '#10b981' : '#4a7dff'" />

            <div class="metrics">
              <div class="metric">
                <el-icon color="#4a7dff"><UserFilled /></el-icon>
                <span class="m-value">{{ c.students_count }}</span>
                <span class="m-label">学生</span>
              </div>
              <div class="metric">
                <el-icon color="#10b981"><Calendar /></el-icon>
                <span class="m-value">{{ c.att_rate }}%</span>
                <span class="m-label">出勤</span>
              </div>
              <div class="metric">
                <el-icon color="#f59e0b"><EditPen /></el-icon>
                <span class="m-value">{{ c.open_assignments }}</span>
                <span class="m-label">待交</span>
              </div>
            </div>

            <div class="course-footer">
              <el-tag v-if="c.alert_count > 0" type="danger" size="small" effect="dark">
                {{ c.alert_count }} 条预警
              </el-tag>
              <el-tag v-else type="success" size="small" effect="plain">状态良好</el-tag>
              <el-button link type="primary" size="small">进入课程 →</el-button>
            </div>
          </div>
        </div>
      </el-col>
    </el-row>
  </div>
</template>

<style scoped lang="scss">
.courses-page { display: flex; flex-direction: column; gap: 12px; }

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

.top-actions { display: flex; gap: 8px; flex-wrap: wrap; }

.course-card {
  background: #fff;
  border-radius: 12px;
  overflow: hidden;
  border: 1px solid #f1f5f9;
  margin-bottom: 14px;
  cursor: pointer;
  transition: all 0.2s;

  &:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 28px rgba(0, 0, 0, 0.1);
  }
}

.course-banner {
  height: 110px;
  position: relative;
  padding: 16px 18px;
  color: #fff;
  overflow: hidden;

  .banner-decoration {
    position: absolute;
    right: -20px;
    top: -20px;
    opacity: 0.15;
    transform: rotate(-15deg);
  }

  .type-tag {
    border: 1px solid rgba(255, 255, 255, 0.3) !important;
    color: #fff !important;
    position: absolute;
    top: 12px;
    right: 14px;
  }

  .banner-credit {
    font-size: 11px;
    opacity: 0.85;
    letter-spacing: 1px;
  }

  .banner-title {
    font-size: 20px;
    font-weight: 700;
    margin-top: 24px;
  }

  .banner-semester {
    font-size: 12px;
    opacity: 0.85;
    margin-top: 4px;
  }
}

.course-body {
  padding: 14px 18px 16px;
}

.row-label {
  display: flex;
  justify-content: space-between;
  font-size: 12px;
  color: #6b7280;
  margin-bottom: 4px;

  .row-value { color: #1f2937; font-weight: 500; }
}

.metrics {
  display: flex;
  justify-content: space-around;
  margin-top: 14px;
  padding: 12px 0;
  border-top: 1px dashed #f1f5f9;
  border-bottom: 1px dashed #f1f5f9;
}

.metric {
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2px;

  .m-value {
    font-size: 16px;
    font-weight: 700;
    color: #1f2937;
    line-height: 1;
  }

  .m-label {
    font-size: 11px;
    color: #9ca3af;
  }
}

.course-footer {
  margin-top: 10px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
</style>
