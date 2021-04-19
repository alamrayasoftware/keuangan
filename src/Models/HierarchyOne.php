<?php

namespace ArsoftModules\Keuangan\Models;

use Illuminate\Database\Eloquent\Model;

class HierarchyOne extends Model
{
    protected $table = 'dk_hierarki_satu';
    protected $primaryKey = 'hs_id';

    public function hierarchySubClass()
    {
        return $this->hasMany('ArsoftModules\Keuangan\Models\HierarchySubClass', 'hs_level_1', 'hs_id');
    }
}
