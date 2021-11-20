<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Astrotomic\Translatable\Translatable;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContracts;

use App\Models\Ingredient;
use App\Models\Tag;
use App\Models\Category;
use Illuminate\Database\Eloquent\SoftDeletes;

class Meal extends Model
{
    use HasFactory, Translatable;
    use SoftDeletes;

    public $translatedAttributes = ["title", "description"];

    public function ingredients(){//many-to-many for table "ingredients"
        return $this->belongsToMany(Ingredient::class);
    }

    public function tags(){//many-to-many for table "tags"
        return $this->belongsToMany(Tag::class);
    }

    public function category(){//one-to-many for table "categories"
        return $this->belongsTo(Category::class);
    }
}
