<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketType extends Model
{
    protected $fillable = ['name', 'quota', 'is_active'];

    protected $casts = ['is_active' => 'boolean', 'quota' => 'integer'];

    public function registrations()
    {
        return $this->hasMany(Registration::class);
    }

    // quota reached? (null quota = never full)
    public function isFull(?int $ignoreRegistrationId = null): bool
    {
        if ($this->quota === null) {
            return false;
        }

        return $this->registrations()->when($ignoreRegistrationId, fn ($q) => $q->where('id', '!=', $ignoreRegistrationId))->count() >= $this->quota;
    }
}
