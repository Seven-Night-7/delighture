<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model AS BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Model extends BaseModel
{
    use SoftDeletes;
}
