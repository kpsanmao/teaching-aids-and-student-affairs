// Mock 数据类型定义（仅供前端展示使用，字段与后端设计保持一致）

export interface MockTeacher {
  id: number
  name: string
  email: string
  role: 'admin' | 'teacher'
  avatar?: string
}

export interface MockStudent {
  id: number
  student_no: string
  name: string
  class_name: string
}

export interface MockCourse {
  id: number
  user_id: number
  name: string
  credit: number
  course_type: '必修课' | '选修课' | '实验课'
  semester: string
  semester_start: string
  semester_end: string
  weekly_days: number[]
  cover_color: string
  students_count: number
  sessions_count: number
}

export type SessionStatus = 'scheduled' | 'done' | 'canceled' | 'makeup'

export interface MockCourseSession {
  id: number
  course_id: number
  session_index: number
  date: string
  time_slot: string
  location: string
  status: SessionStatus
  topic?: string
}

export type AttendanceStatus = 'present' | 'late' | 'absent' | 'leave'

export interface MockAttendance {
  id: number
  session_id: number
  student_id: number
  status: AttendanceStatus
  note?: string
}

export interface MockAssignment {
  id: number
  course_id: number
  title: string
  description: string
  type: '作业' | '实验' | '项目' | '测验'
  total_score: number
  deadline: string
  published_at: string
  status: 'open' | 'closed'
  submitted: number
  total: number
}

export interface MockAssignmentScore {
  id: number
  assignment_id: number
  student_id: number
  score: number | null
  submitted_at?: string | null
  feedback?: string | null
}

export interface MockAssignmentSuggestion {
  id: number
  assignment_id: number
  title: string
  difficulty: '基础' | '进阶' | '挑战'
  estimate_minutes: number
  objectives: string[]
  content: string
  reference?: string[]
  status: 'pending' | 'accepted' | 'rejected'
}

export interface MockAlert {
  id: number
  student_id: number
  course_id: number
  type: 'absence' | 'grade_low' | 'grade_decline' | 'assignment_miss'
  level: 'info' | 'warning' | 'danger'
  status: 'unread' | 'read' | 'resolved'
  title: string
  detail: string
  created_at: string
}

export interface MockLessonPlanSection {
  id: number
  plan_id: number
  parent_id: number | null
  title: string
  summary: string
  objectives: string[]
  keywords: string[]
  estimate_minutes: number
  order: number
  level: number
}

export interface MockLessonPlan {
  id: number
  course_id: number
  title: string
  chapter_no: string
  status: 'parsing' | 'ready' | 'failed'
  uploaded_at: string
  uploader: string
  file_size_kb: number
  sections: MockLessonPlanSection[]
}

export interface MockReport {
  id: number
  course_id: number
  title: string
  generated_at: string
  period: string
  summary: string
  status: 'ready' | 'generating'
  pages: number
}
