<?php

namespace App\DataObjects\Requests;

use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class PaginationRequestDataObject extends Data
{
    public function __construct(
        #[IntegerType, Min(1)]
        public int $page = 1,

        #[IntegerType, Min(1)]
        public int $perPage = 15,
    ) {
    }
}
