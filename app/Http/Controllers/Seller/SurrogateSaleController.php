<?php

namespace App\Http\Controllers\Seller;

use App\Http\Concerns\CreatesAdminSaleReservation;
use App\Http\Concerns\ManagesSurrogateSale;
use App\Http\Controllers\Controller;
use App\Support\SurrogateSaleFlow;

class SurrogateSaleController extends Controller
{
    use CreatesAdminSaleReservation;
    use ManagesSurrogateSale;

    protected function surrogateFlow(): SurrogateSaleFlow
    {
        return SurrogateSaleFlow::seller();
    }
}
