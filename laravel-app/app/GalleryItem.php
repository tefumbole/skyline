<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GalleryItem extends Model
{
    protected $fillable = [
        'type',
        'board',
        'title',
        'description',
        'event_id',
        'event_label',
        'file_path',
        'thumb_path',
        'media_url',
        'sort_order',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'sort_order'   => 'integer',
    ];

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    /** Public URL for an uploaded file. */
    public function fileUrl()
    {
        return $this->publicPath($this->file_path);
    }

    public function thumbUrl()
    {
        return $this->publicPath($this->thumb_path) ?: $this->fileUrl();
    }

    public function eventKey()
    {
        if ($this->event_id) {
            return 'event-'.$this->event_id;
        }
        $label = trim((string) $this->event_label);
        if ($label === '') {
            return 'general';
        }

        return 'label-'.substr(md5(mb_strtolower($label)), 0, 12);
    }

    public function eventHeading()
    {
        if ($this->event_id && $this->event) {
            $date = $this->event->event_start_at ? $this->event->event_start_at->format('j M Y') : '';

            return trim($this->event->name.($date !== '' ? ' · '.$date : ''));
        }
        $label = trim((string) $this->event_label);

        return $label !== '' ? $label : 'General';
    }

    protected function publicPath($path)
    {
        if (! $path) {
            return null;
        }
        if (preg_match('#^(https?:)?//#', $path) || strpos($path, '/') === 0) {
            return $path;
        }

        return url('public/'.ltrim($path, '/'));
    }
}
