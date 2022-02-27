<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $table = 'answers';

    public function students()
    {
        return $this->belongsToMany(Student::class);
    }
    public function exams()
    {
        return $this->belongsToMany(Exam::class);
    }

    public function options()
    {
        return $this->belongsToMany(Option::class);
    }
    public function option()
    {
        return $this->belongsTo(Option::class);
    }
    public function questions()
    {
        return $this->belongsToMany(Question::class);
    }
    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    // public function classes() {
    //     return $this->belongsToMany(Student::class);
    // }
    //public $primaryKey  = 'id';
}
