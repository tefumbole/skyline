<?php

namespace App\Support;

class SystemTestScore
{
    public static function summarize($items)
    {
        if (! is_array($items)) {
            $items = [];
        }

        $total = 0;
        $passed = 0;
        $failed = 0;
        $issues = [];
        $comments = [];
        $open = [];

        foreach (TestingManual::sections() as $index => $section) {
            $stage = $index + 1;
            foreach ($section['items'] as $item) {
                $total++;
                $key = $item['key'];
                $row = isset($items[$key]) && is_array($items[$key]) ? $items[$key] : [];
                $result = isset($row['result']) ? (string) $row['result'] : '';
                $note = isset($row['note']) ? trim((string) $row['note']) : '';
                $label = 'Stage '.$stage.' · '.$section['title'].' — '.$item['title'];
                if ($result === 'pass') {
                    $passed++;
                } elseif ($result === 'fail') {
                    $failed++;
                    $issues[] = ['label' => $label, 'note' => $note, 'stage' => $stage];
                } else {
                    $open[] = ['label' => $label, 'stage' => $stage, 'title' => $section['title']];
                }
                if ($note !== '') {
                    $comments[] = ['label' => $label, 'note' => $note, 'result' => $result];
                }
            }
        }

        $complete = $total > 0 && count($open) === 0;
        $score = $total > 0 ? (int) round(($passed / $total) * 100) : 0;

        return [
            'total' => $total,
            'passed' => $passed,
            'failed' => $failed,
            'answered' => $passed + $failed,
            'complete' => $complete,
            'score' => $score,
            'issues' => $issues,
            'comments' => $comments,
            'open' => $open,
        ];
    }

    public static function message($name, array $summary)
    {
        $lines = [];
        $lines[] = 'Skyline Hotel site test';
        $lines[] = 'Name: '.$name;
        $lines[] = 'Site score: '.$summary['score'].'% ('.$summary['passed'].' of '.$summary['total'].' working)';
        $lines[] = $summary['complete']
            ? 'Every check was tested.'
            : count($summary['open']).' checks are still open, so this is not the final score.';
        $lines[] = '';
        $lines[] = 'What is wrong';
        if (count($summary['issues']) === 0) {
            $lines[] = 'Nothing was marked as an issue.';
        }
        foreach ($summary['issues'] as $issue) {
            $lines[] = '- '.$issue['label'];
            if ($issue['note'] !== '') {
                $lines[] = '  '.$issue['note'];
            }
        }
        $lines[] = '';
        $lines[] = 'All comments';
        if (count($summary['comments']) === 0) {
            $lines[] = 'No comments.';
        }
        foreach ($summary['comments'] as $comment) {
            $lines[] = '- '.$comment['label'].': '.$comment['note'];
        }
        $text = implode("\n", $lines);
        if (mb_strlen($text) > 3500) {
            $text = mb_substr($text, 0, 3400)."\n\nThe full report is in Help, under Site Test Results.";
        }

        return $text;
    }
}
