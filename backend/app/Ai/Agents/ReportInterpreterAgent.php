<?php

namespace App\Ai\Agents;

use App\Models\Course;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * 学情报告解读 Agent：输入聚合后的学情指标（出勤率、成绩分布、预警等），
 * 输出教学叙事、亮点、风险、改进建议。
 */
#[Provider(Lab::Anthropic)]
#[Temperature(0.4)]
#[MaxTokens(4096)]
#[Timeout(180)]
class ReportInterpreterAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function __construct(public Course $course) {}

    public function instructions(): Stringable|string
    {
        return <<<'TXT'
        你是一名教学质量分析师。请基于输入的学情指标（JSON），从"整体表现、亮点、风险、建议"四个维度撰写一份可用于期末报告的解读：
        - narrative：300-500 字的总体教学分析
        - highlights：3-5 条亮点
        - risks：3-5 条需要关注的问题（关联学生 / 课次 / 作业）
        - recommendations：3-5 条可落地的改进建议
        避免空话套话，必须引用输入中的具体数据。
        TXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'narrative' => $schema->string()->required(),
            'highlights' => $schema->array()->items($schema->string())->required(),
            'risks' => $schema->array()->items($schema->string())->required(),
            'recommendations' => $schema->array()->items($schema->string())->required(),
        ];
    }
}
