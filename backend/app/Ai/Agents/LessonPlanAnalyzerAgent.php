<?php

namespace App\Ai\Agents;

use App\Models\LessonPlan;
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
 * 教案（Word .docx）结构化分析：把预抽取后的段落交给模型，返回课次化的章节清单。
 * 结构化输出结果由 AnalyzeLessonPlanJob 落库到 lesson_plan_sections。
 */
#[Provider(Lab::Anthropic)]
#[Temperature(0.2)]
#[MaxTokens(4096)]
#[Timeout(120)]
class LessonPlanAnalyzerAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function __construct(public LessonPlan $lessonPlan) {}

    public function instructions(): Stringable|string
    {
        return <<<'TXT'
        你是一名资深教学设计师。请根据输入的教案（Word 预解析后的段落），
        将其结构化为若干"课次章节（section）"，用于后续排课：
        - 每个 section 对应一次课的主教学内容
        - 抽取 3-6 个知识点标签（knowledge_tags）
        - 给出建议的起止课次（suggested_session_start/end，基于整体课时数）
        严禁编造原文未出现的内容。
        TXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'sections' => $schema->array()
                ->items(
                    $schema->object(fn ($s) => [
                        'seq' => $s->integer()->min(1)->required(),
                        'title' => $s->string()->required(),
                        'summary' => $s->string()->required(),
                        'knowledge_tags' => $s->array()
                            ->items($s->string())
                            ->required(),
                        'suggested_session_start' => $s->integer()->min(1)->required(),
                        'suggested_session_end' => $s->integer()->min(1)->required(),
                    ])
                )
                ->required(),
        ];
    }
}
