<?php

namespace App\DataObjects;

use App\Enums\GameResult;
use App\Enums\GameType;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class GameThrowStatisticsDataObject extends Data
{
    public function __construct(
        public float $total,
        public float $hits,
        public float $hitRate,
        public float $edgeHits,
        public float $edgeHitRate,
        public float $misses,
        public float $missRate,
    ){

    }
}
