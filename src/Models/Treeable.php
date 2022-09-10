<?php

namespace Girover\Tree\Models;

use Girover\Tree\Traits\Treeable as TreeableTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Treeable extends Model
{
    // use SoftDeletes;
    use TreeableTrait, HasFactory;

    protected $guarded = [];

    public $timestamps = false;
}
