<script setup lang="ts">
import { useRoute } from 'vue-router'
const route = useRoute()
</script>

<template>
  <el-card shadow="never" :header="`课程详情 #${route.params.id}`">
    <el-empty description="占位：课程详情、课次日历、教案、学生、作业、报告 Tab 待实现" />
  </el-card>
</template>
