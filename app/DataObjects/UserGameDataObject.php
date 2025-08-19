<?php

namespace App\DataObjects;

use App\Enums\GameResult;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class UserGameDataObject extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public ?GameResult $result,
        public ?int $cupsLeft,
    ){

    }
}
