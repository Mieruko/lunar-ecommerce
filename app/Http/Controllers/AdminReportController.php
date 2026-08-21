<?php
namespace App\Http\Controllers;
use App\Models\OrderItem; use App\Models\Payment; use Illuminate\Http\Request;
class AdminReportController extends Controller {
    public function export(Request $request) {
        abort_unless($request->user()?->hasPermission('reports.export'),403);
        $from=$request->filled('from') ? \Illuminate\Support\Carbon::parse($request->input('from'))->startOfDay() : now()->startOfMonth();
        $to=$request->filled('to') ? \Illuminate\Support\Carbon::parse($request->input('to'))->endOfDay() : now()->endOfDay();
        $rows=Payment::query()->with('order')->where('status','paid')->whereBetween('paid_at',[$from,$to])->latest('paid_at')->get();
        return response()->streamDownload(function()use($rows){$out=fopen('php://output','w');fputcsv($out,['Mã đơn','Thời gian','Cổng','Phương thức','Số tiền','Khách hàng']);foreach($rows as $p)fputcsv($out,[$p->order?->order_number,$p->paid_at?->format('d/m/Y H:i'),$p->provider,$p->payment_method,$p->amount,$p->order?->customer_name]);fclose($out);},'bao-cao-doanh-thu.csv',['Content-Type'=>'text/csv; charset=UTF-8']);
    }
}
