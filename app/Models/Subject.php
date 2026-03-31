<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'section',
        'instructor',
        'description',
        'user_id',
        'status',
    ];

    protected $casts = [
        'section' => 'array', // Cast section to array to support multiple sections
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function records()
    {
        return $this->hasMany(Record::class);
    }

    /**
     * Get all unique sections for this user (for form dropdowns)
     */
    public static function getUserSections($userId)
    {
        $sections = [];
        self::where('user_id', $userId)->whereNotNull('section')->get()->each(function ($subject) use (&$sections) {
            if (is_array($subject->section)) {
                $sections = array_merge($sections, $subject->section);
            } elseif ($subject->section) {
                $sections[] = $subject->section;
            }
        });
        return array_filter(array_unique($sections));
    }
}
