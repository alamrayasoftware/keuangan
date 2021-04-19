<?php

namespace ArsoftModules\Keuangan\Models;

use Illuminate\Database\Eloquent\Model;

class HierarchyTwo extends Model
{
    protected $table = 'dk_hierarki_dua';
    protected $primaryKey = 'hd_id';

    public function hierarchyOne()
    {
        return $this->belongsTo('ArsoftModules\Keuangan\Models\HierarchyOne', 'hd_level_1', 'hs_id');
    }
    public function hierarchySubClass()
    {
        return $this->belongsTo('ArsoftModules\Keuangan\Models\HierarchySubClass', 'hd_subclass', 'hs_id');
    }
    public function financeAccount()
    {
        return $this->hasMany('ArsoftModules\Keuangan\Models\FinanceAccount', 'ak_kelompok', 'hd_id');
    }
}
