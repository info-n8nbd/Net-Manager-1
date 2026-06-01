<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class CompanyProfile extends Model
{
    use HasFactory, TenantAware;

    protected $fillable = [
        'name', 'email', 'phone', 'alt_phone', 'address', 'logo', 'favicon',
        'website', 'currency_symbol', 'currency_code', 'btrc_license',
        'bin_no', 'trade_license', 'invoice_footer', 'terms_conditions', 'company_id'
    ];
}
