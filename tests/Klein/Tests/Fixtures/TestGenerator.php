<?php

declare(strict_types=1);

namespace Klein\Tests\Fixtures;

use Closure;
use Random\RandomException;
use RuntimeException;

final class TestGenerator
{
    public static function generatePaths(
        int $count,
        int $maxSegments,
        int $wildcardCount,
        int $collisionLevel = 2,
        int $commonBase = 0,
        int $minSubPaths = 0
    ): array {
        try {
            // Pre-generate base paths (Ex: 50% of total, rounded up)
            $baseCount = (int)ceil($count / $collisionLevel);
            $basePaths = [];
            for ($i = 0; $i < $baseCount; $i++) {
                $numBaseSegments = min($commonBase, $maxSegments) ?: random_int(1, max(1, $maxSegments - 1));
                $segments = [];
                for ($j = 0; $j < $numBaseSegments; $j++) {
                    $segments[] = self::randomSegment();
                }
                $basePaths[] = '/' . implode('/', $segments);
            }

            $results = [];
            $unique = [];
            while (count($results) < $count) {
                $base = $basePaths[array_rand($basePaths)];
                $baseSegments = explode('/', trim($base, '/'));

                // Generate extra segments
                $maxExtra = max($maxSegments - count($baseSegments), 0);
                $extraCount = random_int(min($minSubPaths, $maxExtra), $maxExtra);
                $extras = [];
                for ($j = 0; $j < $extraCount; $j++) {
                    $extras[] = self::randomSegment();
                }

                // Insert wildcards in correct order
                $mergedExtras = $extras;
                for ($i = 0; $i < $wildcardCount; $i++) {
                    $pos = random_int(0, count($mergedExtras));
                    array_splice($mergedExtras, $pos, 0, ["[:element$i]"]);
                }

                // Merge segments
                $allSegments = array_merge($baseSegments, $mergedExtras);
                $wildcardPath = '/' . implode('/', $allSegments);

                // Generate replacements and replace wildcards
                $replacements = array_map(fn() => substr(bin2hex(random_bytes(2)), 0, 4), range(1, $wildcardCount));
                $newPath = self::replaceWildcards($wildcardPath, $replacements);

                // Ensure uniqueness
                if (!isset($unique[$wildcardPath])) {
                    $results[] = [
                        'path' => $newPath,
                        'wildcard_path' => $wildcardPath,
                        'result' => implode('', $replacements),
                    ];
                    $unique[$wildcardPath] = true;
                }
            }

            sort($results);
            return $results;
        } catch (RandomException $e) {
            throw new RuntimeException($e->getMessage(), 0, $e);
        }
    }

    public static function replaceWildcards(string $wildcardPath, array $replacements): string
    {
        $segments = explode('/', trim($wildcardPath, '/'));
        $replacementIndex = 0;

        foreach ($segments as &$segment) {
            if ($segment === '**' && isset($replacements[$replacementIndex])) {
                $segment = $replacements[$replacementIndex];
                $replacementIndex++;
            }
        }

        return '/' . implode('/', $segments);
    }

    private static function randomSegment(): string
    {
        /** @noinspection SpellCheckingInspection */
        return substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), 0, random_int(1, 12));
    }

    /**
     * @param array $tests
     * @param Closure|null $closure
     * @return ClosureTestClass[]
     */
    public static function generateClosureTests(
        array $tests,
        ?Closure $closure = null
    ): array {
        return array_map(static function ($data) use ($closure) {
            $path = $data['path'];
            $closure = $data['closure'] ?? $closure ?? static function () use ($path): string {
                return $path;
            };
            return new ClosureTestClass(
                $data['path'],
                $data['result'],
                $data['wildcard_path'],
                $closure,
            );
        }, $tests);
    }
}
