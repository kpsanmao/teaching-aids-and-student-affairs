import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import NProgress from 'nprogress'
import 'nprogress/nprogress.css'

NProgress.configure({ showSpinner: false })

const routes: RouteRecordRaw[] = [
  {
    path: '/login',
    name: 'login',
    component: () => import('@/views/auth/LoginView.vue'),
    meta: { public: true, title: '登录' },
  },
  {
    path: '/',
    component: () => import('@/components/layout/AppLayout.vue'),
    redirect: '/dashboard',
    children: [
      {
        path: 'dashboard',
        name: 'dashboard',
        component: () => import('@/views/dashboard/DashboardView.vue'),
        meta: { title: '工作台', icon: 'House' },
      },
      {
        path: 'students',
        name: 'students',
        component: () => import('@/views/students/StudentListView.vue'),
        meta: { title: '学生管理', icon: 'UserFilled' },
      },
      {
        path: 'courses',
        name: 'courses',
        component: () => import('@/views/courses/CourseListView.vue'),
        meta: { title: '课程管理', icon: 'Collection' },
      },
      {
        path: 'courses/:id',
        name: 'course-detail',
        component: () => import('@/views/courses/CourseDetailView.vue'),
        meta: { title: '课程详情', hidden: true },
      },
      {
        path: 'lesson-plans',
        name: 'lesson-plans',
        component: () => import('@/views/lesson-plans/LessonPlanListView.vue'),
        meta: { title: '教案管理', icon: 'Document' },
      },
      {
        path: 'sessions',
        name: 'sessions',
        component: () => import('@/views/sessions/SessionListView.vue'),
        meta: { title: '课次与考勤', icon: 'Calendar' },
      },
      {
        path: 'assignments',
        name: 'assignments',
        component: () => import('@/views/assignments/AssignmentListView.vue'),
        meta: { title: '作业管理', icon: 'EditPen' },
      },
      {
        path: 'grades',
        name: 'grades',
        component: () => import('@/views/grades/GradeListView.vue'),
        meta: { title: '成绩管理', icon: 'DataLine' },
      },
      {
        path: 'alerts',
        name: 'alerts',
        component: () => import('@/views/alerts/AlertListView.vue'),
        meta: { title: '预警中心', icon: 'Warning' },
      },
      {
        path: 'analytics',
        name: 'analytics',
        component: () => import('@/views/analytics/AnalyticsView.vue'),
        meta: { title: '学情分析', icon: 'TrendCharts' },
      },
      {
        path: 'reports',
        name: 'reports',
        component: () => import('@/views/reports/ReportListView.vue'),
        meta: { title: '学情报告', icon: 'Document' },
      },
    ],
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('@/views/NotFoundView.vue'),
    meta: { public: true, title: '404' },
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior: () => ({ top: 0 }),
})

router.beforeEach((to, _from, next) => {
  NProgress.start()
  if (to.meta?.title) {
    document.title = `${to.meta.title as string} · ${import.meta.env.VITE_APP_NAME ?? ''}`
  }
  next()
})

router.afterEach(() => {
  NProgress.done()
})

export default router
