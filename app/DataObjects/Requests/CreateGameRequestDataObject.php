<?php

namespace App\DataObjects\Requests;

use App\Enums\GameType;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\RequiredIf;
use Spatie\LaravelData\Attributes\Validation\Size;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Illuminate\Validation\Rules\Enum as EnumRule;

#[TypeScript]
class CreateGameRequestDataObject extends Data
{
    public function __construct(
        #[Required]
        public bool $is_solo,

        #[Required]
        public string $type,

        // For solo games: array of 2 user IDs
        #[RequiredIf('is_solo', true), ArrayType]
        public array $user_ids = [],

        // For team games: array of 2 team IDs
        #[RequiredIf('is_solo', false), ArrayType]
        public array $team_ids = [],
    ) {
    }

    public static function rules(): array
    {
        return [
            'is_solo' => ['required', 'boolean'],
            'type' => ['required', 'in:SIX_CUP,TEN_CUP'],
            // When is_solo is true, validate user_ids; otherwise exclude it from validation
            'user_ids' => ['exclude_if:is_solo,false', 'required_if:is_solo,true', 'array', 'size:2'],
            'user_ids.*' => ['exclude_if:is_solo,false', 'integer', 'exists:users,id', 'distinct'],
            // When is_solo is false, validate team_ids; otherwise exclude it from validation
            'team_ids' => ['exclude_if:is_solo,true', 'required_if:is_solo,false', 'array', 'size:2'],
            'team_ids.*' => ['exclude_if:is_solo,true', 'integer', 'exists:teams,id', 'distinct'],
        ];
    }
}
