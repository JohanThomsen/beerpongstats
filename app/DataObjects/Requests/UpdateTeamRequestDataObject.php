<?php

namespace App\DataObjects\Requests;

use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Size;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class UpdateTeamRequestDataObject extends Data
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public string $name,

        #[Required, ArrayType, Size(2)]
        public array $user_ids,

        public ?int $teamId = null,
    ) {
    }

    public static function rules(): array
    {
        return [
            'user_ids.*' => ['exists:users,id', 'distinct'],
        ];
    }

    public static function rulesWithTeamId(int $teamId): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('teams')->ignore($teamId)],
            'user_ids' => 'required|array|size:2',
            'user_ids.*' => 'exists:users,id|distinct'
        ];
    }
}
