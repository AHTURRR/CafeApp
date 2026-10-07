<?php

namespace App\DataTransferObjects;

use App\Enums\ProductStatus;
use App\Support\PerPage;

final readonly class AdminProductFilter
{
    public function __construct(
        public ?int $categoryId = null,
        public ?ProductStatus $status = null,
        public ?string $q = null,
        public string $sort = '-created_at',
        public int $page = 1,
        public int $perPage = PerPage::DEFAULT,
    ) {
    }

    /** @param array<string, mixed> $v */
    public static function fromArray(array $v): self
    {
        $q = isset($v['q']) ? trim((string) $v['q']) : '';

        return new self(
            categoryId: isset($v['category_id']) ? (int) $v['category_id'] : null,
            status: isset($v['status']) ? ProductStatus::tryFrom((string) $v['status']) : null,
            q: $q === '' ? null : $q,
            sort: isset($v['sort']) && $v['sort'] !== '' ? (string) $v['sort'] : '-created_at',
            page: max(1, (int) ($v['page'] ?? 1)),
            perPage: PerPage::resolve($v['per_page'] ?? null),
        );
    }
}
