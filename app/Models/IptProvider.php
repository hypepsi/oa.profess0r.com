<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IptProvider extends Model
{
    protected $fillable = [
        'name',
        'bandwidth',
    ];

    // 带宽下拉菜单可选值
    public const BANDWIDTH_OPTIONS = [
        '1G'  => '1G',
        '10G' => '10G',
    ];
}
