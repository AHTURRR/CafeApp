<?php

namespace App\DataTransferObjects;

use App\Enums\ProductStatus;
use App\Support\PerPage;

final readonly class CatalogFilter
{
    public function __construct(
        public ?int $categoryId = null,
        public ?string $q = null,
        public ?ProductStatus $status = null,
        public bool $popular = false,
        public int $page = 1,
        public int $perPage = PerPage::DEFAULT,
    ) {
    }

    /** @param array<string, mixed> $v hasil validasi query string */
    public static function fromArray(array $v): self
    {
        $q = isset($v['q']) ? trim((string) $v['q']) : '';

        return new self(
            categoryId: isset($v['category_id']) ? (int) $v['category_id'] : null,
            q: $q === '' ? null : $q,
            status: isset($v['status']) ? ProductStatus::tryFrom((string) $v['status']) : null,
            popular: filter_var($v['popular'] ?? false, FILTER_VALIDATE_BOOLEAN),
            page: max(1, (int) ($v['page'] ?? 1)),
            perPage: PerPage::resolve($v['per_page'] ?? null),
        );
    }
}
