<?php

namespace Girover\Tree\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TreeNode extends Model
{
    use HasFactory;

    /**
     * @var string the primary key of table `nodes`
     */
    protected $primaryKey = 'node_id';

    /**
     * @var bool removing timestamps from the table `nodes`
     */
    public $timestamps = false;

    /**
     * @var array make these field allowed for masse assign 
     */
    // protected $fillable = ['nodeable_id','treeable_id','gender','photo'];
    protected $guarded  = [];
}
