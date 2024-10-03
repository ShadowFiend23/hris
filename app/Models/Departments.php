<?php

namespace App\Models;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Departments extends Model
{
    use HasFactory;

    protected $fillable = [
        "name",
        "departmentHead",
        "positions"
    ];

    static function withEmployees(){
        return self::join('employees',"departments.departmentHead",'=',"employees.id");
    }

    static function departmentSelect(){
        $arr = Schema::getColumnListing('departments');

        foreach ($arr as $i => $value) {
            $arr[$i] = "departments.$value as departments.$value";
        }

        return $arr;
    }
}
