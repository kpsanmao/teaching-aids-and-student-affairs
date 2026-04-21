<script setup lang="ts">
import { useRoute, useRouter } from 'vue-router'
import { computed } from 'vue'

const route = useRoute()
const router = useRouter()

const menus = computed(() =>
  router
    .getRoutes()
    .filter((r) => r.meta?.title && !r.meta?.hidden && !r.meta?.public && r.name !== undefined)
    .filter((r) => r.path !== '/' && !r.path.includes(':'))
    .map((r) => ({
      path: r.path.startsWith('/') ? r.path : `/${r.path}`,
      title: r.meta?.title as string,
      icon: (r.meta?.icon as string) || 'Menu',
    })),
)

const activeMenu = computed(() => route.path)
</script>

<template>
  <el-container class="app-layout">
    <el-aside width="220px" class="app-aside">
      <div class="brand">学情管理系统</div>
      <el-menu :default-active="activeMenu" router background-color="#001529" text-color="#b7bdc3"
        active-text-color="#ffffff">
        <el-menu-item v-for="m in menus" :key="m.path" :index="m.path">
          <span>{{ m.title }}</span>
        </el-menu-item>
      </el-menu>
    </el-aside>
    <el-container>
      <el-header class="app-header">
        <div class="header-title">{{ (route.meta?.title as string) || '' }}</div>
      </el-header>
      <el-main class="app-main">
        <router-view />
      </el-main>
    </el-container>
  </el-container>
</template>

<style scoped lang="scss">
.app-layout {
  height: 100vh;
}

.app-aside {
  background: #001529;
  color: #fff;

  .brand {
    height: 60px;
    line-height: 60px;
    text-align: center;
    font-size: 18px;
    font-weight: 600;
    color: #fff;
    letter-spacing: 2px;
  }

  :deep(.el-menu) {
    border-right: 0;
  }
}

.app-header {
  background: #fff;
  border-bottom: 1px solid var(--el-border-color-light);
  display: flex;
  align-items: center;

  .header-title {
    font-size: 16px;
    font-weight: 500;
  }
}

.app-main {
  background: #f5f7fa;
  padding: 16px;
}
</style>
