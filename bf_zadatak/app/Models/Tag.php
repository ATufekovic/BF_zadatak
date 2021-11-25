<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Astrotomic\Translatable\Translatable;

class Tag extends Model
{
    use HasFactory, Translatable;

    public $timestamps = false;

    public $translatedAttributes = ["title"];

    public function meals()
    {
        //many-to-many for table "meals"
        return $this->belongsToMany(Meal::class);
    }

    /**
     * Function to store tag information into the given object $temp.
     * Translates into the correct locale according to string $lang.
     *
     * @param object $temp
     * @param string $lang
     */
    public function getDetails(object &$temp, string $lang)
    {
        $temp->id = $this->id;
        $temp->title = $this->translate($lang)->title;
        $temp->slug = $this->slug;
    }
}
