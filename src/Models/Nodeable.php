<?php

namespace Girover\Tree\Models;

use Girover\Tree\Traits\Nodeable as NodeableTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Nodeable extends Model
{
    // use SoftDeletes;
    use NodeableTrait, HasFactory;

    protected $guarded = [];


}
