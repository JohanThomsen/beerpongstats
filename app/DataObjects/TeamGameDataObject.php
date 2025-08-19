<?php

namespace App\DataObjects;

use App\Enums\GameResult;
use App\Enums\GameType;
use App\Models\User;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class TeamGameDataObject extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        /** @var $users UserGameDataObject[]|null */
        public array $users,
        public ?GameResult $result,
        public ?int $cupsLeft,
    ){

    }
}
