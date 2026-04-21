<script setup lang="ts">
</script>

<template>
  <div class="login-page">
    <el-card class="login-card">
      <template #header>
        <div class="login-title">学情管理系统 · 登录</div>
      </template>
      <el-empty description="登录页占位，待实现表单与接口联调" />
    </el-card>
  </div>
</template>

<style scoped lang="scss">
.login-page {
  height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #409eff 0%, #1d6dff 100%);
}

.login-card {
  width: 400px;
}

.login-title {
  font-size: 18px;
  font-weight: 600;
  text-align: center;
}
</style>
