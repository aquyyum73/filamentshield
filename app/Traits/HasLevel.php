<?php

namespace App\Traits;

trait HasLevel
{
    public function getLevelAttribute(): int
    {
        return $this->level();
    }
    
    public function level(): int
    {
        return ($role = $this->roles->sortByDesc('level')->first()) ? $role->level : 0;
    }

    public function hasHigherLevelThan(int $level): bool
    {
        return $this->level() > $level;
    }
}