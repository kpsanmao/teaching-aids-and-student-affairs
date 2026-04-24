<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage, type FormInstance, type FormRules } from 'element-plus'
import { Lock, User } from '@element-plus/icons-vue'
import { loginApi } from '@/api/auth'
import { useAuthStore } from '@/stores/auth'
import { loginText as L } from './i18n'

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()

const formRef = ref<FormInstance>()
const loading = ref(false)
const form = reactive({
  email: '',
  password: '',
  device_name: 'web',
})

const rules: FormRules = {
  email: [
    { required: true, message: L.rule_email_required, trigger: 'blur' },
    { type: 'email', message: L.rule_email_format, trigger: 'blur' },
  ],
  password: [
    { required: true, message: L.rule_pwd_required, trigger: 'blur' },
    { min: 6, message: L.rule_pwd_min, trigger: 'blur' },
  ],
}

function fillDemo(role: 'teacher' | 'admin') {
  if (role === 'teacher') {
    form.email = 'teacher@lncu.cn'
    form.password = 'password'
  } else {
    form.email = 'admin@lncu.cn'
    form.password = 'password'
  }
}

async function submit() {
  if (!formRef.value) return
  await formRef.value.validate(async (valid) => {
    if (!valid) return
    loading.value = true
    try {
      const res = await loginApi(form)
      auth.setAuth({ token: res.data.token, user: res.data.user })
      ElMessage.success(L.welcome_back + res.data.user.name)
      const redirect = (route.query.redirect as string) || '/dashboard'
      router.replace(redirect)
    } catch {
      // intercepted by axios
    } finally {
      loading.value = false
    }
  })
}
</script>

<template>
  <div class="login-page">
    <div class="login-hero">
      <div class="hero-inner">
        <div class="hero-badge">{{ L.hero_badge }}</div>
        <h1 class="hero-title">{{ L.hero_title_1 }}<br />{{ L.hero_title_2 }}</h1>
        <p class="hero-desc">{{ L.hero_desc }}</p>
        <div class="hero-features">
          <div class="hero-feature"><span class="feat-dot">1</span>{{ L.feat_1 }}</div>
          <div class="hero-feature"><span class="feat-dot">2</span>{{ L.feat_2 }}</div>
          <div class="hero-feature"><span class="feat-dot">3</span>{{ L.feat_3 }}</div>
          <div class="hero-feature"><span class="feat-dot">4</span>{{ L.feat_4 }}</div>
        </div>
      </div>
    </div>

    <div class="login-form-wrap">
      <el-card class="login-card" shadow="never">
        <div class="card-title">{{ L.card_title }}</div>
        <div class="card-sub">{{ L.card_sub }}</div>

        <el-form ref="formRef" :model="form" :rules="rules" label-position="top" @keyup.enter="submit">
          <el-form-item :label="L.label_email" prop="email">
            <el-input v-model="form.email" placeholder="your@lncu.cn" :prefix-icon="User" />
          </el-form-item>
          <el-form-item :label="L.label_password" prop="password">
            <el-input v-model="form.password" type="password" :placeholder="L.ph_password" show-password :prefix-icon="Lock" />
          </el-form-item>
          <el-form-item>
            <el-button type="primary" :loading="loading" class="submit-btn" @click="submit">
              {{ L.btn_login }}
            </el-button>
          </el-form-item>
        </el-form>
        <div class="demo-tip">
          <span>{{ L.demo_label }}</span>
          <el-link type="primary" underline="never" @click="fillDemo('teacher')">{{ L.demo_teacher }}</el-link>
          <el-divider direction="vertical" />
          <el-link type="info" underline="never" @click="fillDemo('admin')">{{ L.demo_admin }}</el-link>
        </div>
      </el-card>
    </div>
  </div>
</template>

<style scoped lang="scss">
.login-page {
  height: 100vh;
  display: flex;
}

.login-hero {
  flex: 1.2;
  background: linear-gradient(135deg, #4a7dff 0%, #6a5cff 50%, #00b4a6 100%);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  overflow: hidden;
}

.login-hero::before {
  content: '';
  position: absolute;
  width: 480px;
  height: 480px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.08);
  right: -120px;
  top: -120px;
}

.login-hero::after {
  content: '';
  position: absolute;
  width: 360px;
  height: 360px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.06);
  left: -100px;
  bottom: -80px;
}

.hero-inner {
  position: relative;
  z-index: 1;
  max-width: 520px;
  padding: 40px;
}

.hero-badge {
  display: inline-block;
  background: rgba(255, 255, 255, 0.2);
  padding: 5px 14px;
  border-radius: 20px;
  font-size: 12px;
  letter-spacing: 2px;
}

.hero-title {
  font-size: 36px;
  font-weight: 700;
  line-height: 1.4;
  margin: 24px 0 20px;
}

.hero-desc {
  font-size: 14px;
  line-height: 1.8;
  opacity: 0.9;
  margin-bottom: 32px;
}

.hero-features {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px 20px;
}

.hero-feature {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 14px;
}

.feat-dot {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.25);
  font-size: 12px;
  font-weight: 600;
  flex-shrink: 0;
}

.login-form-wrap {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f5f7fa;
}

.login-card {
  width: 380px;
  border: none;
  border-radius: 12px;
  box-shadow: 0 6px 30px rgba(0, 0, 0, 0.06) !important;
}

.login-card :deep(.el-card__body) { padding: 32px 30px; }

.card-title {
  font-size: 22px;
  font-weight: 600;
  color: #1f2937;
}

.card-sub {
  font-size: 13px;
  color: #9ca3af;
  margin-bottom: 24px;
}

.submit-btn {
  width: 100%;
  height: 42px;
  font-size: 15px;
  background: linear-gradient(135deg, #4a7dff, #6a5cff);
  border: none;
}

.demo-tip {
  text-align: center;
  font-size: 12px;
  color: #9ca3af;
  margin-top: 6px;
}

@media (max-width: 900px) {
  .login-hero { display: none; }
}
</style>
