<?php

namespace ArsoftModules\Keuangan\Models;

use Illuminate\Database\Eloquent\Model;

class HierarchySubClass extends Model
{
    protected $table = 'dk_hierarki_subclass';
    protected $primaryKey = 'hs_id';

    public function hierarchyOne()
    {
        return $this->belongsTo('ArsoftModules\Keuangan\Models\HierarchyOne', 'hs_level_1', 'hs_id');
    }

    public function hierarchyTwo()
    {
        return $this->hasMany('ArsoftModules\Keuangan\Models\HierarchyTwo', 'hd_subclass','hs_id');
    }
}
