<?php

namespace App\Ai\Agents;

use App\Models\Course;
use App\Models\CourseSession;
use App\Models\EmbeddingsKnowledgeChunk;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Laravel\Ai\Tools\SimilaritySearch;
use Stringable;

/**
 * 作业建议 Agent：结合当前课次 + 教案知识块（RAG），给出 3~5 道作业候选。
 * 启用流式输出时返回 SSE，供前端 Vercel AI SDK UI 直接消费。
 */
#[Provider(Lab::Anthropic)]
#[Temperature(0.5)]
#[MaxSteps(6)]
#[Timeout(120)]
class AssignmentSuggesterAgent implements Agent, HasStructuredOutput, HasTools
{
    use Promptable;

    public function __construct(
        public Course $course,
        public ?CourseSession $session = null,
    ) {}

    public function instructions(): Stringable|string
    {
        return <<<'TXT'
        你是教师的作业设计助手。请根据课程、当前课次及教案知识块（来自向量检索），
        产出 3-5 道作业候选，覆盖不同难度（easy/medium/hard）。要求：
        - 贴合课次的实际教学内容（使用 similarity_search 工具检索）
        - 每题写清 title / description / estimated_minutes / knowledge_tags
        - 避免与历史作业重复
        TXT;
    }

    public function tools(): iterable
    {
        return [
            SimilaritySearch::usingModel(EmbeddingsKnowledgeChunk::class, 'embedding')
                ->withDescription('在教案知识块中按语义检索相关段落，用于设计贴合教学内容的作业。'),
        ];
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'suggestions' => $schema->array()
                ->items(
                    $schema->object(fn ($s) => [
                        'title' => $s->string()->required(),
                        'description' => $s->string()->required(),
                        'difficulty' => $s->string()->enum(['easy', 'medium', 'hard'])->required(),
                        'estimated_minutes' => $s->integer()->min(5)->max(480)->required(),
                        'knowledge_tags' => $s->array()->items($s->string())->required(),
                    ])
                )
                ->required(),
        ];
    }
}
