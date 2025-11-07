<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ItemLogisticsSnapshot;

class ProcurementReportController extends Controller
{
    public function itemLogistics()
    {
        return view('admin.procurement.reports.item-logistics');
    }

    public function itemLogisticsData(ItemLogisticsSnapshot $snapshot)
    {
        return response()->json($snapshot->build());
    }
}
