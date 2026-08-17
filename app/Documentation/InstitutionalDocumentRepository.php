<?php

namespace App\Documentation;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Finder\SplFileInfo;

final class InstitutionalDocumentRepository
{
    /**
     * @return list<array{title: string, key: string, documents: list<array{key: string, title: string, href: string}>}>
     */
    public function navigation(): array
    {
        $groups = [];

        foreach ($this->files() as $file) {
            $key = $this->key($file);
            $category = Str::before($key, '/');

            if ($category === $key) {
                $category = 'institution';
            }

            $groups[$category][] = [
                'key' => $key,
                'title' => $this->title($file),
                'href' => route('institutional-documents.show', ['document' => $key]),
            ];
        }

        ksort($groups);

        return array_map(
            static function (array $documents, string $category): array {
                usort(
                    $documents,
                    static fn (array $left, array $right): int => strnatcasecmp($left['title'], $right['title']),
                );

                return [
                    'title' => $category === 'adr' ? 'ADRs' : Str::headline($category),
                    'key' => $category,
                    'documents' => $documents,
                ];
            },
            $groups,
            array_keys($groups),
        );
    }

    /**
     * @return array{key: string, title: string, source_path: string, html: string}|null
     */
    public function find(string $requestedKey): ?array
    {
        $requestedKey = Str::replaceEnd('.md', '', $requestedKey);

        foreach ($this->files() as $file) {
            $key = $this->key($file);

            if ($key !== $requestedKey) {
                continue;
            }

            $markdown = $file->getContents();

            return [
                'key' => $key,
                'title' => $this->title($file),
                'source_path' => 'docs/'.$file->getRelativePathname(),
                'html' => Str::markdown($markdown, [
                    'html_input' => 'strip',
                    'allow_unsafe_links' => false,
                ]),
            ];
        }

        return null;
    }

    /**
     * @return list<SplFileInfo>
     */
    private function files(): array
    {
        return array_values(array_filter(
            File::allFiles(base_path('docs')),
            static fn (SplFileInfo $file): bool => $file->getExtension() === 'md',
        ));
    }

    private function key(SplFileInfo $file): string
    {
        return Str::beforeLast($file->getRelativePathname(), '.md');
    }

    private function title(SplFileInfo $file): string
    {
        $heading = Str::of($file->getContents())->match('/^#\s+(.+)$/m')->trim();

        return $heading->isNotEmpty()
            ? $heading->toString()
            : Str::headline($file->getFilenameWithoutExtension());
    }
}
