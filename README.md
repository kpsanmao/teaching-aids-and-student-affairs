# 高职学情管理系统

> Teaching Affairs Assistant Platform — 面向高职教师的一体化学情管理 Web 应用

## 技术栈

| 层级 | 技术 |
|------|------|
| 后端 | Laravel 13.x（PHP 8.3+）+ Laravel AI SDK |
| 前端 | Vue 3 + Vite + Element Plus |
| 数据库 | MySQL 8.0 |
| AI | laravel/ai（支持 Claude / OpenAI / Qwen 等，仅需修改 .env） |
| 容器化 | Docker + Docker Compose |

## 核心功能

- **课程管理**：课程 CRUD、学生名单导入
- **智能排课**：自动跳过节假日、生成课表视图
- **点名考勤**：快捷点名、旷课预警
- **作业管理**：AI 根据教学进度建议作业方案
- **教案分析**：上传 Word 教案，AI 自动解析课次-知识点映射
- **成绩管理**：成绩录入、公式预测、挂科预警
- **分析报表**：多维度 ECharts 图表 + PDF 报告 + AI 智能解读

## 快速开始

```bash
# 克隆项目
git clone <repo-url>
cd local.ta.lncu.cn

# 后端依赖
cd backend
composer install
cp .env.example .env
php artisan key:generate

# 配置 .env（数据库、AI Provider 等）

# 数据库迁移与填充
php artisan migrate --seed

# 发布 Laravel AI SDK 配置
php artisan vendor:publish --provider="Laravel\Ai\AiServiceProvider"
php artisan migrate

# 同步节假日
php artisan holidays:sync 2026

# 前端依赖与热更新
cd ../frontend
npm install && npm run dev
```

## 项目结构

```
├── backend/    # Laravel 13 后端（API）
└── frontend/   # Vue 3 前端（SPA）
```

## 用户角色

| 角色 | 描述 |
|------|------|
| 教师 | 主要用户，可使用全部功能 |
| 管理员 | 课程审核、汇总报表、节假日维护 |

## 开发阶段

共 8 个 Phase，计划 18 周完成，从基础框架搭建到 AI 模块集成、报表功能，最终上线。

---

*Laravel 13 + Laravel AI SDK | PHP 8.3+ | Vue 3*
