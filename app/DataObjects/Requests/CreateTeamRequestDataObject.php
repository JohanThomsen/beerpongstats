<?php

namespace App\DataObjects\Requests;

use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Size;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class CreateTeamRequestDataObject extends Data
{
    public function __construct(
        #[Required, StringType, Max(255), Unique('teams')]
        public string $name,

        #[Required, ArrayType, Size(2)]
        public array $user_ids,
    ) {
    }

    public static function rules(): array
    {
        return [
            'user_ids.*' => ['exists:users,id', 'distinct'],
        ];
    }
}
