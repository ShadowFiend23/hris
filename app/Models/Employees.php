<?php

namespace App\Models;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Employees extends Model
{
    use HasFactory;

    protected $fillable = [
        "employeeID",
        "firstName",
        "lastName",
        "middleName",
        "suffix",
        "addressNo",
        "addressStreet",
        "addressProvince",
        "addressCity",
        "addressBarangay",
        "phoneNumber",
        "emailAddress",
        "department",
        "position",
        "birthDate",
        "photo",
        "employmentType",
        "status",
        "dateStart",
        "dateEnd",
        "gender",
        "maritalStatus",
        "supervisor",
    ];

    static function employeeSelect(){
        $arr = Schema::getColumnListing('employees');

        foreach ($arr as $i => $value) {
            $arr[$i] = "employees.$value as employees.$value";
        }

        return $arr;
    }
}
