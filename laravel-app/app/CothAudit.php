<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CothAudit extends Model
{
    protected $fillable = ['actor_user_id', 'action', 'subject_type', 'subject_id', 'note'];

    public static function write($action, $subjectType = null, $subjectId = null, $note = null)
    {
        return static::create([
            'actor_user_id' => auth()->id(),
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId === null ? null : (string) $subjectId,
            'note' => $note,
        ]);
    }
}
