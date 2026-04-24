// 种子数据：90 名学生 / 4 门课程 / 每课 16 次课 / 考勤 / 作业 / 预警 / 教案 / 报告
// 保持与后端 DemoStudentSeeder + DemoCourseSeeder 一致的规模

import type {
  MockAlert,
  MockAssignment,
  MockAssignmentScore,
  MockAssignmentSuggestion,
  MockAttendance,
  MockCourse,
  MockCourseSession,
  MockLessonPlan,
  MockReport,
  MockStudent,
  MockTeacher,
} from './types'

// ---- 固定随机数发生器（保证每次刷新数据一致） ----
function mulberry32(seed: number) {
  let t = seed
  return () => {
    t = (t + 0x6d2b79f5) | 0
    let x = Math.imul(t ^ (t >>> 15), 1 | t)
    x ^= x + Math.imul(x ^ (x >>> 7), 61 | x)
    return ((x ^ (x >>> 14)) >>> 0) / 4294967296
  }
}
const rand = mulberry32(20260421)
const rInt = (min: number, max: number) => Math.floor(rand() * (max - min + 1)) + min
const pick = <T>(list: T[]): T => list[Math.floor(rand() * list.length)]

// ---- 教师 ----
export const teachers: MockTeacher[] = [
  { id: 1, name: '系统管理员', email: 'admin@lncu.cn', role: 'admin' },
  { id: 2, name: '示例教师', email: 'teacher@lncu.cn', role: 'teacher' },
]

// ---- 学生 ----
const surnames = ['李', '王', '张', '刘', '陈', '杨', '赵', '黄', '周', '吴', '徐', '孙', '胡', '朱', '高', '林', '何', '郭', '马', '罗']
const givens = [
  '子轩', '浩然', '雨桐', '欣怡', '若曦', '诗涵', '宇航', '梓萱', '博文', '思源',
  '天赐', '梦琪', '俊杰', '雅静', '佳怡', '志远', '涵茜', '梓睿', '子豪', '紫涵',
  '慕辰', '沐阳', '语嫣', '皓轩', '可馨', '晨曦', '嘉诚', '若萱', '一鸣', '文博',
]
const classList = ['计算机 25-1 班', '计算机 25-2 班', '软件 25-1 班']

export const students: MockStudent[] = Array.from({ length: 90 }, (_, i) => {
  const cls = classList[Math.floor(i / 30)]
  return {
    id: i + 1,
    student_no: `2025${String(i + 1).padStart(4, '0')}`,
    name: pick(surnames) + pick(givens),
    class_name: cls,
  }
})

// ---- 课程 ----
export const courses: MockCourse[] = [
  {
    id: 1,
    user_id: 2,
    name: '数据结构与算法',
    credit: 4,
    course_type: '必修课',
    semester: '2025-2026 春',
    semester_start: '2026-02-23',
    semester_end: '2026-06-26',
    weekly_days: [1, 3],
    cover_color: '#4a7dff',
    students_count: 60,
    sessions_count: 32,
  },
  {
    id: 2,
    user_id: 2,
    name: '计算机网络',
    credit: 3,
    course_type: '必修课',
    semester: '2025-2026 春',
    semester_start: '2026-02-24',
    semester_end: '2026-06-27',
    weekly_days: [2, 4],
    cover_color: '#00b4a6',
    students_count: 45,
    sessions_count: 32,
  },
  {
    id: 3,
    user_id: 2,
    name: '数据库原理',
    credit: 3,
    course_type: '必修课',
    semester: '2025-2026 春',
    semester_start: '2026-02-25',
    semester_end: '2026-06-25',
    weekly_days: [3, 5],
    cover_color: '#f57c00',
    students_count: 60,
    sessions_count: 32,
  },
  {
    id: 4,
    user_id: 2,
    name: '人工智能导论',
    credit: 2,
    course_type: '选修课',
    semester: '2025-2026 春',
    semester_start: '2026-02-27',
    semester_end: '2026-06-19',
    weekly_days: [5],
    cover_color: '#9c27b0',
    students_count: 30,
    sessions_count: 16,
  },
]

// 课程-学生关系：课程 1&3 选了 60 人（前 60）、课程 2 选了 45 人、课程 4 选了 30 人（后 30）
export const courseStudentMap: Record<number, number[]> = {
  1: students.slice(0, 60).map((s) => s.id),
  2: students.slice(0, 45).map((s) => s.id),
  3: students.slice(0, 60).map((s) => s.id),
  4: students.slice(60).map((s) => s.id),
}

// ---- 课次 ----
function addDays(date: string, days: number) {
  const d = new Date(date)
  d.setDate(d.getDate() + days)
  return d.toISOString().slice(0, 10)
}
function dayOfWeek(date: string) {
  return new Date(date).getDay() || 7
}

const timeSlots = ['1-2 节（08:00-09:50）', '3-4 节（10:10-12:00）', '5-6 节（14:00-15:50）', '7-8 节（16:10-18:00）']
const locations = ['理科楼 A301', '逸夫楼 B205', '信息楼 C102', '实验楼 D401']

export const sessions: MockCourseSession[] = []
let sessionAutoId = 1
const today = '2026-04-21'

courses.forEach((c) => {
  let date = c.semester_start
  let idx = 1
  const slot = pick(timeSlots)
  const room = pick(locations)
  while (date <= c.semester_end && idx <= c.sessions_count) {
    if (c.weekly_days.includes(dayOfWeek(date))) {
      const topics = [
        '课程概述与绪论',
        '基础概念与术语',
        '核心数据结构',
        '算法分析',
        '典型案例讲解',
        '实验与编程练习',
        '进阶专题',
        '期中复习',
        '应用场景分析',
        '难点答疑',
        '综合练习',
        '项目实践',
        '案例研讨',
        '前沿拓展',
        '期末复习',
        '结课答辩',
      ]
      const status: MockCourseSession['status'] =
        date < today ? 'done' : date === today ? 'scheduled' : 'scheduled'
      sessions.push({
        id: sessionAutoId++,
        course_id: c.id,
        session_index: idx,
        date,
        time_slot: slot,
        location: room,
        status,
        topic: topics[(idx - 1) % topics.length],
      })
      idx++
    }
    date = addDays(date, 1)
  }
})

// ---- 考勤 ----
export const attendances: MockAttendance[] = []
let attendanceId = 1
sessions.forEach((s) => {
  if (s.status !== 'done') return
  const studentIds = courseStudentMap[s.course_id] || []
  studentIds.forEach((sid) => {
    const r = rand()
    let status: MockAttendance['status'] = 'present'
    if (r < 0.04) status = 'absent'
    else if (r < 0.09) status = 'late'
    else if (r < 0.12) status = 'leave'
    attendances.push({
      id: attendanceId++,
      session_id: s.id,
      student_id: sid,
      status,
      note: status === 'leave' ? '病假 / 事假' : undefined,
    })
  })
})

// ---- 作业 ----
export const assignments: MockAssignment[] = [
  {
    id: 1,
    course_id: 1,
    title: '线性表实现与分析',
    description: '使用数组与链表分别实现顺序栈与链栈，比较时间/空间复杂度，提交代码与实验报告。',
    type: '实验',
    total_score: 100,
    deadline: '2026-04-18 23:59',
    published_at: '2026-04-05',
    status: 'closed',
    submitted: 58,
    total: 60,
  },
  {
    id: 2,
    course_id: 1,
    title: '二叉树遍历练习',
    description: '基于课堂示例，分别实现递归与迭代版前中后序遍历，编写单元测试并提交。',
    type: '作业',
    total_score: 100,
    deadline: '2026-04-28 23:59',
    published_at: '2026-04-15',
    status: 'open',
    submitted: 32,
    total: 60,
  },
  {
    id: 3,
    course_id: 2,
    title: 'TCP 三次握手抓包分析',
    description: '使用 Wireshark 抓取并解析 TCP 三次握手过程，提交抓包文件与分析报告。',
    type: '实验',
    total_score: 100,
    deadline: '2026-05-05 23:59',
    published_at: '2026-04-20',
    status: 'open',
    submitted: 8,
    total: 45,
  },
  {
    id: 4,
    course_id: 3,
    title: 'SQL 基础查询练习',
    description: '基于教材第 3 章数据，完成 20 条 SQL 语句（SELECT / JOIN / 子查询 / 聚合）。',
    type: '作业',
    total_score: 100,
    deadline: '2026-04-14 23:59',
    published_at: '2026-04-01',
    status: 'closed',
    submitted: 59,
    total: 60,
  },
  {
    id: 5,
    course_id: 3,
    title: 'ER 图设计与范式分析',
    description: '为给定业务场景绘制 ER 图并转换为关系模式，分析到 3NF 的规范化过程。',
    type: '项目',
    total_score: 100,
    deadline: '2026-05-10 23:59',
    published_at: '2026-04-17',
    status: 'open',
    submitted: 12,
    total: 60,
  },
  {
    id: 6,
    course_id: 4,
    title: 'AI 伦理小论文',
    description: '就 AI 发展与社会责任任选一个子话题，撰写不少于 1500 字的小论文。',
    type: '作业',
    total_score: 100,
    deadline: '2026-05-15 23:59',
    published_at: '2026-04-10',
    status: 'open',
    submitted: 18,
    total: 30,
  },
]

// ---- 作业成绩 ----
export const assignmentScores: MockAssignmentScore[] = []
let scoreId = 1
assignments.forEach((a) => {
  const studentIds = courseStudentMap[a.course_id] || []
  studentIds.forEach((sid, idx) => {
    const r = rand()
    let score: number | null = null
    let submitted: string | null = null
    if (a.status === 'closed' || idx < a.submitted) {
      // 70~98 的正态近似分布
      const base = 85 + (rand() - 0.5) * 20
      score = Math.max(50, Math.min(100, Math.round(base)))
      submitted = a.published_at
      if (r < 0.03) score = null // 3% 缺交
    }
    assignmentScores.push({
      id: scoreId++,
      assignment_id: a.id,
      student_id: sid,
      score,
      submitted_at: submitted,
      feedback: score && score < 70 ? '基础薄弱，建议回顾 2.3 节概念。' : null,
    })
  })
})

// ---- 预警 ----
const alertTemplates = [
  { type: 'absence', level: 'danger', title: '缺勤累计预警', detail: '近 4 次课程缺勤 2 次，出勤率 50%，低于 80% 阈值。' },
  { type: 'absence', level: 'warning', title: '迟到累计提醒', detail: '本学期累计迟到 3 次，请关注学习态度。' },
  { type: 'grade_low', level: 'warning', title: '作业成绩偏低', detail: '近 1 次作业成绩 58 分，低于班级平均 84 分。' },
  { type: 'grade_decline', level: 'warning', title: '成绩明显下滑', detail: '连续 2 次作业得分较上一次下降 > 15 分（92 → 75 → 58）。' },
  { type: 'assignment_miss', level: 'danger', title: '作业逾期未交', detail: '《二叉树遍历练习》已截止，未检测到提交记录。' },
  { type: 'grade_low', level: 'info', title: '实验报告待完善', detail: '本次实验报告得分 78，教师已留言：缺少复杂度分析部分。' },
]

export const alerts: MockAlert[] = []
let alertId = 1
// 精选 20 条预警
for (let i = 0; i < 20; i++) {
  const t = alertTemplates[i % alertTemplates.length]
  const stu = students[rInt(0, students.length - 1)]
  const cid = pick([1, 2, 3, 4])
  alerts.push({
    id: alertId++,
    student_id: stu.id,
    course_id: cid,
    type: t.type as MockAlert['type'],
    level: t.level as MockAlert['level'],
    status: rand() < 0.5 ? 'unread' : rand() < 0.5 ? 'read' : 'resolved',
    title: t.title,
    detail: t.detail.replace('近', stu.name + ' 近'),
    created_at: addDays('2026-04-10', rInt(0, 10)) + ' ' + String(rInt(8, 20)).padStart(2, '0') + ':' + String(rInt(0, 59)).padStart(2, '0'),
  })
}

// ---- 教案 ----
export const lessonPlans: MockLessonPlan[] = [
  {
    id: 1,
    course_id: 1,
    title: '第 5 章：树与二叉树',
    chapter_no: '第 5 章',
    status: 'ready',
    uploaded_at: '2026-04-12 10:25',
    uploader: '示例教师',
    file_size_kb: 328,
    sections: [
      { id: 1, plan_id: 1, parent_id: null, title: '5.1 树的基本概念', summary: '介绍树、节点、父子、度、深度、高度等基础术语。', objectives: ['理解树的定义', '能识别基础术语'], keywords: ['树', '节点', '度'], estimate_minutes: 20, order: 1, level: 1 },
      { id: 2, plan_id: 1, parent_id: null, title: '5.2 二叉树', summary: '二叉树定义、性质、存储结构（顺序与链式）。', objectives: ['掌握二叉树性质', '能画出二叉树链式存储'], keywords: ['二叉树', '性质', '存储'], estimate_minutes: 45, order: 2, level: 1 },
      { id: 3, plan_id: 1, parent_id: 2, title: '5.2.1 满二叉树与完全二叉树', summary: '讲解两种特殊形态及其下标关系。', objectives: ['区分满/完全二叉树', '掌握下标公式'], keywords: ['满二叉树', '完全二叉树'], estimate_minutes: 15, order: 3, level: 2 },
      { id: 4, plan_id: 1, parent_id: null, title: '5.3 遍历算法', summary: '前序/中序/后序/层序遍历，递归与迭代实现。', objectives: ['能手写 4 种遍历', '理解迭代与递归转换'], keywords: ['遍历', '递归', '栈'], estimate_minutes: 60, order: 4, level: 1 },
      { id: 5, plan_id: 1, parent_id: null, title: '5.4 线索二叉树', summary: '为空指针利用而设计的线索化。', objectives: ['理解线索化动机', '能绘制线索二叉树'], keywords: ['线索', '前驱', '后继'], estimate_minutes: 30, order: 5, level: 1 },
      { id: 6, plan_id: 1, parent_id: null, title: '5.5 哈夫曼树与编码', summary: '构造哈夫曼树，生成最优前缀码。', objectives: ['能手算哈夫曼树', '编写贪心代码'], keywords: ['哈夫曼', '贪心', '前缀码'], estimate_minutes: 30, order: 6, level: 1 },
    ],
  },
  {
    id: 2,
    course_id: 2,
    title: '第 3 章：数据链路层',
    chapter_no: '第 3 章',
    status: 'ready',
    uploaded_at: '2026-04-08 14:10',
    uploader: '示例教师',
    file_size_kb: 412,
    sections: [
      { id: 7, plan_id: 2, parent_id: null, title: '3.1 概述与基本问题', summary: '链路层的封装成帧、透明传输、差错控制。', objectives: ['理解帧结构', '掌握 3 个基本问题'], keywords: ['成帧', '透明传输'], estimate_minutes: 25, order: 1, level: 1 },
      { id: 8, plan_id: 2, parent_id: null, title: '3.2 点对点协议 PPP', summary: '家用拨号与宽带常用协议。', objectives: ['说出 PPP 帧格式', 'LCP 与 NCP 的作用'], keywords: ['PPP', 'LCP', 'NCP'], estimate_minutes: 30, order: 2, level: 1 },
      { id: 9, plan_id: 2, parent_id: null, title: '3.3 以太网', summary: 'CSMA/CD、MAC 地址、帧格式、交换机原理。', objectives: ['掌握以太网帧', '理解交换机学习表'], keywords: ['以太网', 'MAC', '交换机'], estimate_minutes: 60, order: 3, level: 1 },
    ],
  },
  {
    id: 3,
    course_id: 3,
    title: '第 6 章：关系数据库规范化',
    chapter_no: '第 6 章',
    status: 'parsing',
    uploaded_at: '2026-04-20 16:30',
    uploader: '示例教师',
    file_size_kb: 276,
    sections: [],
  },
]

// ---- AI 作业建议（供作业详情页流式展示） ----
export const assignmentSuggestions: MockAssignmentSuggestion[] = [
  {
    id: 1,
    assignment_id: 2,
    title: '基础巩固：递归实现前序遍历',
    difficulty: '基础',
    estimate_minutes: 30,
    objectives: ['强化递归思想', '掌握函数调用栈'],
    content: '给定二叉树节点定义，编写 preorder(root: TreeNode): number[] 方法，使用递归实现。要求：(1) 处理空树；(2) 使用数组收集结果；(3) 编写至少 3 组测试用例。',
    reference: ['教材 5.3.1', 'LeetCode 144'],
    status: 'pending',
  },
  {
    id: 2,
    assignment_id: 2,
    title: '进阶挑战：迭代版中序遍历 + 空间优化',
    difficulty: '进阶',
    estimate_minutes: 60,
    objectives: ['理解显式栈模拟', '掌握 Morris 遍历 O(1) 空间'],
    content: '分别实现基于显式栈（O(h) 空间）与 Morris 遍历（O(1) 空间）的中序遍历，比较二者的时间复杂度与适用场景。',
    reference: ['教材 5.3.2', 'LeetCode 94', 'Morris, 1979'],
    status: 'pending',
  },
  {
    id: 3,
    assignment_id: 2,
    title: '综合实践：表达式树求值',
    difficulty: '挑战',
    estimate_minutes: 90,
    objectives: ['将前缀/后缀表达式转换为表达式树', '综合运用树遍历求值'],
    content: '给定后缀表达式如 "3 4 + 2 *"，构建表达式树，并通过后序遍历求值。请在实验报告中讨论与表达式栈求值算法相比的优劣。',
    reference: ['教材 5.3.4', '算法导论 12 章'],
    status: 'pending',
  },
]

// ---- 学情报告 ----
export const reports: MockReport[] = [
  {
    id: 1,
    course_id: 1,
    title: '数据结构与算法 · 期中学情报告',
    generated_at: '2026-04-15 18:22',
    period: '2026-02-23 ~ 2026-04-15',
    summary: '整体出勤率 92.1%，平均分 83.5，较上一次提升 4.2 分。重点关注 3 名成绩下滑学生。',
    status: 'ready',
    pages: 14,
  },
  {
    id: 2,
    course_id: 3,
    title: '数据库原理 · 月度学情报告',
    generated_at: '2026-04-10 09:05',
    period: '2026-03-10 ~ 2026-04-10',
    summary: 'SQL 基础作业班级均分 84，但 ER 图作业整体偏弱，建议下阶段针对性讲解。',
    status: 'ready',
    pages: 11,
  },
  {
    id: 3,
    course_id: 2,
    title: '计算机网络 · 即将生成',
    generated_at: '',
    period: '2026-04-01 ~ 2026-04-21',
    summary: '',
    status: 'generating',
    pages: 0,
  },
]

// ---- 便捷查询函数 ----
export const getStudentById = (id: number) => students.find((s) => s.id === id)
export const getCourseById = (id: number) => courses.find((c) => c.id === id)
export const getSessionsByCourse = (cid: number) =>
  sessions.filter((s) => s.course_id === cid).sort((a, b) => a.session_index - b.session_index)
export const getAssignmentsByCourse = (cid: number) =>
  assignments.filter((a) => a.course_id === cid)
export const getAlertsByStatus = (status?: MockAlert['status']) =>
  status ? alerts.filter((a) => a.status === status) : alerts
export const getSuggestionsByAssignment = (aid: number) =>
  assignmentSuggestions.filter((s) => s.assignment_id === aid)
export const getCourseStudents = (cid: number): MockStudent[] => {
  const ids = courseStudentMap[cid] || []
  return ids.map((id) => students.find((s) => s.id === id)!).filter(Boolean)
}

export const currentTeacher = teachers[1]
