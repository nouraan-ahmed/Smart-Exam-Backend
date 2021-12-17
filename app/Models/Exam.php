<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Exam extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function config(): HasOne {
        return $this->hasOne(Configuration::class);
    }

    public function questions(): BelongsToMany {
        return $this->belongsToMany(Question::class,'exam_question', 'exam_id')->withTimestamps()->withPivot(['time', 'mark']);
    }
}
