<?php

namespace App\Models;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Positions extends Model
{
    use HasFactory;

    protected $fillable = [
        "position",
        "abbreviation"
    ];

    static function positionSelect(){
        $arr = Schema::getColumnListing('positions');

        foreach ($arr as $i => $value) {
            $arr[$i] = "positions.$value as positions.$value";
        }

        return $arr;
    }
}
