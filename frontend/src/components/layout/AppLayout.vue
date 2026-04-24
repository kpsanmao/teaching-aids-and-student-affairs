<script setup lang="ts">
import { useRoute, useRouter } from 'vue-router'
import { computed, h, resolveComponent } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
  ArrowDown,
  Bell,
  Calendar,
  Collection,
  DataLine,
  Document,
  EditPen,
  House,
  Reading,
  SwitchButton,
  TrendCharts,
  User,
  UserFilled,
  Warning,
} from '@element-plus/icons-vue'

import { useAuthStore } from '@/stores/auth'
import { logoutApi } from '@/api/auth'
import { alerts } from '@/mock'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

interface MenuGroup {
  title: string
  items: { path: string; title: string; icon: any }[]
}

const menuGroups = computed<MenuGroup[]>(() => [
  {
    title: '概览',
    items: [
      { path: '/dashboard', title: '工作台', icon: House },
    ],
  },
  {
    title: '教学管理',
    items: [
      { path: '/courses', title: '我的课程', icon: Collection },
      { path: '/lesson-plans', title: '教案管理', icon: Reading },
      { path: '/sessions', title: '课次与考勤', icon: Calendar },
      { path: '/assignments', title: '作业与建议', icon: EditPen },
      { path: '/grades', title: '成绩管理', icon: DataLine },
    ],
  },
  {
    title: '学情洞察',
    items: [
      { path: '/alerts', title: '预警中心', icon: Warning },
      { path: '/analytics', title: '学情分析', icon: TrendCharts },
      { path: '/reports', title: '学情报告', icon: Document },
    ],
  },
  {
    title: '基础数据',
    items: [
      { path: '/students', title: '学生名册', icon: UserFilled },
    ],
  },
])

const activeMenu = computed(() => route.path)
const userName = computed(() => auth.user?.name || '示例教师')
const userEmail = computed(() => auth.user?.email || 'teacher@lncu.cn')
const userInitial = computed(() => (auth.user?.name || '示').slice(0, 1))

const unreadAlerts = computed(() => alerts.filter((a) => a.status === 'unread'))

function toAlerts() {
  router.push({ name: 'alerts' })
}

async function handleCommand(cmd: string) {
  if (cmd === 'logout') {
    try {
      await ElMessageBox.confirm('确定要退出登录吗？', '提示', {
        type: 'warning',
        confirmButtonText: '退出',
        cancelButtonText: '取消',
      })
    } catch {
      return
    }

    try {
      await logoutApi()
    } catch {
      // 即使后端失败也清空本地
    }
    auth.clear()
    ElMessage.success('已退出登录')
    router.replace('/login')
  }
}

// el-icon 的函数式渲染（Element Plus 图标组件）
function renderIcon(icon: any) {
  const ElIcon = resolveComponent('el-icon')
  return h(ElIcon as any, null, { default: () => h(icon) })
}
</script>

<template>
  <el-container class="app-layout">
    <el-aside width="220px" class="app-aside">
      <div class="brand">
        <div class="brand-logo">学</div>
        <div class="brand-text">
          <div class="brand-title">学情管理系统</div>
          <div class="brand-sub">Teaching Analytics</div>
        </div>
      </div>

      <el-scrollbar class="menu-scroll">
        <el-menu
          :default-active="activeMenu"
          router
          background-color="transparent"
          text-color="#c6cbd3"
          active-text-color="#fff"
        >
          <template v-for="g in menuGroups" :key="g.title">
            <div class="menu-group-title">{{ g.title }}</div>
            <el-menu-item v-for="m in g.items" :key="m.path" :index="m.path">
              <component :is="renderIcon(m.icon)" />
              <span>{{ m.title }}</span>
            </el-menu-item>
          </template>
        </el-menu>
      </el-scrollbar>

      <div class="aside-footer">© 2026 学情管理系统</div>
    </el-aside>

    <el-container>
      <el-header class="app-header">
        <div class="header-left">
          <div class="header-title">{{ (route.meta?.title as string) || '' }}</div>
          <el-tag size="small" type="info" round class="header-tag">示例环境 · Demo Data</el-tag>
        </div>
        <div class="header-right">
          <el-tooltip content="预警中心" placement="bottom">
            <el-badge :value="unreadAlerts.length" :max="99" :hidden="unreadAlerts.length === 0" class="bell-badge">
              <el-button circle @click="toAlerts">
                <el-icon><Bell /></el-icon>
              </el-button>
            </el-badge>
          </el-tooltip>
          <el-dropdown @command="handleCommand">
            <div class="user-wrap">
              <el-avatar :size="32" class="user-avatar">{{ userInitial }}</el-avatar>
              <div class="user-meta">
                <div class="user-name">{{ userName }}</div>
                <div class="user-role">教师</div>
              </div>
              <el-icon class="user-arrow"><ArrowDown /></el-icon>
            </div>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item disabled>
                  <el-icon><User /></el-icon>
                  {{ userEmail }}
                </el-dropdown-item>
                <el-dropdown-item divided command="logout">
                  <el-icon><SwitchButton /></el-icon>
                  退出登录
                </el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </div>
      </el-header>
      <el-main class="app-main">
        <router-view v-slot="{ Component }">
          <transition name="fade" mode="out-in">
            <component :is="Component" />
          </transition>
        </router-view>
      </el-main>
    </el-container>
  </el-container>
</template>

<style scoped lang="scss">
.app-layout {
  height: 100vh;
}

.app-aside {
  background: linear-gradient(180deg, #1f2937 0%, #111827 100%);
  color: #fff;
  display: flex;
  flex-direction: column;

  .brand {
    height: 64px;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 0 18px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);

    .brand-logo {
      width: 36px;
      height: 36px;
      border-radius: 10px;
      background: linear-gradient(135deg, #4a7dff, #00b4a6);
      color: #fff;
      font-weight: 700;
      font-size: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 4px 12px rgba(74, 125, 255, 0.4);
    }

    .brand-text {
      line-height: 1.2;
    }

    .brand-title {
      font-size: 15px;
      font-weight: 600;
      color: #fff;
      letter-spacing: 1px;
    }

    .brand-sub {
      font-size: 11px;
      color: #8b96a6;
      letter-spacing: 1px;
    }
  }

  .menu-scroll {
    flex: 1;
    padding-top: 10px;
  }

  .menu-group-title {
    padding: 12px 20px 6px;
    font-size: 11px;
    color: #6b7280;
    letter-spacing: 2px;
  }

  .aside-footer {
    padding: 10px 18px;
    font-size: 11px;
    color: #6b7280;
    border-top: 1px solid rgba(255, 255, 255, 0.06);
  }

  :deep(.el-menu) {
    border-right: 0;
  }

  :deep(.el-menu-item) {
    height: 44px;
    line-height: 44px;
    margin: 2px 10px;
    border-radius: 8px;
    padding-left: 14px !important;

    &:hover {
      background: rgba(255, 255, 255, 0.06) !important;
      color: #fff !important;
    }

    &.is-active {
      background: linear-gradient(90deg, #4a7dff 0%, #6a5cff 100%) !important;
      color: #fff !important;
      font-weight: 500;
      box-shadow: 0 4px 12px rgba(74, 125, 255, 0.3);
    }

    .el-icon {
      margin-right: 10px;
      font-size: 16px;
    }
  }
}

.app-header {
  background: #fff;
  border-bottom: 1px solid var(--el-border-color-light);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 24px;
  height: 60px;

  .header-left {
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .header-title {
    font-size: 16px;
    font-weight: 600;
    color: #1f2937;
  }

  .header-tag {
    background: #fff5e6 !important;
    border: none !important;
    color: #d97706 !important;
  }

  .header-right {
    display: flex;
    align-items: center;
    gap: 14px;
  }
}

.bell-badge :deep(.el-badge__content) {
  top: 6px;
  right: 10px;
}

.user-wrap {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  padding: 4px 10px;
  border-radius: 8px;
  transition: background 0.2s;

  &:hover {
    background: var(--el-fill-color-light);
  }
}

.user-avatar {
  background: linear-gradient(135deg, #4a7dff, #6a5cff);
  color: #fff;
  font-weight: 500;
}

.user-meta {
  line-height: 1.1;
}

.user-name {
  font-size: 13px;
  font-weight: 500;
  color: var(--el-text-color-primary);
}

.user-role {
  font-size: 11px;
  color: var(--el-text-color-secondary);
}

.user-arrow {
  color: var(--el-text-color-secondary);
  font-size: 12px;
}

.app-main {
  background: #f5f7fa;
  padding: 20px;
  overflow: auto;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
