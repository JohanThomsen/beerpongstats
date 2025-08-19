<?php

namespace App\DataObjects;

use App\Enums\GameType;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\Validation\RequiredIf;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class MatchHistoryGameDataObject extends Data
{
    public function __construct(
        public int                 $id,
        public bool                $isSolo,
        public GameType            $type,
        public bool                $isEnded,
        public string              $totalThrows,
        public string $hits,
        public string $hitRate,
        public string $edgeHits,
        public string $edgeHitRate,
        public string $misses,
        public string $missRate,
        #[RequiredIf('isSolo', 'true')]
        public ?UserGameDataObject $primaryUser,
        public ?UserGameDataObject $secondaryUser,
        #[RequiredIf('isSolo', 'false')]
        public ?TeamGameDataObject $primaryTeam,
        public ?TeamGameDataObject $secondaryTeam,
        public CarbonImmutable     $updatedAt,
        public CarbonImmutable     $createdAt,
    ){

    }
}
