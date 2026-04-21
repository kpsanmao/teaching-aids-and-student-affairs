<?php

namespace App\Services;

/**
 * 使用 PhpOffice\PhpWord 解析 .docx 教案，抽取结构化段落
 * 输出：[
 *     ['level' => 1, 'title' => '...', 'content' => '...', 'seq' => 1],
 *     ...
 * ]
 */
class WordStructureExtractor
{
    public function extract(string $absolutePath): array
    {
        throw new \LogicException('Not implemented.');
    }

    public function extractPlainText(string $absolutePath): string
    {
        throw new \LogicException('Not implemented.');
    }
}
