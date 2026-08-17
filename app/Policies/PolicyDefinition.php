<?php

namespace App\Policies;

final readonly class PolicyDefinition
{
    /**
     * @param  list<array<string, mixed>>  $versions
     */
    public function __construct(
        public string $key,
        public string $title,
        public string $owner,
        public string $approvingAuthority,
        public string $currentVersion,
        public array $versions,
    ) {}

    /**
     * @param  array<string, mixed>  $definition
     */
    public static function fromArray(array $definition): self
    {
        return new self(
            key: $definition['key'],
            title: $definition['title'],
            owner: $definition['owner'],
            approvingAuthority: $definition['approving_authority'],
            currentVersion: $definition['current_version'],
            versions: $definition['versions'],
        );
    }
}
