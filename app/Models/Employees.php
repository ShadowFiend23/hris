<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
