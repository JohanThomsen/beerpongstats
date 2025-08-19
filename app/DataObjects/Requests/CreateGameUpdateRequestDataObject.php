<?php

namespace App\DataObjects\Requests;

use App\Enums\GameUpdateType;
use Illuminate\Validation\Rules\Enum as EnumRule;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Between;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class CreateGameUpdateRequestDataObject extends Data
{
    public function __construct(
        #[Nullable, Exists('users', 'id')]
        public ?int $user_id,

        // We'll validate via static rules to leverage Laravel's Enum rule
        public GameUpdateType|string $type,

        #[Nullable, ArrayType]
        public ?array $self_cup_positions,

        #[Nullable, ArrayType]
        public ?array $opponent_cup_positions,

        #[Required, IntegerType, Between(0,10)]
        public int $self_cups_left,

        #[Required, IntegerType, Between(0,10)]
        public int $opponent_cups_left,

        #[Nullable, IntegerType, Between(1,10)]
        public ?int $affected_cup,
    ) {
    }

    public static function rules(): array
    {
        return [
            'type' => [new EnumRule(GameUpdateType::class)],
            'self_cup_positions.*' => ['integer', 'between:1,10'],
            'opponent_cup_positions.*' => ['integer', 'between:1,10'],
        ];
    }
}

