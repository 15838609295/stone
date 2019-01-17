<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class CompanyUser extends Model
{
    protected $table='company_user';

    const IS_ADMIN = array(
        0 => '员工',
        1 => '企业主',
        2 => '主管'
    );
}