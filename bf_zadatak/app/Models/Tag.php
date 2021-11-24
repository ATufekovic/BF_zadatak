<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Astrotomic\Translatable\Translatable;

class Tag extends Model
{
    use HasFactory, Translatable;
    public $timestamps = false;

    public $translatedAttributes = ["title"];

    public function meals(){//many-to-many for table "meals"
        return $this->belongsToMany(Meal::class);
    }

    public function getDetails(&$temp, $lang)
    {
        $temp->id = $this->id;
        $temp->title = $this->translate($lang)->title;
        $temp->slug = $this->slug;
    }
}
